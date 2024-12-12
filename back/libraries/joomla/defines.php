<?php

use Joomla\CMS\Uri\Uri;

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
define('ACYM_NEW_FEATURES_SPLASHSCREEN', ACYM_BACK.'partial'.DS.'update'.DS.'new_features.php');
define('ACYM_NEW_FEATURES_SPLASHSCREEN_JSON', ACYM_BACK.'partial'.DS.'update'.DS.'changelogs_splashscreen.json');
define('ACYM_VIEW_FRONT', ACYM_FRONT.'views'.DS);
define('ACYM_HELPER', ACYM_BACK.'helpers'.DS);
define('ACYM_CLASS', ACYM_BACK.'classes'.DS);
define('ACYM_LIBRARY', ACYM_BACK.'libraries'.DS);
define('ACYM_TYPE', ACYM_BACK.'types'.DS);
define('ACYM_CONTROLLER', ACYM_BACK.'controllers'.DS);
define('ACYM_CONTROLLER_FRONT', ACYM_FRONT.'controllers'.DS);
define('ACYM_MEDIA', ACYM_ROOT.'media'.DS.ACYM_COMPONENT.DS);
define('ACYM_LANGUAGE', ACYM_ROOT.'language'.DS);
define('ACYM_LIBRARIES', ACYM_FRONT.'libraries'.DS);

define('ACYM_MEDIA_RELATIVE', 'media/'.ACYM_COMPONENT.'/');
define('ACYM_MEDIA_URL', acym_rootURI().ACYM_MEDIA_RELATIVE);
define('ACYM_IMAGES', ACYM_MEDIA_URL.'images/');
define('ACYM_CSS', ACYM_MEDIA_URL.'css/');
define('ACYM_JS', ACYM_MEDIA_URL.'js/');
define('ACYM_TEMPLATE', ACYM_MEDIA.'templates'.DS);
define('ACYM_TEMPLATE_URL', ACYM_MEDIA_URL.'templates/');
define('ACYM_TMP_URL', ACYM_MEDIA_URL.'tmp/');
define('ACYM_TEMPLATE_THUMBNAILS', ACYM_IMAGES.'thumbnails/');
define('ACYM_CORE_DYNAMICS_URL', acym_rootURI().'administrator/components/'.ACYM_COMPONENT.'/dynamics/');
define('ACYM_DYNAMICS_URL', ACYM_CORE_DYNAMICS_URL);
define('ACYM_ADDONS_FOLDER_PATH', ACYM_BACK.'dynamics'.DS);

define('ACYM_MEDIA_FOLDER', 'media'.DS.ACYM_COMPONENT.DS);
define('ACYM_UPLOAD_FOLDER', ACYM_MEDIA_FOLDER.'upload'.DS);
define('ACYM_UPLOAD_FOLDER_THUMBNAIL', ACYM_MEDIA.'images'.DS.'thumbnails'.DS);
define('ACYM_UPLOADS_URL', ACYM_MEDIA_URL.'upload/');

define('ACYM_CUSTOM_PLUGIN_LAYOUT', ACYM_MEDIA.'plugins'.DS);
define('ACYM_LOGS_FOLDER', ACYM_MEDIA_FOLDER.'logs'.DS);
define('ACYM_TMP_FOLDER', ACYM_MEDIA.'tmp'.DS);

//Avoid the issue with 3.0.0_beta
$jversion = preg_replace('#[^0-9\.]#i', '', JVERSION);
define('ACYM_CMSV', $jversion);
define('ACYM_J30', version_compare($jversion, '3.0.0', '>='));
define('ACYM_J37', version_compare($jversion, '3.7.0', '>='));
define('ACYM_J39', version_compare($jversion, '3.9.0', '>='));
define('ACYM_J40', version_compare($jversion, '4.0.0', '>='));
define('ACYM_J50', version_compare($jversion, '5.0.0', '>='));

define('ACYM_ALLOWRAW', defined('JREQUEST_ALLOWRAW') ? JREQUEST_ALLOWRAW : 2);
define('ACYM_ALLOWHTML', defined('JREQUEST_ALLOWHTML') ? JREQUEST_ALLOWHTML : 4);
define('ACYM_ADMIN_GROUP', 8);
define('ACYM_PLUGINS_URL', ACYM_BACK);
define('ACYM_AVAILABLE_PLUGINS', json_encode([]));

function acym_rootURI($pathonly = false, $path = null)
{
    $url = Uri::root($pathonly, $path);
    $mysitesGuruPos = strpos($url, '/plugins/system/bfnetwork');
    if ($mysitesGuruPos !== false) {
        $url = substr($url, 0, $mysitesGuruPos);
    }

    return $url;
}
