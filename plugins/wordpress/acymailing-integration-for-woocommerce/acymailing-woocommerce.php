<?php
/*
Plugin Name: AcyMailing integration for WooCommerce
Description: Adds editor and automation options for WooCommerce in AcyMailing
Author: AcyMailing Newsletter Team
Author URI: https://www.acymailing.com
License: GPLv3
Version: 5.3
*/

use AcyMailing\Classes\PluginClass;

register_deactivation_hook(__FILE__, 'acym_integration_woocommerce_disable');
function acym_integration_woocommerce_disable()
{
    $vendorFolder = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'vendor';
    $helperFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
    if (!is_plugin_active('acymailing/index.php') || !file_exists($vendorFolder) || !include_once $helperFile) return;

    $pluginClass = new PluginClass();
    $pluginClass->disable('woocommerce');
}

register_uninstall_hook(__FILE__, 'acym_integration_woocommerce_uninstall');
function acym_integration_woocommerce_uninstall()
{
    $vendorFolder = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'vendor';
    $helperFile = dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing'.DIRECTORY_SEPARATOR.'back'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
    if (!is_plugin_active('acymailing/index.php') || !file_exists($vendorFolder) || !include_once $helperFile) return;

    $pluginClass = new PluginClass();
    $pluginClass->deleteByFolderName('woocommerce');
}

add_action('acym_load_installed_integrations', 'acym_integration_woocommerce', 10, 2);
function acym_integration_woocommerce(&$integrations, $acyVersion)
{
    if (version_compare($acyVersion, '7.5.11', '>=')) {
        $integrations[] = [
            'path' => __DIR__,
            'className' => 'plgAcymWoocommerce',
        ];
    }
}

add_action('before_woocommerce_init', 'acym_register_woocommerce_hpos_compatibility');
function acym_register_woocommerce_hpos_compatibility()
{
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
}
