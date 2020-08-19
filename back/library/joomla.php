<?php

define('ACYM_CMS', 'joomla');
define('ACYM_CMS_TITLE', 'Joomla!');
define('ACYM_COMPONENT', 'com_acym');
define('ACYM_DEFAULT_LANGUAGE', 'en-GB');

define('ACYM_BASE', rtrim(JPATH_BASE, DS).DS);
define('ACYM_ROOT', rtrim(JPATH_ROOT, DS).DS);
define('ACYM_FRONT', rtrim(JPATH_SITE, DS).DS.'components'.DS.ACYM_COMPONENT.DS);
define('ACYM_BACK', rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.ACYM_COMPONENT.DS);
define('ACYM_VIEW', ACYM_BACK.'views'.DS);
define('ACYM_PARTIAL', ACYM_BACK.'partial'.DS);
define('ACYM_VIEW_FRONT', ACYM_FRONT.'views'.DS);
define('ACYM_HELPER', ACYM_BACK.'helpers'.DS);
define('ACYM_CLASS', ACYM_BACK.'classes'.DS);
define('ACYM_LIBRARY', ACYM_BACK.'library'.DS);
define('ACYM_TYPE', ACYM_BACK.'types'.DS);
define('ACYM_CONTROLLER', ACYM_BACK.'controllers'.DS);
define('ACYM_CONTROLLER_FRONT', ACYM_FRONT.'controllers'.DS);
define('ACYM_MEDIA', ACYM_ROOT.'media'.DS.ACYM_COMPONENT.DS);
define('ACYM_LANGUAGE', ACYM_ROOT.'language'.DS);
define('ACYM_INC', ACYM_FRONT.'inc'.DS);

define('ACYM_MEDIA_RELATIVE', 'media/'.ACYM_COMPONENT.'/');
define('ACYM_MEDIA_URL', acym_rootURI().ACYM_MEDIA_RELATIVE);
define('ACYM_IMAGES', ACYM_MEDIA_URL.'images/');
define('ACYM_CSS', ACYM_MEDIA_URL.'css/');
define('ACYM_JS', ACYM_MEDIA_URL.'js/');
define('ACYM_TEMPLATE', ACYM_MEDIA.'templates'.DS);
define('ACYM_TEMPLATE_URL', ACYM_MEDIA_URL.'templates'.DS);
define('ACYM_TEMPLATE_THUMBNAILS', ACYM_IMAGES.'thumbnails/');
define('ACYM_CORE_DYNAMICS_URL', acym_rootURI().'administrator/components/'.ACYM_COMPONENT.'/dynamics/');
define('ACYM_DYNAMICS_URL', ACYM_CORE_DYNAMICS_URL);
define('ACYM_ADDONS_FOLDER_PATH', ACYM_BACK.'dynamics'.DS);

define('ACYM_MEDIA_FOLDER', 'media/'.ACYM_COMPONENT);
define('ACYM_UPLOAD_FOLDER', ACYM_MEDIA_FOLDER.DS.'upload'.DS);
define('ACYM_UPLOAD_FOLDER_THUMBNAIL', ACYM_MEDIA.'images'.DS.'thumbnails'.DS);

define('ACYM_CUSTOM_PLUGIN_LAYOUT', ACYM_MEDIA.'plugins'.DS);
define('ACYM_LOGS_FOLDER', ACYM_MEDIA_FOLDER.DS.'logs'.DS);

//Avoid the issue with 3.0.0_beta
$jversion = preg_replace('#[^0-9\.]#i', '', JVERSION);
define('ACYM_CMSV', $jversion);
define('ACYM_J30', version_compare($jversion, '3.0.0', '>='));
define('ACYM_J37', version_compare($jversion, '3.7.0', '>='));
define('ACYM_J39', version_compare($jversion, '3.9.0', '>='));
define('ACYM_J40', version_compare($jversion, '4.0.0', '>='));

define('ACYM_ALLOWRAW', defined('JREQUEST_ALLOWRAW') ? JREQUEST_ALLOWRAW : 2);
define('ACYM_ALLOWHTML', defined('JREQUEST_ALLOWHTML') ? JREQUEST_ALLOWHTML : 4);

use Joomla\CMS\Language\LanguageHelper;
use Joomla\Archive\Archive;

function acym_getTimeOffsetCMS()
{
    static $timeoffset = null;
    if ($timeoffset === null) {

        $dateC = JFactory::getDate(
            'now',
            acym_getCMSConfig('offset')
        );
        $timeoffset = $dateC->getOffsetFromGMT(true) * 3600;
    }

    return $timeoffset;
}

/**
 * @param $url
 *
 * @return returns the url content or false if couldn't get it
 */
