<?php
/*
 * Plugin Name: AcyMailing custom headers
 * Description: Add custom email headers to the emails sent with AcyMailing.
 * Author: AcyMailing Newsletter Team
 * Author URI: https://www.acymailing.com
 * License: GPLv3
 * Version: 3.4
 * Requires Plugins: acymailing
*/

use AcyMailing\Classes\PluginClass;

if (!defined('ABSPATH')) {
    exit;
}

class CustomHeadersForAcyMailing
{
    const INTEGRATION_PLUGIN_NAME = 'plgAcymCustomheaders';

    public function __construct()
    {
        register_deactivation_hook(__FILE__, [$this, 'disable']);
        register_uninstall_hook(__FILE__, [self::class, 'uninstall']);
        add_action('acym_load_installed_integrations', [$this, 'register'], 10, 2);
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
        if (version_compare($acyVersion, '10.0.0', '>=')) {
            $integrations[] = [
                'path' => __DIR__,
                'className' => self::INTEGRATION_PLUGIN_NAME,
            ];
        }
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

new CustomHeadersForAcyMailing();
