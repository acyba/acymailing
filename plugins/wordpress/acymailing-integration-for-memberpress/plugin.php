<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'MemberpressAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'MemberpressAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'MemberpressAutomationTriggers.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'MemberpressInsertion.php';

class plgAcymMemberpress extends acymPlugin
{
    use MemberpressAutomationConditions;
    use MemberpressAutomationFilters;
    use MemberpressAutomationTriggers;
    use MemberpressInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('memberpress/memberpress.php');
        $this->pluginDescription->name = 'MemberPress';
        $this->pluginDescription->category = 'User management';
        $this->pluginDescription->description = '- Insert user information in your emails<br />- Filter users based on their membership subscription';
    }
}