function acym_fileGetContent($url, $timeout = 10)
{
    ob_start();
    // use the Joomla way first
    $data = '';
    if (class_exists('JHttpFactory') && method_exists('JHttpFactory', 'getHttp')) {
        $http = JHttpFactory::getHttp();
        try {
            $response = $http->get($url, [], $timeout);
        } catch (RuntimeException $e) {
            $response = null;
        }

        if ($response !== null && $response->code === 200) {
            $data = $response->body;
        }
    }

    if (empty($data) && function_exists('curl_exec') && filter_var($url, FILTER_VALIDATE_URL)) {
        $conn = curl_init($url);
        curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($conn, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($timeout)) {
            curl_setopt($conn, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($conn, CURLOPT_CONNECTTIMEOUT, $timeout);
        }

        $data = curl_exec($conn);
        if ($data === false) {
            echo curl_error($conn);
        }
        curl_close($conn);
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
    return JHTML::_('form.token');
}

/**
 * Check token with all the possibilities
 */
function acym_checkToken()
{
    if (ACYM_J40) {
        \JSession::checkToken() || \JSession::checkToken('get') || die('Invalid Token');
    } else {
        if (!JRequest::checkToken() && !JRequest::checkToken('get')) {
            JSession::checkToken() || JSession::checkToken('get') || die('Invalid Token');
        }
    }
}

function acym_getFormToken()
{
    if (ACYM_J30) {
        return JSession::getFormToken().'=1';
    }

    return JUtility::getToken().'=1';
}

function acym_translation($key, $jsSafe = false, $interpretBackSlashes = true)
{
    $translation = JText::_($key, false, $interpretBackSlashes);

    if ($jsSafe) {
        $translation = str_replace('"', '\"', $translation);
    }

    return $translation;
}

function acym_translation_sprintf()
{
    $args = func_get_args();

    return call_user_func_array(['JText', 'sprintf'], $args);
}

function acym_route($url, $xhtml = true, $ssl = null)
{
    return JRoute::_($url, $xhtml, $ssl);
}

function acym_getVar($type, $name, $default = null, $hash = 'default', $mask = 0)
{
    if (ACYM_J40) {
        if ($mask & ACYM_ALLOWRAW) {
            $type = 'RAW';
        } elseif ($mask & ACYM_ALLOWHTML) {
            $type = 'HTML';
        }

        $result = JFactory::getApplication()->input->get($name, $default, $type);
    } else {
        $result = JRequest::getVar($name, $default, $hash, $type, $mask);
    }

    if (is_string($result) && !($mask & ACYM_ALLOWRAW)) {
        return JComponentHelper::filterText($result);
    }

    return $result;
}

function acym_setVar($name, $value = null, $hash = 'method', $overwrite = true)
{
    if (ACYM_J40) {
        return JFactory::getApplication()->input->set($name, $value);
    }

    return JRequest::setVar($name, $value, $hash, $overwrite);
}

function acym_raiseError($level, $code, $msg, $info = null)
{
    return JError::raise($level, $code, $msg, $info);
}

/**
 * @param null $userid
 * @param null $recursive
 * @param bool $names Return an array of ids or names
 *
 * @return array
 */
function acym_getGroupsByUser($userid = null, $recursive = null, $names = false)
{
    if ($userid === null) {
        $userid = acym_currentUserId();
        $recursive = true;
    }

    jimport('joomla.access.access');

    $groups = JAccess::getGroupsByUser($userid, $recursive);

    if ($names) {
        acym_arrayToInteger($groups);
        $groups = acym_loadResultArray(
            'SELECT ugroup.title 
            FROM #__usergroups AS ugroup 
            JOIN #__user_usergroup_map AS map ON ugroup.id = map.group_id 
            WHERE map.user_id = '.intval($userid).' AND ugroup.id IN ('.implode(',', $groups).')'
        );
    }

    return $groups;
}

function acym_getGroups()
{
    $groups = acym_loadObjectList('SELECT a.*, a.title AS text, a.id AS value, COUNT(ugm.user_id) AS nbusers FROM #__usergroups AS a LEFT JOIN #__user_usergroup_map ugm ON a.id = ugm.group_id GROUP BY a.id', 'id');

    return $groups;
}

function acym_getLanguages($installed = false, $uppercase = false)
{
    $result = [];

    $path = acym_getLanguagePath(ACYM_ROOT);
    $dirs = acym_getFolders($path);

    $languages = acym_loadObjectList('SELECT * FROM #__languages', 'lang_code');

    foreach ($dirs as $dir) {
        if (strlen($dir) != 5 || $dir == "xx-XX") {
            continue;
        }
        if ($installed && (empty($languages[$dir]) || $languages[$dir]->published != 1)) {
            continue;
        }

        $xmlFiles = acym_getFiles($path.DS.$dir, '^([-_A-Za-z]*)\.xml$');
        $xmlFile = reset($xmlFiles);
        if (empty($xmlFile)) {
            $data = [];
        } else {
            if (ACYM_J40) {
                $data = \JInstaller::parseXMLInstallFile(ACYM_LANGUAGE.$dir.DS.$xmlFile);
            } else {
                $data = JApplicationHelper::parseXMLLangMetaFile(ACYM_LANGUAGE.$dir.DS.$xmlFile);
            }
        }

        $lang = new stdClass();
        $lang->sef = empty($languages[$dir]) ? null : $languages[$dir]->sef;
        $lang->language = $uppercase ? $dir : strtolower($dir);
        $lang->name = empty($data['name']) ? (empty($languages[$dir]) ? $dir : $languages[$dir]->title_native) : $data['name'];
        $lang->exists = file_exists(ACYM_LANGUAGE.$dir.DS.$dir.'.'.ACYM_COMPONENT.'.ini');
        $lang->content = empty($languages[$dir]) ? false : $languages[$dir]->published == 1;

        $result[$dir] = $lang;
    }

    return $result;
}

function acym_punycode($email, $method = 'emailToPunycode')
{
    if (empty($email) || version_compare(ACYM_CMSV, '3.1.2', '<')) {
        return $email;
    }
    $email = JStringPunycode::$method($email);

    return $email;
}

function acym_extractArchive($archive, $destination)
{
    if (ACYM_J40) {
        $archiveManager = new Archive();

        return $archiveManager->extract($archive, $destination);
    } else {
        return JArchive::extract($archive, $destination);
    }
}

function acym_addScript($raw, $script, $type = 'text/javascript', $defer = true, $async = false, $needTagScript = false, $deps = ['jquery'])
{
    $acyDocument = acym_getGlobal('doc');

    if ($raw) {
        $acyDocument->addScriptDeclaration($script, $type);
    } else {
        if (ACYM_J40) {
            $acyDocument->addScript($script, [], ['defer' => $defer, 'async' => $async, 'type' => $type]);
        } else {
            $acyDocument->addScript($script, $type, $defer, $async);
        }
    }

    return 'acym_script';
}

function acym_addStyle($raw, $style, $type = 'text/css', $media = null, $attribs = [])
{
    $acyDocument = acym_getGlobal('doc');

    if ($raw) {
        $acyDocument->addStyleDeclaration($style, $type);
    } else {
        $acyDocument->addStyleSheet($style, $type, $media, $attribs);
    }
}

function acym_addMetadata($meta, $data, $name = 'name')
{
    $acyDocument = acym_getGlobal('doc');

    $acyDocument->setMetaData($meta, $data, $name);
}

function acym_isAdmin()
{
    $acyapp = acym_getGlobal('app');

    if (ACYM_J40) {
        return $acyapp->isClient('administrator');
    } else {
        return $acyapp->isAdmin();
    }
}

function acym_getCMSConfig($varname, $default = null)
{
    if (ACYM_J30) {
        $acyapp = acym_getGlobal('app');

        return $acyapp->getCfg($varname, $default);
    }

    $conf = JFactory::getConfig();
    $val = $conf->getValue('config.'.$varname);

    return empty($val) ? $default : $val;
}

function acym_redirect($url, $msg = '', $msgType = 'message')
{
    $msg = acym_translation($msg);
    $acyapp = acym_getGlobal('app');

    if (ACYM_J40) {
        if (!empty($msg)) {
            acym_enqueueMessage($msg, $msgType);
        }

        return $acyapp->redirect($url);
    } else {
        return $acyapp->redirect($url, $msg, $msgType);
    }
}

function acym_getLanguageTag($simple = false)
{
    $acylanguage = JFactory::getLanguage();
    $langCode = $acylanguage->getTag();

    return $simple ? substr($langCode, 0, 2) : $langCode;
}

function acym_baseURI($pathonly = false)
{
    return JURI::base($pathonly);
}

function acym_rootURI($pathonly = false, $path = null)
{
    return JURI::root($pathonly, $path);
}

function acym_currentUserId()
{
    $acymy = JFactory::getUser();

    return $acymy->id;
}

function acym_currentUserName($userid = null)
{
    if (!empty($userid)) {
        $special = JFactory::getUser($userid);

        return $special->name;
    }

    $acymy = JFactory::getUser();

    return $acymy->name;
}

function acym_currentUserEmail($userid = null)
{
    if (!empty($userid)) {
        $special = JFactory::getUser($userid);

        return $special->email;
    }

    $acymy = JFactory::getUser();

    return $acymy->email;
}

function acym_loadLanguageFile($extension = 'joomla', $basePath = JPATH_SITE, $lang = null, $reload = false, $default = true)
{
    $acylanguage = JFactory::getLanguage();

    $acylanguage->load($extension, $basePath, $lang, $reload, $default);
}

function acym_getGlobal($type)
{
    $variables = [
        'db' => ['acydb', 'getDBO'],
        'doc' => ['acyDocument', 'getDocument'],
        'app' => ['acyapp', 'getApplication'],
    ];

    global ${$variables[$type][0]};
    if (${$variables[$type][0]} === null) {
        $method = $variables[$type][1];
        ${$variables[$type][0]} = JFactory::$method();
    }

    return ${$variables[$type][0]};
}

function acym_escapeDB($value)
{
    $acydb = acym_getGlobal('db');

    return $acydb->quote($value);
}

function acym_query($query)
{
    $acydb = acym_getGlobal('db');
    $acydb->setQuery($query);

    $method = ACYM_J40 ? 'execute' : 'query';

    $result = $acydb->$method();
    if (!$result) {
        return false;
    }

    return $acydb->getAffectedRows();
}

function acym_loadObjectList($query, $key = '', $offset = null, $limit = null)
{
    $acydb = acym_getGlobal('db');

    $acydb->setQuery($query, $offset, $limit);

    return $acydb->loadObjectList($key);
}

function acym_loadObject($query)
{
    acym_addLimit($query);

    $acydb = acym_getGlobal('db');
    $acydb->setQuery($query);

    return $acydb->loadObject();
}

function acym_loadResult($query)
{
    $acydb = acym_getGlobal('db');

    $acydb->setQuery($query);

    return $acydb->loadResult();
}

function acym_loadResultArray($query)
{
    if (is_string($query)) {
        $acydb = acym_getGlobal('db');
        $acydb->setQuery($query);
    } else {
        $acydb = $query;
    }

    if (ACYM_J30) {
        return $acydb->loadColumn();
    }

    return $acydb->loadResultArray();
}

function acym_getEscaped($value, $extra = false)
{
    $acydb = acym_getGlobal('db');

    if (ACYM_J30) {
        return $acydb->escape($value, $extra);
    }

    return $acydb->getEscaped($value, $extra);
}

function acym_getDBError()
{
    $acydb = acym_getGlobal('db');

    return $acydb->getErrorMsg();
}

function acym_insertObject($table, $element)
{
    $acydb = acym_getGlobal('db');
    $acydb->insertObject($table, $element);

    return $acydb->insertid();
}

function acym_updateObject($table, $element, $pkey)
{
    $acydb = acym_getGlobal('db');

    return $acydb->updateObject($table, $element, $pkey, true);
}

function acym_getPrefix()
{
    $acydb = acym_getGlobal('db');

    return $acydb->getPrefix();
}

function acym_getTableList()
{
    $acydb = acym_getGlobal('db');

    return $acydb->getTableList();
}

function acym_completeLink($link, $popup = false, $redirect = false, $forceNoPopup = false)
{
    if (($popup || acym_isNoTemplate()) && $forceNoPopup == false) {
        $link .= '&'.acym_noTemplate();
    }

    return acym_route('index.php?option='.ACYM_COMPONENT.'&ctrl='.$link, !$redirect);
}

function acym_noTemplate()
{
    return 'tmpl=component';
}

function acym_isNoTemplate()
{
    return acym_getVar('cmd', 'tmpl') == 'component';
}

function acym_setNoTemplate($status = true)
{
    if ($status) {
        acym_setVar('tmpl', 'component');
    } else {
        acym_setVar('tmpl', '');
    }
}

function acym_cmsLoaded()
{
    defined('_JEXEC') || die('Restricted access');
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
    echo '<input type="hidden" name="option" value="'.ACYM_COMPONENT.'"/>';
    echo '<input type="hidden" name="nextstep" value=""/>';
    echo '<input type="hidden" name="task" value="'.$task.'"/>';
    echo '<input type="hidden" name="ctrl" value="'.(empty($currentCtrl) ? acym_getVar('cmd', 'ctrl', '') : $currentCtrl).'"/>';
    if ($token) {
        echo acym_formToken();
    }
    echo '<button type="submit" class="is-hidden" id="formSubmit"></button>';
}

/**
 * @param string $message The message to display
 * @param string $type    The type (success, error, warning, info, message, notice)
 */
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
        $acyapp = acym_getGlobal('app');

        // Display the translated text
        if (ACYM_J30) {
            $type = str_replace(
                ['info', 'success'],
                ['notice', 'message'],
                $type
            );
        }

        $acyapp->enqueueMessage($message, $type);
    }

    return true;
}

