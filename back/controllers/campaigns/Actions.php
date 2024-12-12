<?php

namespace AcyMailing\Controllers\Campaigns;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\MailArchiveClass;
use AcyMailing\Classes\MailClass;
use stdClass;


trait Actions
{
    public function duplicate()
    {
        //We get the id of campaign checked
        $campaignsSelected = acym_getVar('int', 'elements_checked');

        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();
        $campaignId = 0;

        if (!acym_isAdmin()) {
            $campaignClass->onlyManageableCampaigns($campaignsSelected);
        }

        foreach ($campaignsSelected as $campaignSelected) {
            //we get the campaign
            $campaign = $campaignClass->getOneById($campaignSelected);

            //remove id and set to draft and not sent
            unset($campaign->id);
            unset($campaign->sending_date);
            $campaign->draft = 1;
            $campaign->sent = 0;
            $campaign->active = 0;
            if (!empty($campaign->sending_params['resendTarget'])) {
                unset($campaign->sending_params['resendTarget']);
            }

            //We get the mail to duplicate it
            $mail = $mailClass->getOneById($campaign->mail_id);
            $oldMailId = $mail->id;
            unset($mail->id);
            $mail->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
            $mail->name .= '_copy';
            $mail->creator_id = acym_currentUserId();
            $idNewMail = $mailClass->save($mail);

            if (isset($campaign->sending_params['abtest']) && !empty($campaign->sending_params['abtest']['B'])) {
                $mailVersion = $mailClass->getOneById($campaign->sending_params['abtest']['B']);
                if (!empty($mailVersion)) {
                    unset($mailVersion->id);
                    $mailVersion->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
                    $mailVersion->name .= '_copy';
                    $mailVersion->parent_id = $idNewMail;
                    $mailVersion->id = $mailClass->save($mailVersion);
                    $campaign->sending_params['abtest']['B'] = $mailVersion->id;
                }
            } else {
                $translations = $mailClass->getTranslationsById($oldMailId, true);
                foreach ($translations as $oneTranslation) {
                    unset($oneTranslation->id);
                    $oneTranslation->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
                    $oneTranslation->name .= '_copy';
                    $oneTranslation->parent_id = $idNewMail;
                    $mailClass->save($oneTranslation);
                }
            }

            //we set the new mail id and save campaign
            $campaign->mail_id = $idNewMail;
            $campaignId = $campaignClass->save($campaign);

            //We get the lists
            $allLists = $campaignClass->getListsByMailId($oldMailId);

            $campaignClass->manageListsToCampaign($allLists, $idNewMail);
        }

        acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_DUPLICATED_SUCCESS'));

        if (count($campaignsSelected) == 1 && acym_getVar('string', 'step', '') == 'summary') {
            acym_setVar('campaignId', $campaignId);
            $this->editEmail();
        } else {
            $this->listing();
        }
    }

    public function duplicateFollowup()
    {
        //We get the id of follow-ups checked
        $followupsSelected = acym_getVar('int', 'elements_checked');

        $followupClass = new FollowupClass();
        $mailClass = new MailClass();

        foreach ($followupsSelected as $oneFollowup) {
            // Duplicate the follow-up + list associated
            $followUp = $followupClass->getOneByIdWithMails($oneFollowup);
            $followupEmails = $followUp->mails;

            unset($followUp->id, $followUp->creation_date, $followUp->list_id, $followUp->mails);
            $followUp->name .= '_copy';
            $followUp->active = 0;
            $followUp->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
            $newFollowUpId = $followupClass->save($followUp);
            if (empty($newFollowUpId)) {
                acym_enqueueMessage(acym_translation('ACYM_FOLLOWUP_DUPLICATE_ERROR'), 'error');
                $this->listing();

                return;
            }
            $followUp->id = $newFollowUpId;

            // Duplicate emails of the follow-up and attach them to the new followup
            foreach ($followupEmails as $oneEmail) {
                $mail = $mailClass->getOneById($oneEmail->id);
                unset($mail->id, $mail->creation_date, $mail->creator_id, $mail->autosave);
                $newMailId = $mailClass->save($mail);
                if (empty($newMailId)) {
                    acym_enqueueMessage(acym_translation('ACYM_FOLLOWUP_DUPLICATE_ERROR'), 'error');
                    continue;
                }
                $followupData = [
                    'id' => $newFollowUpId,
                    'delay' => $oneEmail->delay,
                    'delay_unit' => $oneEmail->delay_unit,
                ];
                $followupClass->saveDelaySettings($followupData, $newMailId);
            }
        }
        $this->listing();
    }

