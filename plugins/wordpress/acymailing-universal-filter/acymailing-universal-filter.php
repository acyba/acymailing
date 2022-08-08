<?php
/*
Plugin Name: AcyMailing universal filter
Description: Adds two new filters in the AcyMailing automations to let you make your own database integration
Author: AcyMailing Newsletter Team
Author URI: https://www.acymailing.com
License: GPLv3
Version: 1.6
*/

use AcyMailing\Classes\PluginClass;

register_deactivation_hook(__FILE__, 'acym_integration_universalfilter_disable');
function acym_integration_universalfilter_disable()
{
    $vendorFolder = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'vendor';
    $helperFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
    if (!is_plugin_active('acymailing/index.php') || !file_exists($vendorFolder) || !include_once $helperFile) return;

    $pluginClass = new PluginClass();
    $pluginClass->disable('universalfilter');
}

register_uninstall_hook(__FILE__, 'acym_integration_universalfilter_uninstall');
function acym_integration_universalfilter_uninstall()
{
    $vendorFolder = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'vendor';
    $helperFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
    if (!is_plugin_active('acymailing/index.php') || !file_exists($vendorFolder) || !include_once $helperFile) return;

    $pluginClass = new PluginClass();
    $pluginClass->deleteByFolderName('universalfilter');
}

add_action('acym_load_installed_integrations', 'acym_integration_universalfilter', 10, 2);
function acym_integration_universalfilter(&$integrations, $acyVersion)
{
    if (version_compare($acyVersion, '7.5.11', '>=')) {
        $integrations[] = [
            'path' => __DIR__,
            'className' => 'plgAcymUniversalfilter',
        ];
    }
}
