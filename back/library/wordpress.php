<?php

define('ACYM_CMS', 'wordpress');
define('ACYM_CMS_TITLE', 'WordPress');
define('ACYM_COMPONENT', 'acymailing');
define('ACYM_DEFAULT_LANGUAGE', 'en-US');

define('ACYM_BASE', '');
// On wordpress.com, websites have access restrictions on the base folder. According to they we need $_SERVER['DOCUMENT_ROOT'] instead of the standard ABSPATH
$acyAbsPath = ABSPATH;
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
    $docRoot = $_SERVER['DOCUMENT_ROOT'];
    $pos = strpos(ABSPATH, $docRoot);
    if ($pos !== false) {
        $docRoot .= substr(ABSPATH, $pos + strlen($docRoot));
    }
    $docRoot = rtrim($docRoot, DS.'/').DS;
    if (str_replace($docRoot, '', WP_PLUGIN_DIR.DS) === 'wp-content/plugins/') {
        $acyAbsPath = $docRoot;
    }
}
define('ACYM_ROOT', rtrim($acyAbsPath, DS.'/').DS);
define('ACYM_FOLDER', WP_PLUGIN_DIR.DS.ACYM_COMPONENT.DS);
define('ACYM_FRONT', ACYM_FOLDER.'front'.DS);
define('ACYM_BACK', ACYM_FOLDER.'back'.DS);
define('ACYM_VIEW', ACYM_BACK.'views'.DS);
define('ACYM_PARTIAL_TOOLBAR', ACYM_BACK.'partial'.DS.'toolbar'.DS);
define('ACYM_VIEW_FRONT', ACYM_FRONT.'views'.DS);
define('ACYM_HELPER', ACYM_BACK.'helpers'.DS);
define('ACYM_CLASS', ACYM_BACK.'classes'.DS);
define('ACYM_LIBRARY', ACYM_BACK.'library'.DS);
define('ACYM_TYPE', ACYM_BACK.'types'.DS);
define('ACYM_CONTROLLER', ACYM_BACK.'controllers'.DS);
define('ACYM_CONTROLLER_FRONT', ACYM_FRONT.'controllers'.DS);
define('ACYM_MEDIA', ACYM_FOLDER.'media'.DS);

define('ACYM_WP_UPLOADS', basename(WP_CONTENT_DIR).DS.'uploads'.DS.ACYM_COMPONENT.DS);
define('ACYM_UPLOADS_PATH', ACYM_ROOT.ACYM_WP_UPLOADS);
define('ACYM_UPLOADS_URL', WP_CONTENT_URL.'/uploads/'.ACYM_COMPONENT.'/');

define('ACYM_LANGUAGE', ACYM_UPLOADS_PATH.'language'.DS);
define('ACYM_INC', ACYM_FRONT.'inc'.DS);
define('ACYM_UPLOAD_FOLDER', ACYM_WP_UPLOADS.'upload'.DS);
define('ACYM_TEMPLATE', ACYM_UPLOADS_PATH.'templates'.DS);
define('ACYM_TEMPLATE_URL', ACYM_UPLOADS_URL.'templates/');

define('ACYM_MEDIA_RELATIVE', str_replace(ACYM_ROOT, '', ACYM_MEDIA));
define('ACYM_MEDIA_URL', plugins_url().'/'.ACYM_COMPONENT.'/media/');
define('ACYM_IMAGES', ACYM_MEDIA_URL.'images/');
define('ACYM_CSS', ACYM_MEDIA_URL.'css/');
define('ACYM_JS', ACYM_MEDIA_URL.'js/');
define('ACYM_TEMPLATE_THUMBNAILS', ACYM_UPLOADS_URL.'thumbnails/');
define('ACYM_CORE_DYNAMICS_URL', plugins_url().'/'.ACYM_COMPONENT.'/back/dynamics/');
define('ACYM_DYNAMICS_URL', ACYM_UPLOADS_URL.'addons/');
define('ACYM_ADDONS_FOLDER_PATH', ACYM_UPLOADS_PATH.'addons'.DS);

define('ACYM_MEDIA_FOLDER', str_replace(ACYM_ROOT, '', WP_PLUGIN_DIR).'/'.ACYM_COMPONENT.'/media');
define('ACYM_UPLOAD_FOLDER_THUMBNAIL', WP_CONTENT_DIR.DS.'uploads'.DS.ACYM_COMPONENT.DS.'thumbnails'.DS);
define('ACYM_CUSTOM_PLUGIN_LAYOUT', ACYM_UPLOADS_PATH.'plugins'.DS);
define('ACYM_LOGS_FOLDER', ACYM_WP_UPLOADS.'logs'.DS);

define('ACYM_CMSV', get_bloginfo('version'));

define('ACYM_ALLOWRAW', 2);
define('ACYM_ALLOWHTML', 4);

include_once(rtrim(__DIR__, DS).DS.'punycode.php');

