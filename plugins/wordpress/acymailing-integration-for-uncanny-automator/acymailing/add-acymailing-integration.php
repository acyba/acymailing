<?php

use Uncanny_Automator\Recipe;

class Add_Acymailing_Integration
{
    use Recipe\Integrations;

    const INTEGRATION_CODE = 'acymailing';

    public function __construct()
    {
        $this->setup();
    }

    protected function setup()
    {
        $this->set_integration(self::INTEGRATION_CODE);
        $this->set_name('AcyMailing');
        $this->set_icon('acymailing.svg');
        $this->set_icon_path(__DIR__.'/img/');
        $this->set_plugin_file_path(dirname(__DIR__).DIRECTORY_SEPARATOR.'acymailing-integration-for-uncanny-automator.php');
        $this->set_external_integration(true);
    }

    public function plugin_active(): bool
    {
        return is_plugin_active('acymailing/index.php');
    }
}
