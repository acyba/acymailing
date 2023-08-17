<?php
/*
Plugin Name: AcyMailing integration for Business Directory
Description: Adds editor options for Business Directory in AcyMailing
Author: AcyMailing Newsletter Team
Author URI: https://www.acymailing.com
License: GPLv3
Version: 1.4
*/

use AcyMailing\Classes\PluginClass;

register_deactivation_hook(__FILE__, 'acym_integration_businessdirectory_disable');
function acym_integration_businessdirectory_disable()
{
    $vendorFolder = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'vendor';
    $helperFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
    if (!is_plugin_active('acymailing/index.php') || !file_exists($vendorFolder) || !include_once $helperFile) return;

    $pluginClass = new PluginClass();
    $pluginClass->disable('businessdirectory');
}

register_uninstall_hook(__FILE__, 'acym_integration_businessdirectory_uninstall');
function acym_integration_businessdirectory_uninstall()
{
    $vendorFolder = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'vendor';
    $helperFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
    if (!is_plugin_active('acymailing/index.php') || !file_exists($vendorFolder) || !include_once $helperFile) return;

    $pluginClass = new PluginClass();
    $pluginClass->deleteByFolderName('businessdirectory');
}

add_action('acym_load_installed_integrations', 'acym_integration_businessdirectory', 10, 2);
function acym_integration_businessdirectory(&$integrations, $acyVersion)
{
    if (version_compare($acyVersion, '7.9.7', '>=')) {
        $integrations[] = [
            'path' => __DIR__,
            'className' => 'plgAcymBusinessdirectory',
        ];
    }
}
