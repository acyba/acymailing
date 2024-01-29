<?php

namespace AcyMailing\FrontControllers\Api;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\UrlClickClass;
use AcyMailing\Classes\UserStatClass;

trait Statistics
{
    public function getCampaignStatistics()
    {
        $campaignId = acym_getVar('int', 'campaignId', 0);

        $campaignClass = new CampaignClass();
        $campaign = $campaignClass->getOneById($campaignId);
        if (empty($campaign)) {
            $this->sendJsonResponse(['message' => 'Campaign not found'], 404);
        }

        $mailstatClass = new MailStatClass();
        $mailClass = new MailClass();
        $variations = $mailClass->getVersionsById($campaign->mail_id, true);

        $statistics = [];
        foreach ($variations as $oneMail) {
            $statistics[$oneMail->id] = $mailstatClass->getOneRowByMailId($oneMail->id);
        }

        $this->sendJsonResponse(array_values($statistics));
    }

    public function getCampaignStatisticsDetailed()
    {
        $campaignId = acym_getVar('int', 'campaignId', 0);

        $campaignClass = new CampaignClass();
        $campaign = $campaignClass->getOneById($campaignId);
        if (empty($campaign)) {
            $this->sendJsonResponse(['message' => 'Campaign not found'], 404);
        }

        $userstatClass = new UserStatClass();
        $mailClass = new MailClass();
        $variations = $mailClass->getVersionsById($campaign->mail_id, true);

        $statistics = [];
        foreach ($variations as $oneMail) {
            $statistics[$oneMail->id] = $userstatClass->getDetailedStatistics(
                [
                    'mail_id' => $oneMail->id,
                    'offset' => acym_getVar('int', 'offset', 0),
                    'limit' => acym_getVar('int', 'limit', 100),
                ]
            );
        }

        $this->sendJsonResponse(array_values($statistics));
    }

    public function getCampaignStatisticsClicks()
    {
        $campaignId = acym_getVar('int', 'campaignId', 0);

        $campaignClass = new CampaignClass();
        $campaign = $campaignClass->getOneById($campaignId);
        if (empty($campaign)) {
            $this->sendJsonResponse(['message' => 'Campaign not found'], 404);
        }

        $urlclickClass = new UrlClickClass();
        $mailClass = new MailClass();
        $variations = $mailClass->getVersionsById($campaign->mail_id, true);

        $statistics = [];
        foreach ($variations as $oneMail) {
            $statistics[$oneMail->id] = $urlclickClass->getClicksByMailId($oneMail->id);
        }

        $this->sendJsonResponse(array_merge_recursive(...$statistics));
    }

    public function getCampaignStatisticsLinks()
    {
        $campaignId = acym_getVar('int', 'campaignId', 0);

        $campaignClass = new CampaignClass();
        $campaign = $campaignClass->getOneById($campaignId);
        if (empty($campaign)) {
            $this->sendJsonResponse(['message' => 'Campaign not found'], 404);
        }

        $urlclickClass = new UrlClickClass();
        $mailClass = new MailClass();
        $variationIds = array_keys($mailClass->getVersionsById($campaign->mail_id, true));

        if (empty($variationIds)) {
            $this->sendJsonResponse(['message' => 'No variations found'], 404);
        }

        $statistics = $urlclickClass->getUrlsFromMailsWithDetails(
            [
                'mail_ids' => $variationIds,
                'offset' => acym_getVar('int', 'offset', 0),
                'detailedStatsPerPage' => acym_getVar('int', 'limit', 100),
            ]
        );

        $this->sendJsonResponse(array_values($statistics['links_details']));
    }
}