function acym_displayMessages()
{
    $acyapp = acym_getGlobal('app');
    $messages = $acyapp->getMessageQueue(true);
    if (empty($messages)) {
        return;
    }

    $sorted = [];
    foreach ($messages as $oneMessage) {
        $sorted[$oneMessage['type']][] = $oneMessage['message'];
    }

    foreach ($sorted as $type => $message) {
        acym_display($message, $type);
    }
}

function acym_prepareAjaxURL($url)
{
    return htmlspecialchars_decode(acym_completeLink($url, true));
}

function acym_isDebug()
{
    return defined('JDEBUG') && JDEBUG;
}

function acym_getLanguagePath($basePath = ACYM_BASE, $language = null)
{
    if (ACYM_J40) {
        return LanguageHelper::getLanguagePath(rtrim($basePath, DS), $language);
    } else {
        return JLanguage::getLanguagePath(rtrim($basePath, DS), $language);
    }
}

function acym_askLog($current = true, $message = 'ACYM_NOTALLOWED', $type = 'error')
{
    //If the user is not logged in, we just redirect him to the login page....
    $url = 'index.php?option=com_users&view=login';
    if ($current) {
        $url .= '&return='.base64_encode(acym_currentURL());
    }
    acym_redirect($url, $message, $type);
}

