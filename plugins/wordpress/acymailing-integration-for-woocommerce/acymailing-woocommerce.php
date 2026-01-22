<?php
/*
 * Plugin Name: AcyMailing integration for WooCommerce
 * Description: Adds editor and automation options for WooCommerce in AcyMailing
 * Author: AcyMailing Newsletter Team
 * Author URI: https://www.acymailing.com
 * License: GPLv3
 * Version: 7.3
 * Requires Plugins: acymailing, woocommerce
*/

use AcyMailing\Classes\PluginClass;

if (!defined('ABSPATH')) {
    exit;
}

class AcyMailingIntegrationForWooCommerce
{
    const INTEGRATION_PLUGIN_NAME = 'plgAcymWoocommerce';

    public function __construct()
    {
        register_deactivation_hook(__FILE__, [$this, 'disable']);
        register_uninstall_hook(__FILE__, [self::class, 'uninstall']);
        add_action('acym_load_installed_integrations', [$this, 'register'], 10, 2);
        add_action('before_woocommerce_init', [$this, 'hposCompatibility']);
        add_action('block_categories_all', [$this, 'registerBlock'], 10, 2);
        add_action('woocommerce_blocks_loaded', [$this, 'initBlock'], 10);
    }

    public function disable(): void
    {
        if (!self::loadAcyMailingLibrary()) {
            return;
        }

        $pluginClass = new PluginClass();
        $pluginClass->disable(self::getIntegrationName());
    }

    public static function uninstall(): void
    {
        if (!self::loadAcyMailingLibrary()) {
            return;
        }

        $pluginClass = new PluginClass();
        $pluginClass->deleteByFolderName(self::getIntegrationName());
    }

    public function register(array &$integrations, string $acyVersion): void
    {
        if (version_compare($acyVersion, '10.4.0', '>=')) {
            $integrations[] = [
                'path' => __DIR__,
                'className' => self::INTEGRATION_PLUGIN_NAME,
            ];
        }
    }

    public function hposCompatibility()
    {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        }
    }

    public function registerBlock($categories)
    {
        return array_merge(
            $categories,
            [
                [
                    'slug' => 'acymailing-wc-block',
                    'title' => acym_translation('ACYM_WOO_CHECKOUT_BLOCK'),
                ],
            ]
        );
    }

    public function initBlock()
    {
        require_once __DIR__.DIRECTORY_SEPARATOR.'acymailing-wc-block-blocks-integration.php';

        add_action(
            'woocommerce_blocks_cart_block_registration',
            function ($integration_registry) {
                $integration_registry->register(new AcymailingWcBlock());
            }
        );
        add_action(
            'woocommerce_blocks_checkout_block_registration',
            function ($integration_registry) {
                $integration_registry->register(new AcymailingWcBlock());
            }
        );
    }

    private static function getIntegrationName(): string
    {
        return strtolower(substr(self::INTEGRATION_PLUGIN_NAME, 7));
    }

    private static function loadAcyMailingLibrary(): bool
    {
        $ds = DIRECTORY_SEPARATOR;
        $vendorFolder = dirname(__DIR__).$ds.'acymailing'.$ds.'vendor';
        $helperFile = dirname(__DIR__).$ds.'acymailing'.$ds.'back'.$ds.'Core'.$ds.'init.php';

        return file_exists($vendorFolder) && include_once $helperFile;
    }
}

new AcyMailingIntegrationForWooCommerce();
