<?php
/*
 * Plugin Name: AcyMailing integration for Business Directory
 * Description: Adds editor options for Business Directory in AcyMailing
 * Author: AcyMailing Newsletter Team
 * Author URI: https://www.acymailing.com
 * License: GPLv3
 * Version: 2.4
 * Requires Plugins: acymailing, business-directory-plugin
*/

use AcyMailing\Classes\PluginClass;

if (!defined('ABSPATH')) {
    exit;
}

class AcyMailingIntegrationForBusinessDirectory
{
    const INTEGRATION_PLUGIN_NAME = 'plgAcymBusinessdirectory';

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
        if (version_compare($acyVersion, '7.9.7', '>=')) {
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
        $helperFile = dirname(__DIR__).$ds.'acymailing'.$ds.'back'.$ds.'helpers'.$ds.'helper.php';

        return file_exists($vendorFolder) && include_once $helperFile;
    }
}

new AcyMailingIntegrationForBusinessDirectory();