    public function unpause_campaign()
    {
        $id = acym_getVar('int', 'campaignId', 0);
        if (empty($id)) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_NOT_FOUND'), 'error');
            $this->listing();

            return;
        }

        acym_redirect(acym_completeLink('queue', false, true).'&task=playPauseSending&acym__queue__play_pause__active__new_value=1&acym__queue__play_pause__campaign_id='.$id);
    }

    public function stopSending()
    {
        $this->_stopAction('stopSendingCampaignId');
    }

    public function stopScheduled()
    {
        $this->_stopAction('stopScheduledCampaignId');
    }

    private function _stopAction($action)
    {
        acym_checkToken();

        $campaignID = acym_getVar('int', $action);
        $campaignClass = new CampaignClass();

        if (!empty($campaignID)) {
            if (!$campaignClass->hasUserAccess($campaignID)) {
                die('Access denied for campaign send modification');
            }

            $campaign = new stdClass();
            $campaign->id = $campaignID;
            $campaign->active = 0;
            $campaign->draft = 1;

            $campaignId = $campaignClass->save($campaign);
            if (empty($campaignId)) {
                acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_CANT_BE_SAVED'), 'error');
            } else {
                acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'));
            }
        } else {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_CANT_BE_SAVED'), 'error');
        }

        $this->listing();
    }

    public function confirmCampaign()
    {
        $this->updateOpenAcymailerPopup();
        $campaignId = acym_getVar('int', 'campaignId');
        $campaignSendingDate = acym_getVar('string', 'sending_date');
        $resendTarget = acym_getVar('cmd', 'resend_target', '');
        $campaignClass = new CampaignClass();

        if (!$campaignClass->hasUserAccess($campaignId)) {
            die('Access denied for campaign confirmation');
        }

        $campaign = new stdClass();
        $campaign->id = $campaignId;
        $campaign->draft = 0;
        $campaign->active = 1;
        $campaign->sent = 0;

        $currentCampaign = $campaignClass->getOneById($campaignId);
        if (!empty($resendTarget)) {
            $currentCampaign->sending_params['resendTarget'] = $resendTarget;
            $campaign->sending_params = $currentCampaign->sending_params;
            acym_trigger('onAcymResendCampaign', [$currentCampaign->mail_id]);
        }

        $resultSave = $campaignClass->save($campaign);

        if ($resultSave) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_CONFIRMED_CAMPAIGN', acym_date($campaignSendingDate, 'j F Y H:i')));
        } else {
            acym_enqueueMessage(acym_translation('ACYM_CANT_CONFIRM_CAMPAIGN').' : '.end($campaignClass->errors), 'error');
        }

        $this->listing();
    }

    public function activeAutoCampaign()
    {
        $this->updateOpenAcymailerPopup();
        $campaignId = acym_getVar('int', 'campaignId');
        $campaignClass = new CampaignClass();

        $campaign = new stdClass();
        $campaign->id = $campaignId;
        $campaign->draft = 0;
        $campaign->active = 1;
        $campaign->sent = 0;

        $resultSave = $campaignClass->save($campaign);

        if ($resultSave) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_IS_ACTIVE'));
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
        }

        $this->listing();
    }

    public function saveAsDraftCampaign()
    {
        $campaignId = acym_getVar('int', 'campaignId');
        $campaignClass = new CampaignClass();

        if (!$campaignClass->hasUserAccess($campaignId)) {
            die('Access denied for campaign draft save');
        }

        $campaign = new stdClass();
        $campaign->id = $campaignId;
        $campaign->draft = 1;
        $campaign->active = 0;

        $resultSave = $campaignClass->save($campaign);

        if ($resultSave) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_SUCCESSFULLY_SAVE_AS_DRAFT'));
        } else {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_CANT_BE_SAVED').' : '.end($campaignClass->errors), 'error');
        }

        $this->listing();
    }

    public function toggleActivateColumnCampaign()
    {

        $campaignId = acym_getVar('int', 'campaignId');
        $campaignClass = new CampaignClass();

        $campaign = $campaignClass->getOneById($campaignId);
        if (empty($campaign)) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_CANT_BE_SAVED').' : '.end($campaignClass->errors), 'error');
            $this->listing();

            return;
        }

        $campaign->active = empty($campaign->active) ? 1 : 0;

        // Remove the next trigger when disabling the automatic campaign. It will be calculated again properly if reactivated
        if ($campaign->active === 0 && $campaign->sending_type === CampaignClass::SENDING_TYPE_AUTO) {
            $campaign->next_trigger = null;
        }

        $resultSave = $campaignClass->save($campaign);

        if ($resultSave) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_SUCCESSFULLY_SAVE_AS_DRAFT'));
        } else {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_CANT_BE_SAVED').' : '.end($campaignClass->errors), 'error');
        }

        $this->listing();
    }

    public function addQueue()
    {
        acym_checkToken();
        $this->updateOpenAcymailerPopup();

        $campaignClass = new CampaignClass();
        $campaignID = acym_getVar('int', 'campaignId', 0);

        if (empty($campaignID)) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_NOT_FOUND'), 'error');
        } else {

            if (!$campaignClass->hasUserAccess($campaignID)) {
                die('Access denied to add campaign to the queue');
            }
            $campaign = $campaignClass->getOneByIdWithMail($campaignID);

            $resendTarget = acym_getVar('cmd', 'resend_target', '');
            if (!empty($resendTarget)) {
                $currentCampaign = $campaignClass->getOneById($campaignID);
                $currentCampaign->sending_params['resendTarget'] = $resendTarget;
                $campaignClass->save($currentCampaign);
                acym_trigger('onAcymResendCampaign', [$currentCampaign->mail_id]);
            }

            $status = $campaignClass->send($campaignID);

            if ($status) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_CAMPAIGN_ADDED_TO_QUEUE', $campaign->name), 'info');
            } else {
                if (empty($campaignClass->errors)) {
                    acym_enqueueMessage(acym_translationSprintf('ACYM_ERROR_QUEUE_CAMPAIGN', $campaign->name), 'error');
                } else {
                    acym_enqueueMessage($campaignClass->errors, 'error');
                }
            }
        }

        $this->_redirectAfterQueued();
    }

    private function _redirectAfterQueued()
    {
        if (acym_isAdmin() && (!acym_level(ACYM_ESSENTIAL) || $this->config->get('cron_last', 0) < (time() - 43200))) {
            acym_redirect(acym_completeLink('queue&task=campaigns', false, true));
        } else {
            $this->listing();
        }
    }

    private function updateOpenAcymailerPopup()
    {
        if (acym_isAdmin() && $this->config->get('mailer_method') === 'acymailer' && intval($this->config->get('acymailer_popup', 0)) === 0) {
            $this->config->save(['acymailer_popup' => '1']);
        }
    }

    public function updateArchive()
    {
        $campaignId = acym_getVar('int', 'campaignId', 0);
        if (empty($campaignId)) {
            acym_sendAjaxResponse('', [], false);
        }

        $campaignClass = new CampaignClass();
        $campaign = $campaignClass->getOneById($campaignId);
        if (empty($campaign)) {
            acym_sendAjaxResponse(acym_translation('ACYM_CAMPAIGN_NOT_FOUND'), [], false);
        }

        $mailArchiveClass = new MailArchiveClass();
        $archive = $mailArchiveClass->getOneByMailId($campaign->mail_id);
        if (!empty($archive)) {
            $mailArchiveClass->delete($archive->id);
        }

        acym_sendAjaxResponse();
    }
}
