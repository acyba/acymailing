<?php

function acym_getVar($type, $name, $default = null, $source = 'REQUEST', $mask = 0)
{
    $source = strtoupper($source);

    switch ($source) {
        case 'GET':
            $input = &$_GET;
            break;
        case 'POST':
            $input = &$_POST;
            break;
        case 'FILES':
            $input = &$_FILES;
            break;
        case 'COOKIE':
            $input = &$_COOKIE;
            break;
        case 'ENV':
            $input = &$_ENV;
            break;
        case 'SERVER':
            $input = &$_SERVER;
            break;
        default:
            $source = 'REQUEST';
            $input = &$_REQUEST;
            break;
    }

    if (!isset($input[$name])) {
        return $default;
    }

    $result = $input[$name];
    unset($input);
    if ($type == 'array') {
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
            $var = (string)$var;
            break;
        case 'int':
            $var = (int)$var;
            break;
        case 'float':
            $var = (float)$var;
            break;
        case 'boolean':
            $var = (boolean)$var;
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

function acym_setVar($name, $value = null, $hash = 'REQUEST', $overwrite = true)
{
    $hash = strtoupper($hash);

    switch ($hash) {
        case 'GET':
            $input = &$_GET;
            break;
        case 'POST':
            $input = &$_POST;
            break;
        case 'FILES':
            $input = &$_FILES;
            break;
        case 'COOKIE':
            $input = &$_COOKIE;
            break;
        case 'ENV':
            $input = &$_ENV;
            break;
        case 'SERVER':
            $input = &$_SERVER;
            break;
        default:
            $input = &$_REQUEST;
            break;
    }

    if (!isset($input[$name]) || $overwrite) {
        $input[$name] = $value;
    }
}

function acym_raiseError($level, $code, $msg, $info = null)
{
    acym_display($code.': '.$msg, 'error');
    wp_die();
}

function acym_isAdmin()
{
    $page = acym_getVar('string', 'page', '');

    if (!empty($page)) {
        return !in_array($page, [ACYM_COMPONENT.'_front', 'front']);
    } else {
        return is_admin();
    }
}

function acym_cmsLoaded()
{
    defined('ABSPATH') || die('Restricted access');
}

function acym_isDebug()
{
    return defined('WP_DEBUG') && WP_DEBUG;
}

function acym_askLog($current = true, $message = 'ACYM_NOTALLOWED', $type = 'error')
{
    //If the user is not logged in, we just redirect him to the login page....
    $url = acym_rootURI().'wp-login.php';
    if ($current) {
        $url .= '&redirect_to='.base64_encode(acym_currentURL());
    }

    acym_redirect($url, $message, $type);
}

function acym_getDefaultConfigValues()
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

function acym_cmsPermission()
{
    if (!current_user_can('manage_options')) return '';

    $config = acym_config();
    $roles = acym_getGroups();
    $options = [];
    $selected = explode(',', $config->get('wp_access', 'administrator'));

    foreach ($roles as $name => $oneRole) {
        if ($name === 'administrator') continue;
        $options[$name] = $oneRole->text;
    }

    asort($options);

    $option = '
		<div class="cell medium-7 grid-x">
			<label class="cell medium-6 small-9">'.acym_translation('ACYM_ACCESS').' '.acym_info('ACYM_ACCESS_DESC').'</label>
			<div class="cell auto">';

    $option .= acym_selectMultiple(
        $options,
        'config[wp_access]',
        $selected,
        ['class' => 'acym__select']
    );

    $option .= '</div>
		</div>';

    return $option;
}

function acym_checkVersion($ajax = false)
{

    return false;
}

function acym_triggerCmsHook($action, $args = [])
{
    array_unshift($args, $action);

    return call_user_func_array('do_action', $args);
}

function acym_getCmsCaptcha()
{
    return [];
}

function acym_loadCaptcha($captchaPluginName, $id)
{
    return '';
}

function acym_checkCaptcha($captchaPluginName)
{
    return true;
}
