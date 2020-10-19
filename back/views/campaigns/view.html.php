<?php

namespace AcyMailing\Views;

use AcyMailing\Libraries\acymView;

/**
 * Class CampaignsViewLists
 */
class CampaignsViewCampaigns extends acymView
{
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
        $this->tabs = [
            'campaigns' => 'ACYM_CAMPAIGNS',
        ];


        if (acym_isAllowed('mails')) {
            $this->tabs['welcome'] = 'ACYM_WELCOME_EMAILS';
            $this->tabs['unsubscribe'] = 'ACYM_UNSUBSCRIBE_EMAILS';
        }

        acym_trigger('onAcymDisplayCampaignListingSpecificTabs', [&$this->tabs]);
    }

    public function addSegmentStep($displaySegmentTab)
    {
        if ($displaySegmentTab) {
            $this->steps = [
                'chooseTemplate' => 'ACYM_CHOOSE_TEMPLATE',
                'editEmail' => 'ACYM_EDIT_EMAIL',
                'recipients' => 'ACYM_RECIPIENTS',
                'segment' => 'ACYM_SEGMENT',
                'sendSettings' => 'ACYM_SEND_SETTINGS',
                'tests' => 'ACYM_TEST',
                'summary' => 'ACYM_SUMMARY',
            ];
        }
    }
}