function acym_frontendLink($link, $complete = true)
{
    if ($complete) {
        $link = 'index.php?option='.ACYM_COMPONENT.'&ctrl='.$link;
    }

    if (ACYM_J39 && strpos($link, 'ctrl=cron') === false && strpos($link, 'ctrl=fronturl') === false) {
        return JRoute::link('site', $link, true, 0, true);
    }

    $mainurl = acym_mainURL($link);

    return $mainurl.$link;
}

function acym_prepareQuery($query)
{
    $query = str_replace('#__', acym_getPrefix(), $query);

    return $query;
}

function acym_date($input = 'now', $format = null, $useTz = true, $gregorian = false)
{
    if ($useTz === true) {
        $tz = false;
    } else {
        $tz = null;
    }

    if (!$format || (strpos($format, 'ACYM_DATE_FORMAT') !== false && acym_translation($format) == $format)) {
        $format = 'ACYM_DATE_FORMAT_LC1';
    }
    $format = acym_translation($format);

    return JHTML::_('date', $input, $format, $tz, $gregorian);
}

function acym_getMenu()
{
    global $Itemid;

    $jsite = JFactory::getApplication('site');
    $menus = $jsite->getMenu();
    $menu = $menus->getActive();

    if (empty($menu) && !empty($Itemid)) {
        $menus->setActive($Itemid);
        $menu = $menus->getItem($Itemid);
    }

    return $menu;
}

