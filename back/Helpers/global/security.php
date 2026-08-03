<?php
// context verification

use AcyMailing\Helpers\UpdatemeHelper;

/**
 * To secure text echoed in HTML attributes
 */
function acym_escape(string $text): string
{
    if (empty($text) && !is_numeric($text)) {
        return '';
    }

    if (!preg_match('#[&<>"\']#', $text)) {
        return $text;
    }

    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * To secure text echoed in HTML content
 */
function acym_escapeHtml(string $text): string
{
    return acym_escape($text);
}

/**
 * To secure text echoed in HTML content, and whitelisting some HTML tags
 * $allowedHtmlTags:
 *  [
 *      'a' => [
 *          'href' => true
 *      ],
 *      'br' => []
 *  ]
 */
function acym_escapeHtmlWithAllowedTags(string $text, array $allowedHtmlTags = []): string
{
    // Attributes whose value is an URL and must therefore not carry a dangerous scheme.
    $urlAttributes = ['href', 'src', 'xlink:href', 'action', 'formaction'];

    return preg_replace_callback(
        '#<\s*(/?)\s*([a-z][a-z0-9]*)\b([^>]*?)(/?)\s*>#i',
        static function (array $m) use ($allowedHtmlTags, $urlAttributes): string {
            [, $closing, $tag, $attrString, $selfClosing] = $m;
            $tag = strtolower($tag);

            if (!array_key_exists($tag, $allowedHtmlTags)) {
                return '';
            }

            if ($closing === '/') {
                return '</'.$tag.'>';
            }

            $keptAttrs = '';
            if (
                !empty($allowedHtmlTags[$tag])
                && preg_match_all(
                    '#([a-z][a-z0-9:_-]*)\s*(?:=\s*("[^"]*"|\'[^\']*\'|[^\s>]+))?#i',
                    $attrString,
                    $attrMatches,
                    PREG_SET_ORDER
                )
            ) {
                foreach ($attrMatches as $attr) {
                    $name = strtolower($attr[1]);
                    if (empty($allowedHtmlTags[$tag][$name])) {
                        continue;
                    }

                    $value = isset($attr[2]) ? $attr[2] : '';

                    // Drop URL attributes whose value uses a dangerous scheme.
                    if ($value !== '' && in_array($name, $urlAttributes, true)) {
                        // Normalize (unquote, decode entities, remove whitespace/control chars
                        // that could hide the scheme) only to run the check.
                        $normalized = strtolower(
                            preg_replace('/[\s\x00-\x20]+/', '', html_entity_decode(trim($value, '"\''), ENT_QUOTES))
                        );
                        if (preg_match('#^(?:javascript|data|vbscript):#', $normalized)) {
                            continue;
                        }
                    }

                    $keptAttrs .= $value !== '' ? " {$name}={$value}" : " {$name}";
                }
            }

            $selfClose = $selfClosing === '/' ? ' /' : '';

            return '<'.$tag.$keptAttrs.$selfClose.'>';
        },
        $text
    );
}

/**
 * To secure URLs echoed in HTML attributes
 */
function acym_escapeUrl(?string $url): string
{
    if (empty($url)) {
        return '';
    }

    $url = str_replace(' ', '%20', ltrim($url));
    $url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\[\]\\x80-\\xff]|i', '', $url);

    if (empty($url)) {
        return '';
    }

    if (0 !== stripos($url, 'mailto:')) {
        $strip = ['%0d', '%0a', '%0D', '%0A'];
        $count = 1;
        while ($count) {
            $url = str_replace($strip, '', $url, $count);
        }
    }

    $url = str_replace(';//', '://', $url);
    if (strpos($url, ':') === false && !in_array($url[0], ['/', '#', '?'], true) && !preg_match('/^[a-z0-9-]+?\.php/i', $url)) {
        $url = 'https://'.$url;
    }

    $url = str_replace('&amp;', '&#038;', $url);
    $url = str_replace("'", '&#039;', $url);

    if (strpos($url, '[') !== false || strpos($url, ']') !== false) {
        $to_unset = [];

        if (strpos($url, '//') === 0) {
            $to_unset[] = 'scheme';
            $url = 'placeholder:'.$url;
        } elseif (strpos($url, '/') === 0) {
            $to_unset[] = 'scheme';
            $to_unset[] = 'host';
            $url = 'placeholder://placeholder'.$url;
        }

        $parsed = acym_parseUrl($url);

        if (!empty($parsed)) {
            foreach ($to_unset as $key) {
                unset($parsed[$key]);
            }
        }

        $front = '';

        if (isset($parsed['scheme'])) {
            $front .= $parsed['scheme'].'://';
        } elseif ('/' === $url[0]) {
            $front .= '//';
        }

        if (isset($parsed['user'])) {
            $front .= $parsed['user'];
        }

        if (isset($parsed['pass'])) {
            $front .= ':'.$parsed['pass'];
        }

        if (isset($parsed['user']) || isset($parsed['pass'])) {
            $front .= '@';
        }

        if (isset($parsed['host'])) {
            $front .= $parsed['host'];
        }

        if (isset($parsed['port'])) {
            $front .= ':'.$parsed['port'];
        }

        $end_dirty = str_replace($front, '', $url);
        $end_clean = str_replace(['[', ']'], ['%5B', '%5D'], $end_dirty);
        $url = str_replace($end_dirty, $end_clean, $url);
    }

    return $url;
}

