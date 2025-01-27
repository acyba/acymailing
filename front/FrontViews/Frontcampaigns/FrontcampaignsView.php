<?php

namespace AcyMailing\FrontViews\Frontcampaigns;

use AcyMailing\Core\AcymView;

class FrontcampaignsView extends AcymView
{
    public function __construct()
    {
        $this->steps = [
            'chooseTemplate' => 'ACYM_CHOOSE_TEMPLATE',
            'editEmail' => 'ACYM_EDIT_EMAIL',
            'recipients' => 'ACYM_RECIPIENTS',
            'sendSettings' => 'ACYM_SEND_SETTINGS',
            'summary' => 'ACYM_SUMMARY',
        ];

        $this->tabs = [
            'campaigns' => 'ACYM_CAMPAIGNS',
            'welcome' => 'ACYM_WELCOME_EMAILS',
            'unsubscribe' => 'ACYM_UNSUBSCRIBE_EMAILS',
        ];

        parent::__construct();
    }

    public function addSegmentStep($data)
    {

    }
}