function acym_getDefaultConfigValues()
{
    $allPref = [];

    $allPref['from_name'] = acym_getCMSConfig('fromname');
    $allPref['from_email'] = acym_getCMSConfig('mailfrom');
    $allPref['bounce_email'] = acym_getCMSConfig('mailfrom');
    $allPref['sendmail_path'] = acym_getCMSConfig('sendmail');
    $allPref['smtp_port'] = acym_getCMSConfig('smtpport');
    $allPref['smtp_secured'] = acym_getCMSConfig('smtpsecure');
    $allPref['smtp_auth'] = acym_getCMSConfig('smtpauth');
    $allPref['smtp_username'] = acym_getCMSConfig('smtpuser');
    $allPref['smtp_password'] = acym_getCMSConfig('smtppass');
    $allPref['mailer_method'] = acym_getCMSConfig('mailer');
    $smtpinfos = explode(':', acym_getCMSConfig('smtphost'));
    $allPref['smtp_host'] = $smtpinfos[0];
    if (isset($smtpinfos[1])) {
        $allPref['smtp_port'] = $smtpinfos[1];
    }
    if (!in_array($allPref['smtp_secured'], ['tls', 'ssl'])) {
        $allPref['smtp_secured'] = '';
    }
    $allPref['cron_savepath'] = ACYM_LOGS_FOLDER.'report{year}_{month}.log';

    return $allPref;
}

function acym_addBreadcrumb($title, $link = '')
{
    $acyapp = acym_getGlobal('app');
    $pathway = $acyapp->getPathway();
    $pathway->addItem($title, $link);
}

function acym_setPageTitle($title)
{
    if (empty($title)) {
        $title = acym_getCMSConfig('sitename');
    } elseif (acym_getCMSConfig('sitename_pagetitles', 0) == 1) {
        $title = acym_translation_sprintf('ACYM_JPAGETITLE', acym_getCMSConfig('sitename'), $title);
    } elseif (acym_getCMSConfig('sitename_pagetitles', 0) == 2) {
        $title = acym_translation_sprintf('ACYM_JPAGETITLE', $title, acym_getCMSConfig('sitename'));
    }
    $document = JFactory::getDocument();
    $document->setTitle($title);
}

