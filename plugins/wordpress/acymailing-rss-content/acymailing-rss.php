<?php
/*
Plugin Name: AcyMailing RSS content
Description: Allows you to automatically insert RSS content in your emails
Author: AcyMailing Newsletter Team
Author URI: https://www.acymailing.com
License: GPLv3
Version: 1.3
*/

use AcyMailing\Classes\PluginClass;

register_deactivation_hook(__FILE__, 'acym_integration_rss_disable');
function acym_integration_rss_disable()
{
    $vendorFolder = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'vendor';
    $helperFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
    if (!is_plugin_active('acymailing/index.php') || !file_exists($vendorFolder) || !include_once $helperFile) return;

    $pluginClass = new PluginClass();
    $pluginClass->disable('rss');
}

register_uninstall_hook(__FILE__, 'acym_integration_rss_uninstall');
function acym_integration_rss_uninstall()
{
    $vendorFolder = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'vendor';
    $helperFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
    if (!is_plugin_active('acymailing/index.php') || !file_exists($vendorFolder) || !include_once $helperFile) return;

    $pluginClass = new PluginClass();
    $pluginClass->deleteByFolderName('rss');
}

add_action('acym_load_installed_integrations', 'acym_integration_rss', 10, 2);
function acym_integration_rss(&$integrations, $acyVersion)
{
    if (version_compare($acyVersion, '7.5.11', '>=')) {
        $integrations[] = [
            'path' => __DIR__,
            'className' => 'plgAcymRss',
        ];
    }
}
