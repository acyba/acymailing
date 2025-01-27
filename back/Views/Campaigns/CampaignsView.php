<?php

namespace AcyMailing\Views\Campaigns;

use AcyMailing\Core\AcymView;

/**
 * Class CampaignsViewLists
 */
class CampaignsView extends AcymView
{
    public $followupSteps;

    public function __construct()
    {
        parent::__construct();

        $this->steps = [
            'chooseTemplate' => 'ACYM_CHOOSE_TEMPLATE',
            'editEmail' => 'ACYM_EDIT_EMAIL',
            'recipients' => 'ACYM_RECIPIENTS',
            'sendSettings' => 'ACYM_SEND_SETTINGS',
            'tests' => 'ACYM_TEST',
            'summary' => 'ACYM_SUMMARY',
        ];

        $this->followupSteps = [
            'followupTrigger' => 'ACYM_TRIGGER',
            'followupCondition' => 'ACYM_CONDITIONS',
            'followupEmail' => 'ACYM_EMAILS',
            'followupSummary' => 'ACYM_SUMMARY',
        ];

        $this->tabs = [
            'campaigns' => 'ACYM_CAMPAIGNS',
        ];

        $this->tabs['followup'] = 'ACYM_FOLLOW_UP';

        if (acym_isAllowed('mails')) {
            $this->tabs['welcome'] = 'ACYM_WELCOME_EMAILS';
            $this->tabs['unsubscribe'] = 'ACYM_UNSUBSCRIBE_EMAILS';
        }

        acym_trigger('onAcymDisplayCampaignListingSpecificTabs', [&$this->tabs]);

        $this->tabs['mailbox_action'] = 'ACYM_MAILBOX_ACTION_CAMPAIGN';
    }

    public function addSegmentStep($displaySegmentTab)
    {
        if (!$displaySegmentTab) {
            return;
        }
        $this->steps = [
            'chooseTemplate' => 'ACYM_CHOOSE_TEMPLATE',
            'editEmail' => 'ACYM_EDIT_EMAIL',
            'recipients' => 'ACYM_RECIPIENTS',
        ];
        if (acym_isAllowed('segments')) {
            $this->steps['segment'] = 'ACYM_SEGMENT';
        }
        $this->steps['sendSettings'] = 'ACYM_SEND_SETTINGS';
        $this->steps['tests'] = 'ACYM_TEST';
        $this->steps['summary'] = 'ACYM_SUMMARY';
    }
}