function acym_cmsModal($isIframe, $content, $buttonText, $isButton, $modalTitle, $identifier = null, $width = '800', $height = '400')
{
    JHtml::_('jquery.framework');
    JHtml::_('script', 'system/modal-fields.js', ['version' => 'auto', 'relative' => true]);

    if (empty($identifier)) {
        $identifier = 'identifier_'.rand(1000, 9000);
    }

    $html = '<a class="'.($isButton ? 'btn ' : '').'hasTooltip" data-toggle="modal" role="button" href="#'.$identifier.'" id="button_'.$identifier.'">'.acym_translation($buttonText).'</a>';
    $html .= JHtml::_(
        'bootstrap.renderModal',
        $identifier,
        [
            'title' => $modalTitle,
            'url' => $content,
            'height' => $height.'px',
            'width' => $width.'px',
            'bodyHeight' => '70',
            'modalWidth' => '80',
            'footer' => '<a role="button" class="btn" data-dismiss="modal" aria-hidden="true">'.acym_translation('JLIB_HTML_BEHAVIOR_CLOSE').'</a>',
        ]
    );

    return $html;
}

function acym_CMSArticleTitle($id)
{
    return acym_loadResult('SELECT title FROM #__content WHERE id = '.intval($id));
}

function acym_getArticleURL($id, $popup, $text, $titleModal = '')
{
    if (empty($id)) return '';

    // Make sure the Joomla link generator class is loaded
    if (!class_exists('ContentHelperRoute')) {
        $contentHelper = JPATH_SITE.DS.'components'.DS.'com_content'.DS.'helpers'.DS.'route.php';
        if (!file_exists($contentHelper)) return '';
        require_once $contentHelper;
    }

    $query = 'SELECT article.id, article.alias, article.catid, cat.alias AS catalias, article.language
        FROM #__content AS article 
        LEFT JOIN #__categories AS cat ON cat.id = article.catid 
        WHERE article.id = '.intval($id);
    $article = acym_loadObject($query);

    $category = $article->catid.(empty($article->catalias) ? '' : ':'.$article->catalias);
    $articleid = $article->id.(empty($article->alias) ? '' : ':'.$article->alias);

    $url = ContentHelperRoute::getArticleRoute($articleid, $category, $article->language);

    if ($popup == 1) {
        $url .= (strpos($url, '?') ? '&' : '?').acym_noTemplate();
        $url = acym_cmsModal(true, acym_route($url), $text, false, $titleModal);
    } else {
        $url = '<a title="'.acym_translation($text, true).'" href="'.acym_escape(acym_route($url)).'" target="_blank">'.acym_translation($text).'</a>';
    }

    return $url;
}

function acym_articleSelectionPage()
{
    return 'index.php?option=com_content&amp;view=articles&amp;layout=modal&amp;tmpl=component&amp;object=content&amp;'.acym_getFormToken();
}

function acym_getPageOverride(&$ctrl, $view, $forceBackend = false)
{
    if ($forceBackend || acym_isAdmin()) {
        $app = JFactory::getApplication('administrator');
        $folder = JPATH_ADMINISTRATOR;
    } else {
        $app = JFactory::getApplication('site');
        $folder = JPATH_SITE;
        if (!file_exists(ACYM_VIEW_FRONT.$ctrl)) $ctrl = 'front'.$ctrl;
    }

    return $folder.DS.'templates'.DS.$app->getTemplate().DS.'html'.DS.ACYM_COMPONENT.DS.$ctrl.DS.$view.'.php';
}

function acym_isLeftMenuNecessary()
{
    return (!ACYM_J40 && acym_isAdmin() && !acym_isNoTemplate());
}

