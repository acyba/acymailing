<?php

/**
 * Plugin Name: AcyMailing integration for Uncanny Automator
 * Description: Add AcyMailing triggers and actions in Uncanny Automator
 * Author: AcyMailing Newsletter Team
 * Author URI: https://www.acymailing.com
 * License: GPLv3
 * Version: 2.0
 * Text Domain: acymailing-integration-for-uncanny-automator
 * Domain Path: /language
 * Requires Plugins: acymailing, uncanny-automator
 */

class AcyMailingUncannyAutomatorIntegration
{
    const INTEGRATION_CODE = 'acymailing';

    public function __construct()
    {
        add_action('automator_configuration_complete', [$this, 'add_this_integration']);
    }

    /**
     * Add integration and all related files to Automator so that it shows up under Triggers / Actions
     *
     * @return bool|null
     * @throws \Uncanny_Automator\Automator_Exception
     */
    public function add_this_integration(): bool
    {
        return automator_add_integration_directory(self::INTEGRATION_CODE, __DIR__.'/acymailing');
    }
}

new AcyMailingUncannyAutomatorIntegration();
