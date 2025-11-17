<?php

use AcyMailing\Core\AcymPlugin;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__.DIRECTORY_SEPARATOR.'EasyDigitalDownloadsInsertion.php';

class plgAcymEasydigitaldownloads extends AcymPlugin
{
    use EasyDigitalDownloadsInsertion;

    private $isOldDatabaseVersion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('easy-digital-downloads/easy-digital-downloads.php');
        $this->pluginDescription->name = 'Easy Digital Downloads';
        $this->pluginDescription->icon = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/icon.png';
        $this->pluginDescription->category = 'Content management';
        $this->pluginDescription->description = '- Insert digital downloads and generate coupons in your emails';
        $this->isOldDatabaseVersion = false;
        if (is_admin()) {
            if (!function_exists('get_plugin_data')) {
                require_once(ABSPATH.'wp-admin/includes/plugin.php');
            }
            if (acym_isExtensionActive('easy-digital-downloads/easy-digital-downloads.php')) {
                $plugin_data = get_plugin_data(ABSPATH.'wp-content/plugins/easy-digital-downloads/easy-digital-downloads.php');
                $this->isOldDatabaseVersion = version_compare($plugin_data['Version'], '3.0.0', '<');
            }
        }
        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'price' => ['ACYM_PRICE', true],
                'shortdesc' => ['ACYM_SHORT_DESCRIPTION', true],
                'desc' => ['ACYM_DESCRIPTION', false],
                'cats' => ['ACYM_CATEGORIES', false],
                'note' => ['ACYM_DETAILS', false],
            ];

            $this->initCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->replaceOptions, $this->elementOptions),
                ],
            ];
        } else {
            $this->settings = [
                'not_installed' => '1',
            ];
        }
    }

    public function getPossibleIntegrations(): ?object
    {
        return $this->pluginDescription;
    }
}
