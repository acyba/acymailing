<?php

namespace AcyMailing\Controllers\Campaigns;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailClass;


trait AutoCampaigns
{
    public function campaigns_auto(): void
    {
        if (!acym_level(ACYM_ENTERPRISE)) {
            $this->campaigns();
        }
    }

    private function getIsPendingGenerated(array &$data): void
    {
        $campaignClass = new CampaignClass();
        $campaingsGenerated = $campaignClass->getAllCampaignsGeneratedWaiting();
        $data['generatedPending'] = !empty($campaingsGenerated);
    }

    private function getAutoCampaignsFrequency(array &$data): void
    {
        foreach ($data['allCampaigns'] as $key => $campaign) {
            if (empty($campaign->sending_params)) continue;
            $textToDisplay = new \stdClass();
            $textToDisplay->triggers = $campaign->sending_params;
            acym_trigger('onAcymDeclareSummary_triggers', [&$textToDisplay], 'plgAcymTime');

            if (empty($textToDisplay->triggers['trigger_type']) || empty($textToDisplay->triggers[$textToDisplay->triggers['trigger_type']])) {
                $data['allCampaigns'][$key]->sending_params['trigger_text'] = acym_translation('ACYM_ERROR_WHILE_RECOVERING_TRIGGERS');
                $message = acym_translationSprintf('ACYM_ERROR_WHILE_RECOVERING_TRIGGERS_NOTIF_X', $campaign->id);
                $message .= ' <a id="acym__queue__configure-cron" href="'.acym_completeLink('campaigns&task=campaigns_auto').'">'.acym_translation(
                        'ACYM_GOTO_CAMPAIGNS_AUTO'
                    ).'</a>';
                $message .= '<p class="acym__do__not__remindme" title="auto_campaigns_triggers_reminder">'.acym_translation('ACYM_DO_NOT_REMIND_ME').'</p>';

                $notification = [
                    'name' => 'auto_campaigns_triggers_reminder',
                    'removable' => 1,
                ];
                acym_enqueueMessage($message, 'warning', true, [$notification]);
            } else {
                $data['allCampaigns'][$key]->sending_params['trigger_text'] = $textToDisplay->triggers[$textToDisplay->triggers['trigger_type']];
            }
        }
    }

    public function summaryGenerated(): void
    {
        $campaignId = acym_getVar('int', 'campaignId', 0);
        $mailClass = new MailClass();

        acym_setVar('layout', 'summary_generated');

        $generatedCampaign = $this->loadCampaignMail($campaignId);

        if (empty($generatedCampaign)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_LOAD_CAMPAIGN'), 'error');
            $this->listing();

            return;
        }

        $campaign = $generatedCampaign['campaign'];
        $mail = $generatedCampaign['mail'];

        $lists = $mailClass->getAllListsByMailId($mail->id);

        if (empty($lists)) {
            $this->listing();

            return;
        }

        $parentCampaign = $this->loadCampaignMail((int)$campaign->parent_id);
        if (empty($parentCampaign)) {
            $parentCampaign = [
                'campaign' => false,
                'mail' => false,
            ];
        }

        //if campaign wait for confirmation
        $campaign->waiting_confirmation = false;
        if ($campaign->draft && $campaign->active) {
            $campaign->waiting_confirmation = true;
        }
        //if campaign canceled
        $campaign->canceled = false;
        if (!$campaign->draft && !$campaign->active) {
            $campaign->canceled = true;
        }

        $data = [
            'campaign' => $campaign,
            'mailId' => $campaign->mail_id,
            'mail' => $mail,
            'lists' => $lists,
            'parent_campaign' => $parentCampaign['campaign'],
            'parent_mail' => $parentCampaign['mail'],
            'mailClass' => $mailClass,
        ];

        $this->prepareMultilingual($data, false);
        $this->prepareAllMailsForMultilingual($data);

        $this->breadcrumb[acym_escape($mail->name)] = acym_completeLink('campaigns&task=summaryGenerated&campaignId='.$campaign->id);
        parent::display($data);
    }

    protected function changeStatusGeneratedCampaign(string $statusToApply = 'disable'): void
    {
        $campaignId = acym_getVar('int', 'campaignId', 0);
        $campaignClass = new CampaignClass();

        $campaign = $this->loadCampaignMail($campaignId);

        if (empty($campaign)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_LOAD_CAMPAIGN'), 'error');
            $this->listing();

            return;
        }

        $campaign = $campaign['campaign'];

        if ('disable' === $statusToApply) {
            $campaign->sent = 0;
            $campaign->active = 0;
            $campaign->draft = 0;
            $successMsg = acym_translation('ACYM_CAMPAIGN_HAS_BEEN_DISABLED');
        } else {
            $campaign->active = 1;
            $campaign->draft = 1;
            $successMsg = acym_translation('ACYM_CAMPAIGN_HAS_BEEN_ENABLED');
        }

        $savedCampaignId = $campaignClass->save($campaign);
        if (!empty($savedCampaignId)) {
            acym_enqueueMessage($successMsg);
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
        }

        if ('enable' === $statusToApply) {
            acym_setVar('campaignId', $campaignId);
            $this->summaryGenerated();
        } else {
            acym_setVar('campaigns_status', 'generated');
            $this->listing();
        }
    }

    public function disableGeneratedCampaign(): void
    {
        $this->changeStatusGeneratedCampaign();
    }

    public function enableGeneratedCampaign(): void
    {
        $this->changeStatusGeneratedCampaign('enable');
    }

    private function loadCampaignMail(int $campaignId): array
    {
        if (empty($campaignId)) {
            return [];
        }

        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();

        $campaign = $campaignClass->getOneById($campaignId);
        if (empty($campaign)) {
            return [];
        }

        $mail = $mailClass->getOneById($campaign->mail_id);
        if (empty($mail)) {
            return [];
        }

        if (empty($mail->from_name)) $mail->from_name = $this->config->get('from_name');
        if (empty($mail->from_email)) $mail->from_email = $this->config->get('from_email');
        if (empty($mail->reply_to_name)) $mail->reply_to_name = $this->config->get('replyto_name');
        if (empty($mail->reply_to_email)) $mail->reply_to_email = $this->config->get('replyto_email');

        return [
            'campaign' => $campaign,
            'mail' => $mail,
        ];
    }
}
