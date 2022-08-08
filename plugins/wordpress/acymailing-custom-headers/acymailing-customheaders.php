<?php
/*
Plugin Name: AcyMailing custom headers
Description: Add custom email headers to the emails sent with AcyMailing.
Author: AcyMailing Newsletter Team
Author URI: https://www.acymailing.com
License: GPLv3
Version: 1.3
*/

use AcyMailing\Classes\PluginClass;

register_deactivation_hook(__FILE__, 'acym_custom_headers_disable');
function acym_custom_headers_disable()
{
    $vendorFolder = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'vendor';
    $helperFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
    if (!is_plugin_active('acymailing/index.php') || !file_exists($vendorFolder) || !include_once $helperFile) return;

    $pluginClass = new PluginClass();
    $pluginClass->disable('customheaders');
}

register_uninstall_hook(__FILE__, 'acym_custom_headers_uninstall');
function acym_custom_headers_uninstall()
{
    $vendorFolder = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'vendor';
    $helperFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
    if (!is_plugin_active('acymailing/index.php') || !file_exists($vendorFolder) || !include_once $helperFile) return;

    $pluginClass = new PluginClass();
    $pluginClass->deleteByFolderName('customheaders');
}

add_action('acym_load_installed_integrations', 'acym_custom_headers', 10, 2);
function acym_custom_headers(&$integrations, $acyVersion)
{
    if (version_compare($acyVersion, '7.5.11', '>=')) {
        $integrations[] = [
            'path' => __DIR__,
            'className' => 'plgAcymCustomheaders',
        ];
    }
}
