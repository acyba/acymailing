<?php

use AcyMailing\Core\AcymParameter;
use AcyMailing\Core\AcymPlugin;

define('ACYM_NAME', 'AcyMailing');
define('ACYM_DBPREFIX', '#__acym_');
define('ACYM_LANGUAGE_FILE', 'com_acym');
define('ACYM_ACYMAILING_WEBSITE', 'https://www.acymailing.com/');
define('ACYM_ACYCHECKER_WEBSITE', 'https://www.acychecker.com/');
define('ACYM_UPDATEME_API_URL', 'https://api.acymailing.com/');
define('ACYM_YOURCRONTASK_IP', '178.23.155.178');
define('ACYM_DOCUMENTATION', 'https://docs.acymailing.com/');
define('ACYM_COMPONENT_NAME_API', 'acymailing');
define('ACYM_PRODUCTION', '{__PRODUCTION__}');
define('ACYM_STARTER', 0);
define('ACYM_ESSENTIAL', 1);
define('ACYM_ENTERPRISE', 2);
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

include_once __DIR__.DS.strtolower('{__CMS__}.php');

define('ACYM_LIVE', rtrim(acym_rootURI(), '/').'/');
define('ACYM_HELPER_GLOBAL', ACYM_HELPER.'global'.DS);

define('ACYM_REGEX_SWITCHES', '#(switch_[0-9]*".*)(data\-switch=")(switch_.+id=")(switch_.+for=")(switch_)#Uis');
define('ACYM_SOCIAL_MEDIA', json_encode(['facebook', 'twitter', 'instagram', 'linkedin', 'pinterest', 'vimeo', 'wordpress', 'youtube', 'x', 'telegram']));
define('ACYM_ENTERPRISE_PRICE', '7.4');
define('ACYM_ESSENTIAL_PRICE', '2.4');

//Avoid date warnings...
if (is_callable('date_default_timezone_set')) {
    date_default_timezone_set(@date_default_timezone_get());
}

include_once ACYM_HELPER_GLOBAL.'acl.php';
include_once ACYM_HELPER_GLOBAL.'addon.php';
include_once ACYM_HELPER_GLOBAL.'ajax.php';
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
include_once ACYM_HELPER_GLOBAL.'multibyte.php';
include_once ACYM_HELPER_GLOBAL.'query.php';
include_once ACYM_HELPER_GLOBAL.'security.php';
include_once ACYM_HELPER_GLOBAL.'url.php';
include_once ACYM_HELPER_GLOBAL.'utf8.php';
include_once ACYM_HELPER_GLOBAL.'version.php';
include_once ACYM_HELPER_GLOBAL.'view.php';
include_once ACYM_HELPER_GLOBAL.'log.php';

// Load libraries
include_once ACYM_CORE.'AcymObject.php';
include_once ACYM_CORE.'AcymClass.php';
include_once ACYM_CORE.'AcymParameter.php';
include_once ACYM_CORE.'AcymController.php';
include_once ACYM_CORE.'AcymView.php';
include_once ACYM_CORE.'AcymPlugin.php';

// For old plugins compatibility
class_alias(AcymParameter::class, 'AcyMailing\\Libraries\\AcymParameter');
class_alias(AcymPlugin::class, 'AcyMailing\\Libraries\\AcymPlugin');

// Load the AcyMailing translations
acym_loadLanguage();
