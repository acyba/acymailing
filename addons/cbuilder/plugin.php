<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'CbuilderAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'CbuilderAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'CbuilderInsertion.php';

class plgAcymCbuilder extends acymPlugin
{
    use CbuilderAutomationConditions;
    use CbuilderAutomationFilters;
    use CbuilderInsertion;

    var $sendervalues = [];

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_comprofiler');

        $this->pluginDescription->name = 'Community Builder';
    }
}
