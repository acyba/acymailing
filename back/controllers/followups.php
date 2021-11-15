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
        $id = acym_getVar('int', 'id', 0);
        if (empty($id)) {
            acym_sendAjaxResponse(acym_translation('ACYM_FOLLOWUP_NOT_FOUND'), [], false);
        }

        $emailIds = $this->currentClass->getEmailsByIds($id);
        if (empty($emailIds)) {
            acym_sendAjaxResponse(acym_translation('ACYM_NO_EMAIL_FOR_FOLLOWUP'), [], false);
        }

        $mailClass = new MailClass();
        $mailStatClass = new MailStatClass();
        $campaignClass = new CampaignClass();
        $urlClickClass = new UrlClickClass();
        $data = [];
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

            $data[] = $thisMailStats;
        }

        acym_sendAjaxResponse('', $data);
    }

    public function addQueueAjax()
    {
        acym_checkToken();

        $emailId = acym_getVar('int', 'emailId', 0);
        if (empty($emailId)) {
            acym_sendAjaxResponse(acym_translation('ACYM_EMAIL_NOT_FOUND'), [], false);
        }

        $followupClass = new FollowupClass();
        $queued = $followupClass->queueForSubscribers($emailId);

        if ($queued === false) {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR_ADD_QUEUE'), [], false);
        }

        acym_sendAjaxResponse(acym_translationSprintf('ACYM_EMAILS_ADDED_QUEUE', $queued));
    }
}