function acym_getLeftMenu($name)
{
    $pluginClass = acym_get('class.plugin');
    $nbPluginNotUptodate = $pluginClass->getNotUptoDatePlugins();

    $addOnsTitle = empty($nbPluginNotUptodate) ? 'ACYM_ADD_ONS' : acym_translation_sprintf('ACYM_ADD_ONS_X', $nbPluginNotUptodate);
    $isCollapsed = empty($_COOKIE['menuJoomla']) ? '' : $_COOKIE['menuJoomla'];

    $menus = [
        'dashboard' => ['title' => 'ACYM_DASHBOARD', 'class-i' => 'acymicon-dashboard', 'span-class' => ''],
        'users' => ['title' => 'ACYM_USERS', 'class-i' => 'acymicon-group', 'span-class' => ''],
        'fields' => ['title' => 'ACYM_CUSTOM_FIELDS', 'class-i' => 'acymicon-text_fields', 'span-class' => ''],
        'lists' => ['title' => 'ACYM_LISTS', 'class-i' => 'acymicon-address-book-o', 'span-class' => 'acym__joomla__left-menu__fa'],
        'campaigns' => ['title' => 'ACYM_EMAILS', 'class-i' => 'acymicon-email', 'span-class' => ''],
        'mails' => ['title' => 'ACYM_TEMPLATES', 'class-i' => 'acymicon-pencil', 'span-class' => 'acym__joomla__left-menu__fa'],
        'automation' => ['title' => 'ACYM_AUTOMATION', 'class-i' => 'acymicon-cog', 'span-class' => 'acym__joomla__left-menu__fa'],
        'queue' => ['title' => 'ACYM_QUEUE', 'class-i' => 'acymicon-hourglass-2', 'span-class' => 'acym__joomla__left-menu__fa'],
        'stats' => ['title' => 'ACYM_STATISTICS', 'class-i' => 'acymicon-bar-chart', 'span-class' => 'acym__joomla__left-menu__fa'],
        'bounces' => ['title' => 'ACYM_BOUNCE_HANDLING', 'class-i' => 'acymicon-random', 'span-class' => 'acym__joomla__left-menu__fa'],
        'plugins' => ['title' => $addOnsTitle, 'class-i' => 'acymicon-plug', 'span-class' => 'acym__joomla__left-menu__fa'],
        'forms' => ['title' => 'ACYM_SUBSCRIPTION_FORMS', 'class-i' => 'acymicon-edit', 'span-class' => 'acym__joomla__left-menu__fa'],
        'configuration' => ['title' => 'ACYM_CONFIGURATION', 'class-i' => 'acymicon-settings', 'span-class' => ''],
    ];

    $leftMenu = '<div id="acym__joomla__left-menu--show"><i class="acym-logo"></i><i id="acym__joomla__left-menu--burger" class="acymicon-menu"></i></div>
                    <div id="acym__joomla__left-menu" class="'.$isCollapsed.'">
                        <i class="acymicon-close" id="acym__joomla__left-menu--close"></i>';
    foreach ($menus as $oneMenu => $menuOption) {
        $class = $name == $oneMenu ? 'acym__joomla__left-menu--current' : '';
        $leftMenu .= '<a href="'.acym_completeLink($oneMenu).'" class="'.$class.'"><i class="'.$menuOption['class-i'].'"></i><span class="'.$menuOption['span-class'].'">'.acym_translation($menuOption['title']).'</span></a>';
    }

    $leftMenu .= '<a href="#" id="acym__joomla__left-menu--toggle"><i class="acymicon-keyboard_arrow_left"></i><span>'.acym_translation('ACYM_COLLAPSE').'</span></a>';

    $leftMenu .= '</div>';

    return $leftMenu;
}

function acym_cmsCleanHtml($html)
{
    return $html;
}

function acym_isPluginActive($plugin, $family = 'system')
{
    $plugin = JPluginHelper::getPlugin($family, $plugin);

    return !empty($plugin);
}

function acym_getPluginsPath($file, $dir)
{
    return rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS;
}

function acym_includeHeaders()
{
}

function acym_getPluginPath($plugin)
{
    return ACYM_ADDONS_FOLDER_PATH.$plugin.DS.'plugin.php';
}

function acym_prepareFrontViewDisplay($ctrl)
{
}

function acym_isExtensionActive($extension)
{
    return JComponentHelper::isEnabled($extension, true);
}

