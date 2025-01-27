<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'PayplansAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'PayplansAutomationFilters.php';

class plgAcymPayplans extends AcymPlugin
{
    use PayplansAutomationConditions;
    use PayplansAutomationFilters;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'PayPlans',
            'description' => '- Filter your users based on their subscription plans',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/payplans',
            'category' => 'E-commerce solutions',
            'level' => 'enterprise',
        ];
        $this->installed = acym_isExtensionActive('com_payplans');
    }
}
