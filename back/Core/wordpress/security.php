<?php

defined('ABSPATH') || die('Restricted Access');

use AcyMailing\Helpers\UpdatemeHelper;

/**
 * @param mixed $default
 *
 * @return mixed
 */
function acym_getVar(string $type, string $name, $default = null, string $source = 'REQUEST', int $mask = 0)
{
    $source = strtoupper($source);

    switch ($source) {
        case 'FILES':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is the caller's responsibility for this generic helper.
            $input = &$_FILES;
            break;
        case 'COOKIE':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is the caller's responsibility for this generic helper.
            $input = &$_COOKIE;
            break;
        case 'SERVER':
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification is the caller's responsibility for this generic helper.
            $input = &$_SERVER;
            break;
        case 'SESSION':
            acym_session();
            $input = &$_SESSION;
            break;
        default:
            $source = 'REQUEST';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is the caller's responsibility for this generic helper.
            $input = &$_REQUEST;
            break;
    }

    if (!isset($input[$name])) {
        return $default;
    }

    $result = $input[$name];
    unset($input);
    if ($type === 'array') {
        $result = (array)$result;
    }

    // WP alters every variable in $_REQUEST... Seriously...
    if (in_array($source, ['POST', 'REQUEST', 'GET', 'COOKIE'])) {
        $result = acym_stripslashes($result);
    }

    return acym_cleanVar($result, $type, $mask);
}

function acym_stripslashes($element)
{
    if (is_array($element)) {
        foreach ($element as &$oneCell) {
            $oneCell = acym_stripslashes($oneCell);
        }
    } elseif (is_string($element)) {
        $element = stripslashes($element);
    }

    return $element;
}

function acym_cleanVar($var, $type, $mask)
{
    if (is_array($var)) {
        foreach ($var as $i => $val) {
            $var[$i] = acym_cleanVar($val, $type, $mask);
        }

        return $var;
    }

    switch ($type) {
        case 'string':
            $var = strval($var);
            break;
        case 'int':
            $var = intval($var);
            break;
        case 'float':
            $var = floatval($var);
            break;
        case 'bool':
        case 'boolean':
            $var = boolval($var);
            break;
        case 'word':
            $var = preg_replace('#[^a-zA-Z_]#', '', $var);
            break;
        case 'cmd':
            $var = preg_replace('#[^a-zA-Z0-9_\.-]#', '', $var);
            $var = ltrim($var, '.');
            break;
        default:
            break;
    }

    if (!is_string($var)) {
        return $var;
    }

    $var = trim($var);

    if ($mask & ACYM_ALLOWRAW) {
        return $var;
    }

    if (!preg_match('//u', $var)) {
        // String contains invalid byte sequence, remove it
        $var = htmlspecialchars_decode(htmlspecialchars($var, ENT_IGNORE, 'UTF-8'));
    }

    if (!($mask & ACYM_ALLOWHTML)) {
        $var = preg_replace('#<[a-zA-Z/]+[^>]*>#Uis', '', $var);
    }

    return $var;
}

function acym_setVar(string $name, $value): void
{
    $_REQUEST[$name] = $value;
}

/**
 * Function detecting the context of a request, not related to security checks
 */
function acym_isAdmin(): bool
{
    // Outside of /wp-admin we are on the front-end, whatever the request parameters say
    if (!is_admin()) {
        return false;
    }

    $page = acym_getVar('string', 'page', '');

    return !in_array($page, [ACYM_COMPONENT.'_front', 'front'], true);
}

function acym_cmsLoaded(): void
{
    defined('ABSPATH') || die('Restricted access');
}

function acym_isDebug(): bool
{
    return defined('WP_DEBUG') && WP_DEBUG;
}

function acym_askLog(bool $current = true, string $message = 'ACYM_NOTALLOWED', string $type = 'error'): void
{
    //If the user is not logged in, we just redirect him to the login page....
    $url = acym_rootURI().'wp-login.php';
    if ($current) {
        $url .= '&redirect_to='.base64_encode(acym_currentURL());
    }

    acym_redirect($url, $message, $type);
}

function acym_getDefaultConfigValues(): array
{
    $allPref = [];

    $allPref['from_name'] = get_option('fromname', '');
    $allPref['from_email'] = get_option('admin_email', '');
    $allPref['bounce_email'] = $allPref['from_email'];
    $allPref['sendmail_path'] = '';
    $allPref['smtp_port'] = get_option('mailserver_port', '');
    $allPref['smtp_secured'] = $allPref['smtp_port'] == 465 ? 'ssl' : '';
    $allPref['smtp_auth'] = 1;
    $allPref['smtp_username'] = get_option('mailserver_login', '');
    $allPref['smtp_password'] = get_option('mailserver_pass', '');
    $allPref['mailer_method'] = empty($allPref['smtp_host']) ? 'phpmail' : 'smtp';
    $allPref['smtp_host'] = get_option('mailserver_url', '');
    $allPref['cron_savepath'] = ACYM_LOGS_FOLDER.'report{year}_{month}.log';

    return $allPref;
}

function acym_hasAdminPermissions(): bool
{
    return current_user_can('manage_options');
}

/**
 * May the user reach the AcyMailing back-end at all? Same rule as the menu visibility (Menu::addMenus).
 */
function acym_hasBackofficeAccess(): bool
{
    $config = acym_config();
    $allowedGroups = explode(',', $config->get('wp_access', 'administrator'));

    $userGroups = acym_getGroupsByUser();
    foreach ($userGroups as $oneGroup) {
        if ($oneGroup == 'administrator' || in_array($oneGroup, $allowedGroups)) {
            return true;
        }
    }

    return false;
}

function acym_cmsPermission(): void
{
    if (!acym_hasAdminPermissions()) {
        return;
    }

    $config = acym_config();
    $roles = acym_getGroups();
    $options = [];
    $selected = explode(',', $config->get('wp_access', 'administrator'));

    foreach ($roles as $name => $oneRole) {
        if ($name === 'administrator') {
            continue;
        }
        $options[$name] = $oneRole->text;
    }

    asort($options);

    echo '<div class="cell grid-x">
			<label class="cell large-3 medium-5 small-9">'.acym_escapeHtml(acym_translation('ACYM_ACCESS')).' ';
    acym_info(['textShownInTooltip' => 'ACYM_ACCESS_DESC']);
    echo '</label>
			<div class="cell auto">';

    acym_selectMultiple(
        $options,
        'config[wp_access]',
        $selected,
        ['class' => 'acym__select'],
        'value',
        'text',
        true,
    );

    echo '</div>
		</div>';
}

function acym_triggerCmsHook(string $action, array $args = [], bool $isAction = true)
{
    array_unshift($args, $action);

    return call_user_func_array($isAction ? 'do_action' : 'apply_filters', $args);
}

function acym_getCmsCaptcha(): array
{
    return [];
}

function acym_loadCaptcha(string $captchaPluginName, string $id): void
{
}

function acym_checkCaptcha(string $captchaPluginName, ?string $response = null): bool
{
    return true;
}

function acym_getSiteSalt(): string
{
    return wp_salt('auth');
}

function acym_stripTags(string $text): string
{
    return wp_strip_all_tags($text);
}

function acym_setSession(string $name, $value, bool $remove = false): void
{
    acym_session();

    if ($remove) {
        unset($_SESSION[$name]);
    } else {
        $_SESSION[$name] = $value;
    }
}
