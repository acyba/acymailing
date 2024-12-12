<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'ContactAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'ContactAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'ContactRegistration.php';

class plgAcymContact extends acymPlugin
{
    use ContactAutomationConditions;
    use ContactAutomationFilters;
    use ContactRegistration;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_contact');
    }
}
