<?php

namespace AcyMailing\Controllers\Campaigns;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailClass;


trait AutoCampaigns
{
    public function campaigns_auto()
    {
        if (!acym_level(ACYM_ENTERPRISE)) {
            $this->campaigns();
        }
    }

    private function getIsPendingGenerated(&$data)
    {
        $campaignClass = new CampaignClass();
        $campaingsGenerated = $campaignClass->getAllCampaignsGeneratedWaiting();
        $data['generatedPending'] = !empty($campaingsGenerated);
    }

    private function getAutoCampaignsFrequency(&$data)
    {
        foreach ($data['allCampaigns'] as $key => $campaign) {
            if (empty($campaign->sending_params)) continue;
            $textToDisplay = new \stdClass();
            $textToDisplay->triggers = $campaign->sending_params;
            acym_trigger('onAcymDeclareSummary_triggers', [&$textToDisplay], 'plgAcymTime');

            if (empty($textToDisplay->triggers['trigger_type']) || empty($textToDisplay->triggers[$textToDisplay->triggers['trigger_type']])) {
                $data['allCampaigns'][$key]->sending_params['trigger_text'] = acym_translation('ACYM_ERROR_WHILE_RECOVERING_TRIGGERS');
            } else {
                $data['allCampaigns'][$key]->sending_params['trigger_text'] = $textToDisplay->triggers[$textToDisplay->triggers['trigger_type']];
            }
        }
    }

    private function getCountStatusFilterCampaignsAuto($allCampaigns, &$allCountStatus)
    {
        $allCountStatus->all = 0;
        $allCountStatus->generated = 0;

        if (!empty($allCampaigns)) $allCountStatus->all = count($allCampaigns);

        $campaignClass = new CampaignClass();
        $generatedCampaigns = $campaignClass->getAllCampaignsGenerated();
        if (!empty($generatedCampaigns)) {
            $allCountStatus->generated = count($generatedCampaigns);
        }
    }

    public function summaryGenerated()
    {
        $campaignId = acym_getVar('int', 'campaignId', 0);
        $mailClass = new MailClass();

        acym_setVar('layout', 'summary_generated');

        $generatedCampaign = $this->_loadCampaignMail($campaignId);

        if (!$generatedCampaign) {
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

        $parentCampaign = $this->_loadCampaignMail($campaign->parent_id);
        if (!$parentCampaign) {
            $parentCampaign = ['campaign' => false, 'mail' => false];
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

    protected function changeStatusGeneratedCampaign($statusToApply = 'disable')
    {
        $campaignId = acym_getVar('int', 'campaignId', 0);
        $campaignClass = new CampaignClass();

        $campaign = $this->_loadCampaignMail($campaignId);

        if (!$campaign) {
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

        if ($campaignClass->save($campaign)) {
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

    public function disableGeneratedCampaign()
    {
        $this->changeStatusGeneratedCampaign();
    }

    public function enableGeneratedCampaign()
    {
        $this->changeStatusGeneratedCampaign('enable');
    }

    private function _loadCampaignMail($campaignId)
    {
        if (empty($campaignId)) return false;

        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();

        $campaign = $campaignClass->getOneById($campaignId);
        if (empty($campaign)) return false;

        $mail = $mailClass->getOneById($campaign->mail_id);
        if (empty($mail)) return false;


        if (empty($mail->from_name)) $mail->from_name = $this->config->get('from_name');
        if (empty($mail->from_email)) $mail->from_email = $this->config->get('from_email');
        if (empty($mail->reply_to_name)) $mail->reply_to_name = $this->config->get('replyto_name');
        if (empty($mail->reply_to_email)) $mail->reply_to_email = $this->config->get('replyto_email');

        return ['campaign' => $campaign, 'mail' => $mail];
    }
}
