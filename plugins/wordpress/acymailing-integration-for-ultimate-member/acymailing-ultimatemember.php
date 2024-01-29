<?php
/*
Plugin Name: AcyMailing integration for Ultimate Member
Description: Add AcyMailing lists on your Ultimate Member registration form
Author: AcyMailing Newsletter Team
Author URI: https://www.acymailing.com
License: GPLv3
Version: 2.5
*/

use AcyMailing\Classes\PluginClass;
use AcyMailing\Helpers\RegacyHelper;

register_deactivation_hook(__FILE__, 'acym_integration_ultimatemember_disable');
function acym_integration_ultimatemember_disable()
{
    $vendorFolder = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'vendor';
    $helperFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
    if (!is_plugin_active('acymailing/index.php') || !file_exists($vendorFolder) || !include_once $helperFile) return;

    $pluginClass = new PluginClass();
    $pluginClass->disable('ultimatemember');
}

register_uninstall_hook(__FILE__, 'acym_integration_ultimatemember_uninstall');
function acym_integration_ultimatemember_uninstall()
{
    $vendorFolder = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'vendor';
    $helperFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
    if (!is_plugin_active('acymailing/index.php') || !file_exists($vendorFolder) || !include_once $helperFile) return;

    $pluginClass = new PluginClass();
    $pluginClass->deleteByFolderName('ultimatemember');
}

add_action('acym_load_installed_integrations', 'acym_integration_ultimatemember', 10, 2);
function acym_integration_ultimatemember(&$integrations, $acyVersion)
{
    if (version_compare($acyVersion, '7.5.11', '>=')) {
        $integrations[] = [
            'path' => __DIR__,
            'className' => 'plgAcymUltimatemember',
        ];
    }
}

add_action('um_after_register_fields', 'addRegistrationFields');
function addRegistrationFields()
{
    $vendorFolder = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'vendor';
    $helperFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
    if (!is_plugin_active('acymailing/index.php') || !file_exists($vendorFolder) || !include_once $helperFile) return;

    $config = acym_config();
    $displayOnExternalPlugin = $config->get('regacy_use_ultimate_member', 0) == 1;

    if (!$config->get('regacy', 0) || !$displayOnExternalPlugin) return;

    $regacyHelper = new RegacyHelper();
    if (!$regacyHelper->prepareLists(['formatted' => true])) return;

    ?>
	<div class="acym__regacy">
		<label class="acym__regacy__label"><?php echo $regacyHelper->label; ?></label>
		<div class="acym__regacy__values"><?php echo $regacyHelper->lists; ?></div>
	</div>
    <?php
}
