<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriptionAutomationTriggers.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriptionAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriptionAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriptionAutomationActions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriptionFollowup.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriptionInsertion.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'SubscriptionMailboxAction.php';

class plgAcymSubscription extends acymPlugin
{
    use SubscriptionAutomationTriggers;
    use SubscriptionAutomationConditions;
    use SubscriptionAutomationFilters;
    use SubscriptionAutomationActions;
    use SubscriptionFollowup;
    use SubscriptionInsertion;
    use SubscriptionMailboxAction;

    public function __construct()
    {
        parent::__construct();

        global $acymCmsUserVars;
        $this->cmsUserVars = $acymCmsUserVars;

        $this->pluginDescription->name = acym_translation('ACYM_SUBSCRIPTION');
    }
}
