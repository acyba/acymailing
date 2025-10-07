<?php

/**
 * To secure text echoed in HTML attributes or HTML content
 */
function acym_escape($text, bool $addSlashes = true): string
{
    if (is_array($text) || is_object($text)) {
        $text = json_encode($text);

        if ($addSlashes) {
            $text = str_replace('\\', '\\\\', $text);
        }
    }

    if (empty($text) && !is_numeric($text)) {
        return '';
    }

    if (!preg_match('#[&<>"\']#', $text)) {
        return $text;
    }

    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * To secure URLs echoed in HTML attributes
 */
function acym_escapeUrl(string $url): string
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

        $parsed = parse_url($url);

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

function acym_arrayToInteger(&$array)
{
    if (is_array($array)) {
        $array = @array_map('intval', $array);
    } else {
        $array = [];
    }
}

function acym_getIP()
{
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
        if (empty($_SERVER[$oneAttribute]) || strlen($_SERVER[$oneAttribute]) < 7) continue;

        $ipAddress = $_SERVER[$oneAttribute];
        break;
    }

    // Load balancers and CDNs may put multiple IPs comma separated
    if (strstr($ipAddress, ',') !== false) {
        $addresses = explode(',', $ipAddress);
        $ipAddress = trim(end($addresses));
    }

    // We add a strip tags here as the ip could be definitely be modified by something...
    return strip_tags($ipAddress);
}

function acym_generateKey($length)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randstring = '';
    $max = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        $randstring .= $characters[mt_rand(0, $max)];
    }

    return $randstring;
}

function acym_isRobot(): bool
{
    if (empty($_SERVER)) {
        return false;
    }
    // SpamBayes checks the images in the sent emails, so the stats image too... Don't count it as opened!
    if (!empty($_SERVER['HTTP_USER_AGENT']) && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'spambayes') !== false) {
        return true;
    }
    // Avoid auto-confirming by the Barracuda firewall installed on some mail clients
    if (!empty($_SERVER['REMOTE_ADDR']) && version_compare($_SERVER['REMOTE_ADDR'], '64.235.144.0', '>=') && version_compare($_SERVER['REMOTE_ADDR'], '64.235.159.255', '<=')) {
        return true;
    }

    return false;
}

function acym_displayErrors()
{
    error_reporting(E_ALL);
    @ini_set('display_errors', 1);
}

function acym_checkRobots()
{
    //Maybe add a check on the referer too? That may be really useful, if the referer is not in the list of allowed referers, we stop it
    if (preg_match('#(libwww-perl|python|googlebot)#i', @$_SERVER['HTTP_USER_AGENT'])) {
        die('Not allowed for robots. Please contact us if you are not a robot');
    }
}

function acym_noCache()
{
    acym_header('Cache-Control: no-store, no-cache, must-revalidate');
    acym_header('Cache-Control: post-check=0, pre-check=0', false);
    acym_header('Pragma: no-cache');
    acym_header('Expires: Wed, 17 Sep 1975 21:32:10 GMT');
}

function acym_isAllowed($controller, $task = ''): bool
{
    $config = acym_config();
    $globalAccess = $config->get('acl_'.$controller, 'all');
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

    return false;
}

function acym_raiseError($code, $message)
{
    echo '<link type="text/css" rel="stylesheet" href="'.ACYM_CSS.'back_global.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'back_global.min.css').'">';
    echo '<div id="acym_wrapper">';
    acym_display('Error '.$code.': '.$message, 'error', false);
    echo '</div>';
    http_response_code($code);
    exit;
}

/**
 * Check if the license is valid, make this check weekly
 *
 * @return bool
 */
function acym_isLicenseValidWeekly(): bool
{
    $config = acym_config();
    $expirationDate = $config->get('expirationdate', 0);
    // $expirationDate is empty when no call has been made yet on our server, or when it is a Starter license. Starter licenses don't have access to the cron
    if (empty($expirationDate) || (time() - 604800) > $config->get('lastlicensecheck', 0)) {
        acym_checkVersion();
        $config = acym_config(true);
        $expirationDate = $config->get('expirationdate', 0);
    }

    return $expirationDate >= time();
}