function acym_arrayToInteger(array &$array): void
{
    $array = @array_map('intval', $array);
}

function acym_getIP(bool $full = false): string
{
    $config = acym_config();
    $ipCollection = $config->get('ip_collection', 'yes');

    if (!$full && $ipCollection === 'no') {
        return '';
    }

    $map = [
        'HTTP_X_FORWARDED_IP',
        'X_FORWARDED_FOR',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    ];

    $ipAddress = '';
    foreach ($map as $oneAttribute) {
        $serverValue = acym_getVar('string', $oneAttribute, '', 'SERVER');
        if (empty($serverValue) || strlen($serverValue) < 7) {
            continue;
        }

        $ipAddress = $serverValue;
        break;
    }

    // Load balancers and CDNs may put multiple IPs comma separated
    if (strstr($ipAddress, ',') !== false) {
        $addresses = explode(',', $ipAddress);
        $ipAddress = trim(end($addresses));
    }

    // We strip tags here as the ip could be modified by something
    $ipAddress = acym_stripTags($ipAddress);

    if ($full || $ipCollection === 'yes') {
        return $ipAddress;
    }

    return acym_anonymizeIP($ipAddress);
}

function acym_anonymizeIP(string $ipAddress): string
{
    if (empty($ipAddress)) {
        return '';
    }

    // IPv6
    if (strpos($ipAddress, ':') !== false) {
        $packed = inet_pton($ipAddress);
        if ($packed === false) {
            return '';
        }

        $anonymized = substr($packed, 0, 8).str_repeat("\x00", 8);

        return inet_ntop($anonymized);
    }

    // IPv4
    $parts = explode('.', $ipAddress);
    if (count($parts) !== 4) {
        return '';
    }

    $parts[3] = '0';

    return implode('.', $parts);
}

function acym_generateKey(int $length): string
{
    $charactersPool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    $max = strlen($charactersPool) - 1;
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $charactersPool[acym_rand(0, $max)];
    }

    return $randomString;
}

function acym_isRobot(): bool
{
    if (empty($_SERVER)) {
        return false;
    }

    // Real calls are made using GET
    $requestMethod = acym_getVar('string', 'REQUEST_METHOD', '', 'SERVER');
    if (!empty($requestMethod) && $requestMethod === 'HEAD') {
        return true;
    }

    // SpamBayes checks the images in the sent emails, so the stats image too... Don't count it as opened!
    $userAgent = acym_getVar('string', 'HTTP_USER_AGENT', '', 'SERVER');
    if (!empty($userAgent) && strpos(strtolower($userAgent), 'spambayes') !== false) {
        return true;
    }

    // Avoid auto-confirming by the Barracuda firewall installed on some mail clients
    $remoteIp = acym_getVar('string', 'REMOTE_ADDR', '', 'SERVER');
    if (!empty($remoteIp) && version_compare($remoteIp, '64.235.144.0', '>=') && version_compare($remoteIp, '64.235.159.255', '<=')) {
        return true;
    }

    return false;
}

