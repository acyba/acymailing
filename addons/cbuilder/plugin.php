<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'CbuilderAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'CbuilderAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'CbuilderInsertion.php';

class plgAcymCbuilder extends AcymPlugin
{
    use CbuilderAutomationConditions;
    use CbuilderAutomationFilters;
    use CbuilderInsertion;

    var $sendervalues = [];

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'Community Builder',
            'description' => '- Insert CB user fields in your emails<br>- Filter your users based on CB fields',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/community-builder',
            'category' => 'User management',
            'level' => 'starter',
        ];
        $this->installed = acym_isExtensionActive('com_comprofiler');

        $this->pluginDescription->name = 'Community Builder';
    }
}
