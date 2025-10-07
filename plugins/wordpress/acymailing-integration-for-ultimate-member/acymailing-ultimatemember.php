<?php
/*
 * Plugin Name: AcyMailing integration for Ultimate Member
 * Description: Add AcyMailing lists on your Ultimate Member registration form
 * Author: AcyMailing Newsletter Team
 * Author URI: https://www.acymailing.com
 * License: GPLv3
 * Version: 3.8
 * Requires Plugins: acymailing, ultimate-member
*/

use AcyMailing\Classes\PluginClass;
use AcyMailing\Helpers\RegacyHelper;

if (!defined('ABSPATH')) {
    exit;
}

class AcyMailingIntegrationForUltimateMember
{
    const INTEGRATION_PLUGIN_NAME = 'plgAcymUltimatemember';

    public function __construct()
    {
        register_deactivation_hook(__FILE__, [$this, 'disable']);
        register_uninstall_hook(__FILE__, [self::class, 'uninstall']);
        add_action('acym_load_installed_integrations', [$this, 'register'], 10, 2);
        add_action('um_after_register_fields', [$this, 'addRegistrationFields']);
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
        if (version_compare($acyVersion, '10.1.0', '>=')) {
            $integrations[] = [
                'path' => __DIR__,
                'className' => self::INTEGRATION_PLUGIN_NAME,
            ];
        }
    }

    public function addRegistrationFields()
    {
        if (!self::loadAcyMailingLibrary()) {
            return;
        }

        $config = acym_config();
        $displayOnExternalPlugin = $config->get('regacy_use_ultimate_member', 0) == 1;

        if (!$config->get('regacy', 0) || !$displayOnExternalPlugin) {
            return;
        }

        $regacyHelper = new RegacyHelper();
        if (!$regacyHelper->prepareLists(['formatted' => true])) {
            return;
        }

        ?>
		<div class="acym__regacy">
			<label class="acym__regacy__label"><?php echo esc_html($regacyHelper->label); ?></label>
			<div class="acym__regacy__values">
                <?php
                echo wp_kses(
                    $regacyHelper->listsHtml,
                    [
                        'table' => ['class' => [], 'style' => []],
                        'tr' => ['style' => []],
                        'td' => ['style' => []],
                        'input' => [
                            'type' => [],
                            'name' => [],
                            'id' => [],
                            'value' => [],
                            'class' => [],
                            'checked' => [],
                        ],
                        'label' => ['for' => [], 'class' => []],
                    ]
                );
                ?>
			</div>
		</div>
        <?php
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

new AcyMailingIntegrationForUltimateMember();