function acym_displayErrors(): void
{
    // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_error_reporting -- intended error printing on export and debug mode.
    error_reporting(E_ALL);
    // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged -- Error display is toggled intentionally for debugging purposes.
    @ini_set('display_errors', 1);
}

function acym_checkRobots(): void
{
    $userAgent = acym_getVar('string', 'HTTP_USER_AGENT', null, 'SERVER');
    if (!empty($userAgent) && preg_match('#(libwww-perl|python|googlebot)#i', $userAgent)) {
        die('Not allowed for robots. Please contact us if you are not a robot');
    }
}

function acym_noCache(): void
{
    acym_header('Cache-Control: no-store, no-cache, must-revalidate');
    acym_header('Cache-Control: post-check=0, pre-check=0', false);
    acym_header('Pragma: no-cache');
    acym_header('Expires: Wed, 17 Sep 1975 21:32:10 GMT');
}

function acym_conditionsContainRestrictedFilter($data): bool
{
    if (!is_array($data)) {
        return false;
    }

    foreach ($data as $key => $value) {
        if ($key === 'sqladvanced' && is_array($value) && (!empty($value['query']) || !empty($value['conn_host']))) {
            return true;
        }
        if (is_array($value) && acym_conditionsContainRestrictedFilter($value)) {
            return true;
        }
    }

    return false;
}

function acym_blockRestrictedFilterAjax($conditions): void
{
    if (acym_conditionsContainRestrictedFilter($conditions) && !acym_hasAdminPermissions()) {
        acym_sendAjaxResponse(acym_translation('ACYM_ACCESS_DENIED'), [], false);
    }
}

function acym_isAllowed(string $controller, string $task = ''): bool
{
    if ($controller === 'dashboard') {
        // Promotional pages and the home page hold no sensitive data, but they stay reserved to the back-end users
        if (in_array($task, ['acychecker', 'features', 'listing', 'upgrade'])) {
            return acym_hasBackofficeAccess();
        }

        return acym_isAllowed('configuration');
    }

    $controller = str_replace('front', '', $controller);

    $config = acym_config();
    $globalAccess = $config->get('acl_'.$controller, ACYM_ADMIN_GROUP);
    if ($globalAccess === 'all') {
        return true;
    }

    $globalAccess = explode(',', $globalAccess);
    $globalAccess[] = ACYM_ADMIN_GROUP;

    $userId = acym_currentUserId();
    if (empty($userId)) {
        return false;
    }

    $userGroups = acym_getGroupsByUser($userId);
    if (empty($userGroups)) {
        return false;
    }

    foreach ($userGroups as $oneGroup) {
        if (in_array($oneGroup, $globalAccess)) {
            return true;
        }
    }

    // Having access to campaigns gives access to some email actions
    if ($controller === 'mails' && in_array($task, ['autoSave', 'getTemplateAjax']) && acym_isAllowed('campaigns')) {
        return true;
    }

    // The language management is made in the configuration
    if ($controller === 'language' && acym_isAllowed('configuration')) {
        return true;
    }

    // When editing a campaign or a template, the user might need attachments
    if ($controller === 'file' && (acym_isAllowed('campaigns') || acym_isAllowed('mails'))) {
        return true;
    }

    // When editing a campaign, one can need to get a segment's number of affected users from the segment step
    if (in_array($task, ['countResultsTotal', 'countGlobalBySegmentId', 'countResults']) && acym_isAllowed('campaigns')) {
        return true;
    }

    // When editing an email, lists might be loaded with ajax
    if ($controller === 'lists' && $task === 'setAjaxListing' && (acym_isAllowed('campaigns') || acym_isAllowed('mails'))) {
        return true;
    }

    // The zones are used in the editor
    if (
        $controller === 'zones'
        && (
            acym_isAllowed('campaigns')
            || acym_isAllowed('mails')
        )
    ) {
        return true;
    }

    // Togglable icons are located in most features
    if (
        $controller === 'toggle'
        && (
            acym_isAllowed('campaigns')
            || acym_isAllowed('mails')
            || acym_isAllowed('automation')
            || acym_isAllowed('segments')
            || acym_isAllowed('scenarios')
            || acym_isAllowed('forms')
            || acym_isAllowed('users')
            || acym_isAllowed('lists')
            || acym_isAllowed('fields')
            || acym_isAllowed('bounces')
        )
    ) {
        return true;
    }

    // The add-ons are used in the editor and the filters
    if (
        $controller === 'dynamics'
        && (
            acym_isAllowed('campaigns')
            || acym_isAllowed('mails')
            || acym_isAllowed('automation')
            || acym_isAllowed('segments')
            || acym_isAllowed('scenarios')
        )
    ) {
        return true;
    }

    // The entity select is used in multiple features
    if (
        $task === 'loadEntityFront'
        && (
            acym_isAllowed('campaigns')
            || acym_isAllowed('mails')
            || acym_isAllowed('users')
            || acym_isAllowed('lists')
        )
    ) {
        return true;
    }

    return false;
}

