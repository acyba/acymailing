<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\UrlClickClass;
use AcyMailing\Libraries\acymController;

class FollowupsController extends acymController
{
    public function getEmailsListing()
    {
        $results = [
            'type' => 'error',
            'message' => '',
            'data' => [],
        ];
        $id = acym_getVar('int', 'id', 0);
        if (empty($id)) {
            $results['message'] = acym_translation('ACYM_FOLLOWUP_NOT_FOUND');
            echo json_encode($results);
            exit;
        }

        $emailIds = $this->currentClass->getEmailsByIds($id);

        if (empty($emailIds)) {
            $results['message'] = acym_translation('ACYM_NO_EMAIL_FOR_FOLLOWUP');
            echo json_encode($results);
            exit;
        }

        $mailClass = new MailClass();
        $mailStatClass = new MailStatClass();
        $campaignClass = new CampaignClass();
        $urlClickClass = new UrlClickClass();
        foreach ($emailIds as $oneMailId) {
            $mail = $mailClass->getOneById($oneMailId);
            if (empty($mail)) continue;

            $thisMailStats = [
                'subject' => $mail->subject,
            ];

            $stats = $mailStatClass->getOneById($oneMailId);

            if (empty($stats)) {
                $thisMailStats['sent'] = '0';
                $thisMailStats['open'] = '-';
                $thisMailStats['click'] = '-';
                $thisMailStats['income'] = '-';
            } else {
                $stats->subscribers = $stats->sent;
                $campaignClass->getStatsCampaign($stats, $urlClickClass);

                $thisMailStats['sent'] = $stats->sent;
                $thisMailStats['open'] = $stats->open.'%';
                $thisMailStats['click'] = $stats->click.'%';
                $thisMailStats['income'] = round($stats->sale, 2).' '.$stats->currency;
            }

            $results['data'][] = $thisMailStats;
        }

        $results['type'] = 'success';
        echo json_encode($results);
        exit;
    }
}
