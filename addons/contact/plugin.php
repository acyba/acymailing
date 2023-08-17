<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'ContactAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'ContactAutomationFilters.php';

class plgAcymContact extends acymPlugin
{
    use ContactAutomationConditions;
    use ContactAutomationFilters;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'Contacts',
            'description' => '- Filter your AcyMailing users based on their contact category',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/joomla-contacts',
            'category' => 'User management',
            'level' => 'enterprise',
        ];
        $this->installed = acym_isExtensionActive('com_contact');
    }
}