global $acyWPLangCodes;
$acyWPLangCodes = [
    'af' => 'af-ZA',
    'ar' => 'ar-AA',
    'as' => 'as-AS', // Not sure
    'az' => 'az-AZ', // Not sure
    'bo' => 'bo-BO', // Not sure
    'ca' => 'ca-ES',
    'cy' => 'cy-GB',
    'el' => 'el-GR',
    'eo' => 'eo-XX',
    'et' => 'et-EE',
    'eu' => 'eu-ES',
    'fi' => 'fi-FI',
    'gd' => 'gd-GD', // Not sure
    'gu' => 'gu-GU', // Not sure
    'hr' => 'hr-HR',
    'hy' => 'hy-AM',
    'ja' => 'ja-JP',
    'kk' => 'kk-KK', // Not sure
    'km' => 'km-KH',
    'lo' => 'lo-LO', // Not sure
    'lv' => 'lv-LV',
    'mn' => 'mn-MN', // Not sure
    'mr' => 'mr-MR', // Not sure
    'ps' => 'ps-PS', // Not sure
    'sq' => 'sq-AL',
    'te' => 'te-TE',
    'th' => 'th-TH',
    'tl' => 'tl-TL', // Not sure
    'uk' => 'uk-UA',
    'ur' => 'ur-PK', // Not sure
    'vi' => 'vi-VN',
];

global $acymLanguages;

function acym_getTimeOffsetCMS()
{
    static $timeoffset = null;
    if ($timeoffset === null) {
        $timeoffset = acym_getCMSConfig('offset');

        if (!is_numeric($timeoffset)) {
            $timezone = new DateTimeZone($timeoffset);
            $timeoffset = $timezone->getOffset(new DateTime());
        }
    }

    return $timeoffset;
}

/**
 * @param $url
 *
 * @return string returns the url content or false if couldn't get it
 */
function acym_fileGetContent($url, $timeout = 10)
{
    if (strpos($url, '_custom.ini') !== false && !file_exists($url)) {
        return '';
    }

    ob_start();
    $data = '';

    if (strpos($url, 'http') === 0 && class_exists('WP_Http') && method_exists('WP_Http', 'request')) {
        $args = ['timeout' => $timeout];
        $request = new WP_Http();
        $data = $request->request($url, $args);
        $data = (empty($data) || !is_array($data) || empty($data['body'])) ? '' : $data['body'];
    }

    if (empty($data) && function_exists('file_get_contents')) {
        if (!empty($timeout)) {
            ini_set('default_socket_timeout', $timeout);
        }
        $streamContext = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $data = file_get_contents($url, false, $streamContext);
    }

    if (empty($data) && function_exists('fopen') && function_exists('stream_get_contents')) {
        $handle = fopen($url, "r");
        if (!empty($timeout)) {
            stream_set_timeout($handle, $timeout);
        }
        $data = stream_get_contents($handle);
    }
    $warnings = ob_get_clean();

    if (acym_isDebug()) {
        echo $warnings;
    }

    return $data;
}

function acym_formToken()
{
    return '<input type="hidden" name="_wpnonce" value="'.wp_create_nonce('acymnonce').'">';
}

/**
 * Check token with all the possibilities
 */
function acym_checkToken()
{
    $token = acym_getVar('cmd', '_wpnonce');
    if (!wp_verify_nonce($token, 'acymnonce')) {
        die('Invalid Token');
    }
}

function acym_getFormToken()
{
    $token = acym_getVar('cmd', '_wpnonce', '');
    if (empty($token)) {
        $token = wp_create_nonce('acymnonce');
    }
    acym_setVar('_wpnonce', $token);

    return '_wpnonce='.$token;
}

/**
 * Display the translation based on a key
 *
 * @param string $key                  The translation key used in AcyMailing language files
 * @param bool   $jsSafe               Whether or not the result should be escaped
 * @param bool   $interpretBackSlashes Interpret or not the \ like \t \n etc...
 *
 * @return string
 */
function acym_translation($key, $jsSafe = false, $interpretBackSlashes = true)
{
    // Return the key passed by default as it may be a random text instead of a key
    $translation = $key;

    global $acymLanguages;
    acym_getLanguageTag();
    if (!isset($acymLanguages[$acymLanguages['currentLanguage']])) acym_loadLanguage();

    $acymailingEnglishText = '';
    foreach ($acymLanguages[ACYM_DEFAULT_LANGUAGE] as $fileContent) {
        if (isset($fileContent[$key])) {
            $acymailingEnglishText = $fileContent[$key];
            break;
        }
    }

    if (!empty($acymailingEnglishText)) {
        // If there is an english translation, get the wordpress translation. By default it returns the text passed, so the english translation
        $translation = __($acymailingEnglishText, 'acymailing');

        // If there is no translation on the WordPress side, and we're not in english, take the AcyMailing community translation
        if ($translation === $acymailingEnglishText && $acymLanguages['currentLanguage'] != ACYM_DEFAULT_LANGUAGE) {
            foreach ($acymLanguages[$acymLanguages['currentLanguage']] as $fileContent) {
                if (isset($fileContent[$key])) {
                    // If a translation is found for the specified key, take it
                    $translation = $fileContent[$key];
                    break;
                }
            }
        }
    }

    // Escape the quotes if we're in a javascript context
    if ($jsSafe) {
        $translation = str_replace('"', '\"', $translation);
    } elseif ($interpretBackSlashes && strpos($translation, '\\') !== false) {
        $translation = str_replace(['\\\\', '\t', '\n'], ["\\", "\t", "\n"], $translation);
    }

    return $translation;
}

/**
 * Display the according translation
 */
function acym_translation_sprintf()
{
    $args = func_get_args();
    $args[0] = acym_translation($args[0]);

    return call_user_func_array('sprintf', $args);
}

