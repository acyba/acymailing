<?php

namespace AcyMailing\Controllers\Dashboard;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Controllers\StatsController;

trait Listing
{
    public function listing()
    {
        acym_setVar('layout', 'listing');

        if ($this->migration()) return;
        if ($this->walkthrough()) return;

        $campaignClass = new CampaignClass();
        $mailStatClass = new MailStatClass();
        $statsController = new StatsController();

        $data = [];
        $data['page_title'] = true;
        $data['mail_filter'] = '';
        $data['stats_export'] = '';
        $data['selectedMailid'] = '';
        $data['show_date_filters'] = false;
        $data['campaignsScheduled'] = $campaignClass->getCampaignForDashboard();
        $data['sentMails'] = $mailStatClass->getAllMailsForStats();

        $statsController->prepareOpenTimeChart($data);
        $statsController->preparecharts($data);
        $statsController->prepareDefaultRoundCharts($data);
        $statsController->prepareDefaultLineChart($data);
        $statsController->prepareDefaultDevicesChart($data);
        $statsController->prepareDefaultBrowsersChart($data);

        parent::display($data);
    }
}
