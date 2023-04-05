<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'PayplansAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'PayplansAutomationFilters.php';

class plgAcymPayplans extends acymPlugin
{
    use PayplansAutomationConditions;
    use PayplansAutomationFilters;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_payplans');
    }
}