function acym_route($url, $xhtml = true, $ssl = null)
{
    return acym_baseURI().$url;
}

function acym_getVar($type, $name, $default = null, $hash = 'REQUEST', $mask = 0)
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
            $hash = 'REQUEST';
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
    if (in_array($hash, ['POST', 'REQUEST', 'GET', 'COOKIE'])) {
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

function acym_getGroupsByUser($userid = null, $recursive = null, $names = false)
{
    if ($userid === null) {
        $user = wp_get_current_user();
    } else {
        $user = new WP_User($userid);
    }

    return $user->roles;
}

function acym_getGroups()
{
    $groups = acym_loadResult('SELECT option_value FROM #__options WHERE option_name = "#__user_roles"');
    $groups = unserialize($groups);

    $usersPerGroup = acym_loadObjectList('SELECT meta_value, COUNT(meta_value) AS nbusers FROM #__usermeta WHERE meta_key = "#__capabilities" GROUP BY meta_value');

    $nbUsers = [];
    foreach ($usersPerGroup as $oneGroup) {
        $oneGroup->meta_value = unserialize($oneGroup->meta_value);
        $nbUsers[key($oneGroup->meta_value)] = $oneGroup->nbusers;
    }

    foreach ($groups as $key => $group) {
        $newGroup = new stdClass();
        $newGroup->id = $key;
        $newGroup->value = $key;
        $newGroup->parent_id = 0;
        $newGroup->text = $group['name'];
        $newGroup->nbusers = empty($nbUsers[$key]) ? 0 : $nbUsers[$key];
        $groups[$key] = $newGroup;
    }

    return $groups;
}

function acym_getLanguages($installed = false)
{
    global $acyWPLangCodes;

    $result = [];

    require_once ACYM_ROOT.'wp-admin/includes/translation-install.php';
    $wplanguages = wp_get_available_translations();
    $languages = get_available_languages();
    foreach ($languages as $oneLang) {
        $wpLangCode = $oneLang;
        if (!empty($acyWPLangCodes[$oneLang])) $oneLang = $acyWPLangCodes[$oneLang];
        $langTag = str_replace('_', '-', $oneLang);

        $lang = new stdClass();
        $lang->sef = empty($wplanguages[$oneLang]['iso'][1]) ? null : $wplanguages[$oneLang]['iso'][1];
        $lang->language = strtolower($langTag);
        $lang->name = empty($wplanguages[$wpLangCode]) ? $langTag : $wplanguages[$wpLangCode]['native_name'];
        $lang->exists = file_exists(ACYM_LANGUAGE.$langTag.'.'.ACYM_LANGUAGE_FILE.'.ini');
        $lang->content = true;

        $result[$langTag] = $lang;
    }

    if (!in_array('en-US', array_keys($result))) {
        $lang = new stdClass();
        $lang->sef = 'en';
        $lang->language = 'en-us';
        $lang->name = 'English (United States)';
        $lang->exists = file_exists(ACYM_LANGUAGE.'en-US.'.ACYM_LANGUAGE_FILE.'.ini');
        $lang->content = true;

        $result['en-US'] = $lang;
    }

    return $result;
}

function acym_punycode($email, $method = 'emailToPunycode')
{
    if (empty($email)) {
        return $email;
    }

    $explodedAddress = explode('@', $email);
    $newEmail = $explodedAddress[0];

    if (!empty($explodedAddress[1])) {
        $domainExploded = explode('.', $explodedAddress[1]);
        $newdomain = '';
        $puc = new acympunycode();

        foreach ($domainExploded as $domainex) {
            $domainex = $puc->$method($domainex);
            $newdomain .= $domainex.'.';
        }

        $newdomain = substr($newdomain, 0, -1);
        $newEmail = $newEmail.'@'.$newdomain;
    }

    return $newEmail;
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

function acym_getCMSConfig($varname, $default = null)
{
    $map = [
        'offset' => 'timezone_string',
        'list_limit' => 'posts_per_page',
        'sitename' => 'blogname',
        'mailfrom' => 'new_admin_email',
        'feed_email' => 'new_admin_email',
    ];

    if (!empty($map[$varname])) {
        $varname = $map[$varname];
    }
    $value = get_option($varname, $default);

    // In WP there are multiple possible formats in the same option for the timezone
    if ($varname == 'timezone_string' && empty($value)) {
        $value = acym_getCMSConfig('gmt_offset');

        if (empty($value)) {
            $value = 'UTC';
        } elseif ($value < 0) {
            $value = 'GMT'.$value;
        } else {
            $value = 'GMT+'.$value;
        }
    }

    // In WP this could be any number, but Acy pagination only works with 5,10,15,20,25,30,50 or 100
    if ($varname == 'posts_per_page') {
        $possibilities = [5, 10, 15, 20, 25, 30, 50, 100];
        $closest = 5;
        foreach ($possibilities as $possibility) {
            if (abs($value - $closest) > abs($value - $possibility)) {
                $closest = $possibility;
            }
        }
        $value = $closest;
    }

    return $value;
}

function acym_addPageParam($url, $ajax = false, $front = false)
{
    preg_match('#^([a-z]+)(?:[^a-z]|$)#Uis', $url, $ctrl);

    if ($front) {
        if ($ajax) {
            $link = 'admin-ajax.php?page='.ACYM_COMPONENT.'_front&ctrl='.$url.'&action='.ACYM_COMPONENT.'_frontrouter&'.acym_noTemplate();
        } else {
            $link = 'admin.php?page='.ACYM_COMPONENT.'_front&ctrl='.$url;
        }
        $link = 'wp-admin/'.$link;
    } else {
        if ($ajax) {
            $link = 'admin-ajax.php?page='.ACYM_COMPONENT.'_'.$ctrl[1].'&ctrl='.$url.'&action='.ACYM_COMPONENT.'_router&'.acym_noTemplate();
        } else {
            $link = 'admin.php?page='.ACYM_COMPONENT.'_'.$ctrl[1].'&ctrl='.$url;
        }
    }

    return $link;
}

function acym_redirect($url, $msg = '', $msgType = 'message')
{
    if (acym_isAdmin() && substr($url, 0, 4) != 'http' && substr($url, 0, 4) != 'www.') {
        $url = acym_addPageParam($url);
    }
    @ob_get_clean();
    if (empty($url)) $url = acym_rootURI();
    acym_addScript(true, 'window.location.href = "'.addslashes($url).'";');
    exit;
}

function acym_getLanguageTag($simple = false)
{
    if (acym_isAdmin()) {
        $currentLocale = get_user_locale(acym_currentUserId());
    } else {
        $currentLocale = get_locale();
    }

    if (strpos($currentLocale, '-') === false) {
        global $acyWPLangCodes;
        if (empty($acyWPLangCodes[$currentLocale])) {
            if (strpos($currentLocale, '_') === false) {
                $currentLocale = $currentLocale.'-'.strtoupper($currentLocale);
            } else {
                $currentLocale = str_replace('_', '-', $currentLocale);
            }
        } else {
            $currentLocale = $acyWPLangCodes[$currentLocale];
        }
    }

    global $acymLanguages;
    if (!isset($acymLanguages['currentLanguage']) || $acymLanguages['currentLanguage'] !== $currentLocale) {
        $acymLanguages['currentLanguage'] = $currentLocale;
    }

    return $simple ? substr($acymLanguages['currentLanguage'], 0, 2) : $acymLanguages['currentLanguage'];
}

function acym_baseURI($pathonly = false)
{
    if (acym_isAdmin()) {
        return acym_rootURI().'wp-admin/';
    }

    return acym_rootURI();
}

function acym_rootURI($pathonly = false, $path = 'siteurl')
{
    return get_option($path).'/';
}

function acym_currentUserId()
{
    return get_current_user_id();
}

function acym_currentUserName($userid = null)
{
    if (!empty($userid)) {
        $special = get_user_by('id', $userid);

        return $special->display_name;
    }

    $current_user = wp_get_current_user();

    return $current_user->display_name;
}

function acym_currentUserEmail($userid = null)
{
    if (!empty($userid)) {
        $special = get_user_by('id', $userid);

        return $special->user_email;
    }

    $current_user = wp_get_current_user();

    return $current_user->user_email;
}

function acym_loadLanguageFile($extension, $basePath = null, $lang = null, $reload = false, $default = true)
{
    global $acymLanguages;
    $currentLanguage = acym_getLanguageTag();
    if (isset($acymLanguages[$currentLanguage][$extension]) && !$reload) return;

    $base = ACYM_LANGUAGE;
    $language = $currentLanguage;

    if (!file_exists($base.$language.'.'.$extension.'.ini')) {
        $language = ACYM_DEFAULT_LANGUAGE;
        if (!file_exists($base.$language.'.'.$extension.'.ini')) {
            $base = ACYM_FOLDER.'language'.DS;
            $language = $currentLanguage;
            if (!file_exists($base.$language.'.'.$extension.'.ini')) {
                $language = ACYM_DEFAULT_LANGUAGE;
                if (!file_exists($base.$language.'.'.$extension.'.ini')) return;
            }
        }
    }

    $data = acym_fileGetContent($base.$language.'.'.$extension.'.ini');
    $data = str_replace('"_QQ_"', '"', $data);
    $separate = explode("\n", $data);
    foreach ($separate as $raw) {
        if (strpos($raw, '=') === false) continue;

        $keyval = explode('=', $raw);
        $key = array_shift($keyval);

        $acymLanguages[$acymLanguages['currentLanguage']][$extension][$key] = trim(implode('=', $keyval), "\"\r\n\t ");
    }

    if ($language == ACYM_DEFAULT_LANGUAGE) return;

    $data = acym_fileGetContent($base.ACYM_DEFAULT_LANGUAGE.'.'.$extension.'.ini');
    $data = str_replace('"_QQ_"', '"', $data);
    $separate = explode("\n", $data);

    foreach ($separate as $raw) {
        if (strpos($raw, '=') === false) continue;

        $keyval = explode('=', $raw);
        $key = array_shift($keyval);

        $acymLanguages[ACYM_DEFAULT_LANGUAGE][$extension][$key] = trim(implode('=', $keyval), "\"\r\n\t ");
    }
}

function acym_escapeDB($value)
{
    return "'".esc_sql($value)."'";
}

function acym_query($query)
{
    global $wpdb;
    $query = acym_prepareQuery($query);

    $result = $wpdb->query($query);

    return $result === false ? null : $result;
}

function acym_loadObjectList($query, $key = '', $offset = null, $limit = null)
{
    global $wpdb;
    $query = acym_prepareQuery($query);

    if (isset($offset)) {
        $query .= ' LIMIT '.intval($offset).','.intval($limit);
    }

    $results = $wpdb->get_results($query);
    if (empty($key)) {
        return $results;
    }

    $sorted = [];
    foreach ($results as $oneRes) {
        $sorted[$oneRes->$key] = $oneRes;
    }

    return $sorted;
}

function acym_prepareQuery($query)
{
    global $wpdb;
    $query = str_replace('#__', $wpdb->prefix, $query);
    if (is_multisite()) {
        $query = str_replace($wpdb->prefix.'users', $wpdb->base_prefix.'users', $query);
    }

    return $query;
}

function acym_date($time = 'now', $format = null, $useTz = true, $gregorian = false)
{
    if ($time == 'now') {
        $time = time();
    }

    if (is_numeric($time)) {
        $time = date('Y-m-d H:i:s', $time);
    }

    if (!$format || (strpos($format, 'ACYM_DATE_FORMAT') !== false && acym_translation($format) == $format)) {
        $format = 'ACYM_DATE_FORMAT_LC1';
    }
    $format = acym_translation($format);

    //Don't use timezone
    if ($useTz === false) {
        $date = new DateTime($time);

        return acym_translateDate($date->format($format));
    } else {
        //use timezone
        $cmsOffset = acym_getCMSConfig('offset');

        $timezone = new DateTimeZone($cmsOffset);

        if (!is_numeric($cmsOffset)) {
            $cmsOffset = $timezone->getOffset(new DateTime);
        }

        return acym_translateDate(date($format, strtotime($time) + $cmsOffset));
    }
}

function acym_loadObject($query)
{
    global $wpdb;
    $query = acym_prepareQuery($query);

    return $wpdb->get_row($query);
}

function acym_loadResult($query)
{
    global $wpdb;
    $query = acym_prepareQuery($query);

    return $wpdb->get_var($query);
}

function acym_loadResultArray($query)
{
    global $wpdb;
    $query = acym_prepareQuery($query);

    return $wpdb->get_col($query);
}

function acym_getEscaped($text, $extra = false)
{
    $result = esc_sql($text);
    if ($extra) {
        $result = addcslashes($result, '%_');
    }

    return $result;
}

function acym_getDBError()
{
    global $wpdb;

    return $wpdb->last_error;
}

function acym_insertObject($table, $element)
{
    global $wpdb;
    $element = get_object_vars($element);
    $table = acym_prepareQuery($table);
    $wpdb->insert($table, $element);

    return $wpdb->insert_id;
}

function acym_updateObject($table, $element, $pkey)
{
    global $wpdb;
    $element = get_object_vars($element);
    $table = acym_prepareQuery($table);

    if (!is_array($pkey)) {
        $pkey = [$pkey];
    }

    $where = [];
    foreach ($pkey as $onePkey) {
        $where[$onePkey] = $element[$onePkey];
    }

    $nbUpdated = $wpdb->update($table, $element, $where);

    return $nbUpdated !== false;
}

function acym_getPrefix()
{
    global $wpdb;

    return $wpdb->prefix;
}

function acym_getTableList()
{
    global $wpdb;

    return acym_loadResultArray("SELECT table_name FROM information_schema.tables WHERE table_schema = '".$wpdb->dbname."' AND table_name LIKE '".$wpdb->prefix."%'");
}

function acym_completeLink($link, $popup = false, $redirect = false, $forceNoPopup = false)
{
    if (($popup || acym_isNoTemplate()) && $forceNoPopup == false) {
        $link .= '&'.acym_noTemplate();
    }

    $link = acym_addPageParam($link);

    return acym_route($link);
}

function acym_noTemplate()
{
    return 'noheader=1';
}

function acym_isNoTemplate()
{
    return acym_getVar('cmd', 'noheader') == '1';
}

function acym_setNoTemplate($status = true)
{
    if ($status) {
        acym_setVar('noheader', '1');
    } else {
        unset($_REQUEST['noheader']);
    }
}

function acym_cmsLoaded()
{
    defined('ABSPATH') || die('Restricted access');
}

/**
 * @param bool   $token
 * @param string $task
 * @param string $currentStep
 * @param string $currentCtrl
 */
function acym_formOptions($token = true, $task = '', $currentStep = null, $currentCtrl = '')
{
    if (!empty($currentStep)) {
        echo '<input type="hidden" name="step" value="'.$currentStep.'"/>';
        echo '<input type="hidden" name="nextstep" value=""/>';
    }
    echo '<input type="hidden" name="task" value="'.$task.'"/>';
    echo '<input type="hidden" name="nextstep" value=""/>';
    echo '<input type="hidden" name="page" value="'.acym_getVar('cmd', 'page', '').'"/>';
    echo empty($currentCtrl) ? '<input type="hidden" name="ctrl" value="'.acym_getVar('cmd', 'ctrl', '').'"/>' : '<input type="hidden" name="ctrl" value="'.$currentCtrl.'"/>';
    if ($token) {
        echo acym_formToken();
    }
    echo '<button type="submit" class="is-hidden" id="formSubmit"></button>';
}

function acym_enqueueMessage($message, $type = 'success')
{
    $type = str_replace(['notice', 'message'], ['info', 'success'], $type);
    $message = is_array($message) ? implode('<br/>', $message) : $message;

    $notification = new stdClass();
    $notification->message = $message;
    $notification->date = time();
    $notification->read = false;
    $notification->level = $type;

    $handledTypes = ['info', 'warning', 'error'];

    if (acym_isAdmin()) {
        $helperHeader = acym_get('helper.header');
        $notification->id = $helperHeader->addNotification($notification);
    } else {
        $handledTypes[] = 'success';
    }

    if (in_array($type, $handledTypes)) {
        acym_session();
        if (empty($_SESSION['acymessage'.$type]) || !in_array($message, $_SESSION['acymessage'.$type])) {
            $_SESSION['acymessage'.$type][$notification->id] = $message;
        }
    }

    return true;
}

function acym_displayMessages()
{
    $types = ['success', 'info', 'warning', 'error'];
    acym_session();
    foreach ($types as $id => $type) {
        if (empty($_SESSION['acymessage'.$type])) continue;

        acym_display($_SESSION['acymessage'.$type], $type);
        unset($_SESSION['acymessage'.$type]);
    }
}

/**
 * If you use it to prepare a POST ajax, make sure you add the action and page parameters to the data passed, it's not taken into account if it's only in the URL
 */
function acym_prepareAjaxURL($url)
{
    return htmlspecialchars_decode(acym_route(acym_addPageParam($url, true)));
}

function acym_isDebug()
{
    return defined('WP_DEBUG') && WP_DEBUG;
}

// DO NOT USE OUTSIDE OF THIS FILE
function acym_translateDate($date)
{
    $map = [
        'January' => 'ACYM_JANUARY',
        'February' => 'ACYM_FEBRUARY',
        'March' => 'ACYM_MARCH',
        'April' => 'ACYM_APRIL',
        'May' => 'ACYM_MAY',
        'June' => 'ACYM_JUNE',
        'July' => 'ACYM_JULY',
        'August' => 'ACYM_AUGUST',
        'September' => 'ACYM_SEPTEMBER',
        'October' => 'ACYM_OCTOBER',
        'November' => 'ACYM_NOVEMBER',
        'December' => 'ACYM_DECEMBER',
        'Monday' => 'ACYM_MONDAY',
        'Tuesday' => 'ACYM_TUESDAY',
        'Wednesday' => 'ACYM_WEDNESDAY',
        'Thursday' => 'ACYM_THURSDAY',
        'Friday' => 'ACYM_FRIDAY',
        'Saturday' => 'ACYM_SATURDAY',
        'Sunday' => 'ACYM_SUNDAY',
    ];

    foreach ($map as $english => $translationKey) {
        $translation = acym_translation($translationKey);
        if ($translation == $translationKey) {
            continue;
        }

        $date = preg_replace('#'.preg_quote($english).'( |,|$)#i', $translation.'$1', $date);
        $date = preg_replace('#'.preg_quote(substr($english, 0, 3)).'( |,|$)#i', mb_substr($translation, 0, 3).'$1', $date);
    }

    return $date;
}

function acym_addScript($raw, $script, $type = 'text/javascript', $defer = false, $async = false, $needTagScript = false, $deps = ['jquery'])
{
    static $scriptNumber = 0;
    $scriptNumber++;
    if ($raw) {
        if (!empty($deps['script_name'])) {
            wp_add_inline_script($deps['script_name'], $script);
        } else {
            echo '<script type="'.$type.'">'.$script.'</script>';
        }
    } elseif ($defer || $async || $needTagScript) {
        echo '<script type="'.$type.'" src="'.$script.'"'.($async ? ' async' : '').($defer ? ' defer' : '').'></script>';
    } else {
        wp_enqueue_script('script'.$scriptNumber, $script, $deps);
    }

    return 'script'.$scriptNumber;
}

function acym_addStyle($raw, $style, $type = 'text/css', $media = null, $attribs = [])
{
    if ($raw) {
        echo '<style type="'.$type.'"'.(empty($media) ? '' : ' media="'.$media.'"').'>'.$style.'</style>';
    } else {
        echo '<link rel="stylesheet" href="'.$style.'" type="'.$type.'"'.(empty($media) ? '' : ' media="'.$media.'"').'>';
    }
}

global $acymMetaData;
function acym_addMetadata($meta, $data, $name = 'name')
{
    global $acymMetaData;

    $tag = new stdClass();
    $tag->meta = $meta;
    $tag->data = $data;
    $tag->name = $name;

    $acymMetaData[] = $tag;
}

add_action('wp_head', 'acym_head_wp');
add_action('admin_head', 'acym_head_wp');
add_action('acym_head', 'acym_head_wp');
function acym_head_wp()
{
    global $acymMetaData;

    if (!empty($acymMetaData)) {
        foreach ($acymMetaData as $metadata) {
            if (empty($metadata->data)) continue;
            echo '<meta '.$metadata->name.'="'.acym_escape($metadata->meta).'" content="'.acym_escape($metadata->data).'"/>';
        }
    }

    $acymMetaData = [];
}

function acym_includeHeaders()
{
    do_action('acym_head');
}

function acym_getLanguagePath($basePath, $language = null)
{
    return rtrim(ACYM_LANGUAGE, DS);
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

function acym_frontendLink($link, $complete = true)
{
    return acym_rootURI().acym_addPageParam($link, true, true);
}

function acym_getMenu()
{
    return null;
}

function acym_extractArchive($archive, $destination)
{
    if (substr($archive, strlen($archive) - 4) !== '.zip') {
        return false;
    }

    WP_Filesystem();

    return true === unzip_file($archive, $destination);
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

// Front-end breadcrumb for Joomla
function acym_addBreadcrumb($title, $link = '')
{
}

// Page title modification for Joomla
function acym_setPageTitle($title)
{
}

function acym_cmsModal($isIframe, $content, $buttonText, $isButton, $identifier = null, $width = '800', $height = '400')
{
    // Use the WP's thickbox library
    add_thickbox();

    $class = $isButton ? ' button' : '';

    if ($isIframe) {
        return '<a href="'.$content.'&TB_iframe=true&width='.$width.'&height='.$height.'" class="thickbox'.$class.'">'.acym_translation($buttonText).'</a>';
    } else {
        if (empty($identifier)) {
            $identifier = 'identifier_'.rand(1000, 9000);
        }

        return '<div id="'.$identifier.'" style="display:none;">'.$content.'</div>
                <a href="#TB_inline?width='.$width.'&height='.$height.'&inlineId='.$identifier.'" class="thickbox'.$class.'">'.acym_translation($buttonText).'</a>';
    }
}

function acym_CMSArticleTitle($id)
{
    return acym_loadResult('SELECT post_title FROM #__posts WHERE ID = '.intval($id));
}

function acym_getArticleURL($id, $popup, $text)
{
    if (empty($id)) return '';

    $url = get_permalink($id);

    if ($popup == 1) {
        $url .= (strpos($url, '?') ? '&' : '?').acym_noTemplate();
        $url = acym_cmsModal(true, $url, $text, false);
    } else {
        $url = '<a title="'.acym_translation($text, true).'" href="'.acym_escape($url).'" target="_blank">'.acym_translation($text).'</a>';
    }

    return $url;
}

function acym_articleSelectionPage()
{
    return 'admin-ajax.php?action=acymailing_router&page=acymailing_configuration&ctrl=configuration&task=getarticles&'.acym_getFormToken();
}

function acym_getPageOverride($name, $view)
{
    // No override system known for WP
    return '';
}

function acym_isLeftMenuNecessary()
{
    // Already one in WP
    return false;
}

function acym_getLeftMenu($name)
{
    return '';
}

function acym_cmsCleanHtml($html)
{
    if (strpos($html, '<!-- wp:') === false) return $html;

    // Replace special WP content in inserted posts and pages
    $elementsToRemove = [
        'shortcode',
        'core-embed/.*',
        'video .*',
        'audio .*',
    ];

    $replacements = [
        '#<!-- wp:core-embed/vimeo.*"url":"([^"]+)".+<!-- /wp:core-embed/vimeo -->#Uis' => '{vimeo}$1{/vimeo}',
        '#<!-- wp:core-embed/youtube.*"url":"([^"]+)".+<!-- /wp:core-embed/youtube -->#Uis' => '{youtube}$1{/youtube}',
        '#<a [^>]*wp-block-file__button[^>]*>[^<]*</a>#Uis' => '',
    ];

    foreach ($elementsToRemove as $oneElement) {
        $replacements['#<!-- wp:'.$oneElement.' -->.*<!-- /wp:'.$oneElement.' -->#Uis'] = '';
    }

    $cleanText = preg_replace(array_keys($replacements), $replacements, $html);
    if (!empty($cleanText)) $html = $cleanText;

    // Display the WP content correctly
    $html .= '<style type="text/css">
        /* Handle media-text blocks */
        .wp-block-media-text {
            display: grid;
            grid-template-rows: auto;
            align-items: center;
            grid-template-areas: "media-text-media media-text-content";
            grid-template-columns: 50% auto;
        }
        .wp-block-media-text .wp-block-media-text__media {
            grid-area: media-text-media;
            margin: 0;
        }
        .wp-block-media-text .wp-block-media-text__content {
            word-break: break-word;
            grid-area: media-text-content;
            padding: 0 8%;
        }

        /* Handle multi column blocks */
        .wp-block-columns {
            display: flex !important;
            flex-wrap: nowrap;
        }
        .wp-block-columns .wp-block-column {
            flex-basis: 100%;
            flex-grow: 0;
        }

        /* Handle WP tables */
        table.wp-block-table td {
            padding: 1em 1.41575em !important;
        }

        /* Handle preformatted content */
        .wp-block-preformatted, .wp-block-code, .wp-block-verse {
            padding: 1.618em;
        }

        /* Handle download files */
        .wp-block-file {
            margin: 20px 0;
        }

        /* Handle cover blocks */
        .wp-block-cover, .wp-block-cover-image {
            -webkit-box-orient: horizontal;
            -webkit-box-direction: normal;
            -webkit-flex-flow: row wrap;
            flex-flow: row wrap;
            position: relative;
            background-color: #000;
            background-size: cover;
            background-position: 50%;
            min-height: 430px;
            width: 100%;
            margin: 0 0 1.5em;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }
        .wp-block-cover-image.has-background-dim:before, .wp-block-cover.has-background-dim:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background-color: inherit;
            opacity: .5;
            z-index: 1;
        }
        .wp-block-cover p {
            font-size: 1.618em;
            font-weight: 300;
            line-height: 1.618;
            padding: 1em;
            color: #fff !important;
            z-index: 1;
        }

        /* Handle galleries */
        .wp-block-gallery {
            margin: 0 0 1.41575em;
            display: flex;
            flex-wrap: wrap;
            list-style-type: none;
            padding: 0;
        }
        .blocks-gallery-item {
            margin-left: auto;
            margin-right: auto;
        }
        </style>';

    return $html;
}

function acym_isPluginActive($plugin, $family = 'system')
{
    return true;
}

function acym_getPluginsPath($file, $dir)
{
    return substr(plugin_dir_path($file), 0, strpos(plugin_dir_path($file), plugin_basename($dir)));
}

function acym_getPluginPath($plugin)
{
    $corePath = ACYM_BACK.'dynamics'.DS.$plugin.DS.'plugin.php';
    if (file_exists($corePath)) return $corePath;

    return ACYM_ADDONS_FOLDER_PATH.$plugin.DS.'plugin.php';
}

function acym_prepareFrontViewDisplay($ctrl)
{
    if (acym_isAdmin()) return;

    $config = acym_config();
    if ('archive' !== $ctrl && $config->get('unsubpage_header', 0) == 1) get_header();
}

function acym_isExtensionActive($extension)
{
    if (function_exists('is_plugin_active')) return is_plugin_active($extension);

    return file_exists(WP_PLUGIN_DIR.DS.$extension);
}

function acym_loadCmsScripts()
{
    $toggleController = acym_isAdmin() ? 'toggle' : 'fronttoggle';
    acym_addScript(
        true,
        'var ACYM_TOGGLE_URL = "admin-ajax.php?action='.ACYM_COMPONENT.'_router&'.acym_noTemplate().'&page='.ACYM_COMPONENT.'_toggle&ctrl='.$toggleController.'&'.acym_getFormToken().'";
        var ACYM_AJAX_URL = "admin-ajax.php?action='.ACYM_COMPONENT.'_router&'.acym_noTemplate().'&'.acym_getFormToken().'";
        var ACYM_IS_ADMIN = '.(acym_isAdmin() ? 'true' : 'false').';

        if("undefined" === typeof icl_ajxloaderimg_src) var icl_ajxloaderimg_src = "";'
    );

    // Without this line the image insertion and dtexts button don't work
    wp_enqueue_media();

    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui-draggable');
    wp_enqueue_script('jquery-ui-droppable');
    wp_enqueue_script('jquery-ui-sortable');
    wp_enqueue_script('jquery-effects-slide');
}

function acym_menuOnly($link)
{
}

function acym_getAlias($name)
{
    return sanitize_title_with_dashes(remove_accents($name));
}

function acym_replaceGroupTags($uploadFolder)
{
    if (strpos($uploadFolder, '{groupname}') === false) return $uploadFolder;

    // Get user groups
    $groups = acym_getGroupsByUser(acym_currentUserId());
    $group = array_shift($groups);

    $uploadFolder = str_replace(
        '{groupname}',
        strtolower(str_replace(' ', '_', $group)),
        $uploadFolder
    );

    return $uploadFolder;
}

function acym_getCmsUserEdit($userId)
{
    return 'user-edit.php?user_id='.intval($userId);
}

function acym_disableCmsEditor()
{
    add_filter(
        'user_can_richedit',
        function ($a) {
            return false;
        },
        50
    );
}

function acym_isElementorEdition()
{
    global $post;

    if (empty($post) || !class_exists('\\Elementor\\Plugin')) return false;

    return \Elementor\Plugin::$instance->db->is_built_with_elementor($post->ID);
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
		<div class="cell medium-6 grid-x">
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

function acym_languageOption($emailLanguage, $name)
{
    return '';
}

function acym_coreAddons(){
    return [
        (object)[
            'title' => acym_translation('ACYM_ARTICLE'),
            'folder_name' => 'post',
            'version' => '{__VERSION__}',
            'active' => '1',
            'category' => 'Content management',
            'level' => 'starter',
            'uptodate' => '1',
            'features' => '["content"]',
            'description' => '- Insert WordPress posts in your emails<br/>- Insert the latest posts of a category in an automatic email',
            'latest_version' => '{__VERSION__}',
            'core' => '1',
        ],
        (object)[
            'title' => acym_translation('ACYM_PAGE'),
            'folder_name' => 'page',
            'version' => '{__VERSION__}',
            'active' => '1',
            'category' => 'Content management',
            'level' => 'starter',
            'uptodate' => '1',
            'features' => '["content"]',
            'description' => '- Insert pages in your emails',
            'latest_version' => '{__VERSION__}',
            'core' => '1',
        ],
    ];
}

global $acymCmsUserVars;
$acymCmsUserVars = new stdClass();
$acymCmsUserVars->table = '#__users';
$acymCmsUserVars->name = 'display_name';
$acymCmsUserVars->username = 'user_login';
$acymCmsUserVars->id = 'id';
$acymCmsUserVars->email = 'user_email';
$acymCmsUserVars->registered = 'user_registered';
$acymCmsUserVars->blocked = 'user_status';

// Needed to display the fields in front / params
class JFormField
{
}
