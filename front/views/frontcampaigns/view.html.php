<?php

namespace AcyMailing\FrontViews;

use AcyMailing\Libraries\acymView;

class FrontcampaignsViewFrontcampaigns extends acymView
{
    public function __construct()
    {
        global $Itemid;
        $this->Itemid = $Itemid;

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