function acym_loadCmsScripts()
{
    $toggleController = acym_isAdmin() ? 'toggle' : 'fronttoggle';
    acym_addScript(
        true,
        'var ACYM_TOGGLE_URL = "'.(acym_isAdmin() ? '' : acym_rootURI()).'index.php?option='.ACYM_COMPONENT.'&'.acym_noTemplate().'&ctrl='.$toggleController.'&'.acym_getFormToken().'";
        var ACYM_AJAX_URL = "'.(acym_isAdmin() ? '' : acym_rootURI()).'index.php?option='.ACYM_COMPONENT.'&'.acym_noTemplate().'&'.acym_getFormToken().'";
        var ACYM_JOOMLA_MEDIA_IMAGE = "'.ACYM_LIVE.'";
        var ACYM_IS_ADMIN = '.(acym_isAdmin() ? 'true' : 'false').';'
    );

    JHtml::_('jquery.framework');
    acym_addScript(false, 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js');
}

function acym_menuOnly($link)
{
    $menu = JFactory::getApplication('site')->getMenu()->getActive();
    if (empty($menu) || $menu->link !== $link) {
        acym_redirect(acym_rootURI(), 'ACYM_UNAUTHORIZED_ACCESS', 'error');
    }
}

function acym_getAlias($name)
{
    return JFilterOutput::stringURLSafe($name);
}

function acym_replaceGroupTags($uploadFolder)
{
    if (strpos($uploadFolder, '{groupname}') === false) return $uploadFolder;

    // Get user groups
    $groups = acym_getGroupsByUser(acym_currentUserId(), false);
    acym_arrayToInteger($groups);

    // Get group name
    $group = acym_loadResult('SELECT title FROM #__usergroups WHERE id = '.intval(max($groups)));

    $uploadFolder = str_replace(
        '{groupname}',
        strtolower(
            str_replace(
                '-',
                '_',
                acym_getAlias($group)
            )
        ),
        $uploadFolder
    );

    return $uploadFolder;
}

function acym_getCmsUserEdit($userId)
{
    return 'index.php?option=com_users&task=user.edit&id='.intval($userId);
}

function acym_disableCmsEditor()
{
}

function acym_cmsPermission()
{
    $user = JFactory::getUser();
    if (!$user->authorise('core.admin', ACYM_COMPONENT)) return '';

    $url = 'index.php?option=com_config&view=component&component='.ACYM_COMPONENT.'&return='.urlencode(base64_encode((string)JUri::getInstance()));

    return '
		<div class="cell medium-6 grid-x">
			<label class="cell medium-6 small-9">'.acym_translation('ACYM_JOOMLA_PERMISSIONS').'</label>
			<div class="cell auto">
				<a class="button button-secondary" href="'.$url.'">'.acym_translation('JTOOLBAR_OPTIONS').'</a>
			</div>
		</div>';
}

function acym_languageOption($emailLanguage, $name)
{
    $languages = acym_getLanguages(true, true);
    if (count($languages) < 2) return '';

    $default = new stdClass();
    $default->language = '';
    $default->name = acym_translation('ACYM_DEFAULT');
    array_unshift($languages, $default);

    return acym_select(
        $languages,
        $name,
        $emailLanguage,
        'class="acym__select"',
        'language',
        'name'
    );
}

function acym_coreAddons()
{
    return [
        (object)[
            'title' => acym_translation('ACYM_ARTICLE'),
            'folder_name' => 'article',
            'version' => '{__VERSION__}',
            'active' => '1',
            'category' => 'Content management',
            'level' => 'starter',
            'uptodate' => '1',
            'features' => '["content"]',
            'description' => '- Insert Joomla articles in your emails<br/>- Insert the latest articles of a category in an automatic email',
            'latest_version' => '{__VERSION__}',
            'core' => '1',
        ],
    ];
}

function acym_getCmsUserLanguage($userId = null)
{
    if ($userId === null) $userId = acym_currentUserId();
    if (empty($userId)) return '';

    $user = JFactory::getUser($userId);

    return $user->getParam('language', $user->getParam('admin_language', ''));
}

function acym_getAllPages()
{
    $menuType = acym_loadResultArray('SELECT menutype FROM #__menu_types');
    if (empty($menuType)) $menuType = [];
    $menuItems = acym_loadObjectList('SELECT id, title FROM #__menu WHERE menutype IN ("'.implode('","', $menuType).'")');
    $pages = [];
    foreach ($menuItems as $item) {
        $pages[$item->id] = $item->title;
    }

    return $pages;
}

function acym_checkVersion($ajax = false)
{
    // Get any error correctly
    ob_start();
    $config = acym_config();
    $url = ACYM_UPDATEURL.'loadUserInformation';

    $paramsForLicenseCheck = [
        'component' => 'acymailing', // Know which product to look at
        'level' => strtolower($config->get('level', 'starter')), // Know which version to look at
        'domain' => rtrim(ACYM_LIVE, '/'), // Tell the user if the automatic features are available for the current installation
        'version' => $config->get('version'), // Tell the user if a newer version is available
        'cms' => ACYM_CMS, // We may delay some new Acy versions depending on the CMS
        'cmsv' => ACYM_CMSV, // Acy isn't available for some versions
    ];


    foreach ($paramsForLicenseCheck as $param => $value) {
        $url .= '&'.$param.'='.urlencode($value);
    }

    $userInformation = acym_fileGetContent($url, 30);
    $warnings = ob_get_clean();
    $result = (!empty($warnings) && acym_isDebug()) ? $warnings : '';

    // Could not load the user information
    if (empty($userInformation) || $userInformation === false) {
        if ($ajax) {
            echo json_encode(['content' => '<br/><span style="color:#C10000;">'.acym_translation('ACYM_ERROR_LOAD_FROM_ACYBA').'</span><br/>'.$result]);
            exit;
        } else {
            return '';
        }
    }

    $decodedInformation = json_decode($userInformation, true);

    $newConfig = new stdClass();

    $newConfig->latestversion = $decodedInformation['latestversion'];
    $newConfig->expirationdate = $decodedInformation['expiration'];
    $newConfig->lastlicensecheck = time();
    $config->save($newConfig);

    //check for plugins
    acym_checkPluginsVersion();

    return $newConfig->lastlicensecheck;
}

global $acymCmsUserVars;
$acymCmsUserVars = new stdClass();
$acymCmsUserVars->table = '#__users';
$acymCmsUserVars->name = 'name';
$acymCmsUserVars->username = 'username';
$acymCmsUserVars->id = 'id';
$acymCmsUserVars->email = 'email';
$acymCmsUserVars->registered = 'registerDate';
$acymCmsUserVars->blocked = 'block';
