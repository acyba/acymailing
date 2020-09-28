<?php

define('ACYM_NAME', 'AcyMailing');
define('ACYM_DBPREFIX', '#__acym_');
define('ACYM_LANGUAGE_FILE', 'com_acym');
define('ACYM_ACYMAILLING_WEBSITE', 'https://www.acymailing.com/');
define('ACYM_UPDATEMEURL', 'https://www.acyba.com/index.php?option=com_updateme&nocache='.time().'&ctrl=');
define('ACYM_SPAMURL', ACYM_UPDATEMEURL.'spamsystem&task=');
define('ACYM_HELPURL', ACYM_UPDATEMEURL.'doc&component='.ACYM_NAME.'&page=');
define('ACYM_REDIRECT', ACYM_UPDATEMEURL.'redirect&page=');
define('ACYM_UPDATEURL', ACYM_UPDATEMEURL.'update&task=');
define('ACYM_DOCUMENTATION', ACYM_UPDATEMEURL.'doc&task=getLink');
define('ACYM_COMPONENT_NAME_API', 'acymailing');
if (!defined('DS')) define('DS', DIRECTORY_SEPARATOR);

include_once rtrim(dirname(__DIR__), DS).DS.'libraries'.DS.strtolower('{__CMS__}.php');

define('ACYM_LIVE', rtrim(acym_rootURI(), '/').'/');
define('ACYM_HELPER_GLOBAL', ACYM_HELPER.'global'.DS);

//Avoid date warnings...
if (is_callable('date_default_timezone_set')) {
    date_default_timezone_set(@date_default_timezone_get());
}

include_once ACYM_HELPER_GLOBAL.'addon.php';
include_once ACYM_HELPER_GLOBAL.'chart.php';
include_once ACYM_HELPER_GLOBAL.'curl.php';
include_once ACYM_HELPER_GLOBAL.'date.php';
include_once ACYM_HELPER_GLOBAL.'email.php';
include_once ACYM_HELPER_GLOBAL.'field.php';
include_once ACYM_HELPER_GLOBAL.'file.php';
include_once ACYM_HELPER_GLOBAL.'global.php';
include_once ACYM_HELPER_GLOBAL.'language.php';
include_once ACYM_HELPER_GLOBAL.'mail.php';
include_once ACYM_HELPER_GLOBAL.'modal.php';
include_once ACYM_HELPER_GLOBAL.'module.php';
include_once ACYM_HELPER_GLOBAL.'query.php';
include_once ACYM_HELPER_GLOBAL.'security.php';
include_once ACYM_HELPER_GLOBAL.'url.php';
include_once ACYM_HELPER_GLOBAL.'version.php';
include_once ACYM_HELPER_GLOBAL.'view.php';


// Load libraries
include_once ACYM_LIBRARY.'object.php';
include_once ACYM_LIBRARY.'class.php';
include_once ACYM_LIBRARY.'parameter.php';
include_once ACYM_LIBRARY.'controller.php';
include_once ACYM_LIBRARY.'view.php';
include_once ACYM_LIBRARY.'plugin.php';
include_once ACYM_LIBRARY.'legacyPlugin.php';

// Load the AcyMailing translations
acym_loadLanguage();
