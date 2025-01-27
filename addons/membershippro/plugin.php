<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'MembershipProAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'MembershipProAutomationFilters.php';

class plgAcymMembershippro extends AcymPlugin
{
    use MembershipProAutomationConditions;
    use MembershipProAutomationFilters;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'Membership Pro',
            'description' => '- Filter your users based on their memberships',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/membership-pro',
            'category' => 'Subscription system',
            'level' => 'starter',
        ];
        $this->installed = acym_isExtensionActive('com_osmembership');

        $this->settings = [];
        if (!$this->installed) {
            $this->settings['not_installed'] = '1';
        }
    }
}