function acym_raiseError(int $code, string $message): void
{
    // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet -- Ending script here, no hook available.
    echo '<link type="text/css" rel="stylesheet" href="'.acym_escapeUrl(ACYM_CSS.'back_global.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'back_global.min.css')).'">';
    echo '<div id="acym_wrapper">';
    acym_display('Error '.$code.': '.$message, 'error', false);
    echo '</div>';
    http_response_code($code);
    exit;
}

/**
 * Check if the license is valid, make this check weekly
 */
function acym_isLicenseValidWeekly(): bool
{
    $config = acym_config();
    $expirationDate = $config->get('expirationdate', 0);
    // $expirationDate is empty when no call has been made yet on our server, or when it is a Starter license. Starter licenses don't have access to the cron
    if (empty($expirationDate) || (time() - 604800) > $config->get('lastlicensecheck', 0)) {
        UpdatemeHelper::getLicenseInfo();
        $config = acym_config(true);
        $expirationDate = $config->get('expirationdate', 0);
    }

    return $expirationDate >= time();
}

/**
 * Generate a time-limited autologin token for a subscriber.
 * Token format: hex(timestamp).hmac_signature
 * The token is bound to the subscriber ID and their secret key, and signed with the site's auth salt.
 */
function acym_generateAutologinToken(int $subId, string $subKey): string
{
    $timestamp = time();
    $payload = $subId.'|'.$timestamp;
    $secret = $subKey.acym_getSiteSalt();
    $signature = hash_hmac('sha256', $payload, $secret);

    return dechex($timestamp).'.'.$signature;
}

/**
 * Verify an autologin token. Returns true if the token is valid and not expired.
 *
 * @param int    $subId     The subscriber ID
 * @param string $token     The token from the URL
 * @param string $storedKey The subscriber's stored secret key
 */
function acym_verifyAutologinToken(int $subId, string $token, string $storedKey): bool
{
    $parts = explode('.', $token, 2);
    if (count($parts) !== 2) {
        return false;
    }

    $timestamp = @hexdec($parts[0]);
    $signature = $parts[1];

    if (empty($timestamp) || $timestamp <= 0) {
        return false;
    }

    $config = acym_config();
    $maxAgeHours = intval($config->get('autologin_token_duration', 48));
    $maxAge = $maxAgeHours * 3600;

    if ((time() - $timestamp) > $maxAge) {
        return false;
    }

    $payload = $subId.'|'.$timestamp;
    $secret = $storedKey.acym_getSiteSalt();
    $expectedSignature = hash_hmac('sha256', $payload, $secret);

    return hash_equals($expectedSignature, $signature);
}
