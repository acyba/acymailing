<?php

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Classes\CampaignClass;
use AcyMailing\Helpers\MailerHelper;

class plgAcymForward extends acymPlugin
{
    public function onAcymMailboxActionDefine(&$actions)
    {
        $listClass = new ListClass();
        $lists = $listClass->getAllWithIdName();

        $mailClass = new MailClass();
        $templates = $mailClass->getTemplatesForMailboxAction();

        $firstOption = new stdClass();
        $firstOption->id = 0;
        $firstOption->name = acym_translation('ACYM_CHOOSE_TEMPLATE');
        array_unshift($templates, $firstOption);

        $actions['forward_specific'] = new stdClass();
        $actions['forward_specific']->name = acym_translation('ACYM_FORWARD_EMAIL');
        $actions['forward_specific']->option = '<input type="text" 
                                                        name="acym_action[__num__][forward_specific][addresses]" 
                                                        placeholder="address@example.com,other@example.com"><br />';
        $actions['forward_specific']->option .= acym_translationSprintf(
            'ACYM_INCLUDE_IN_TEMPLATE',
            '<div class="intext_select_mailbox cell">'.acym_select(
                $templates,
                'acym_action[__num__][forward_specific][template_id]',
                null,
                ['class' => 'acym__select'],
                'id',
                'name'
            ).'</div>'
        );
        $actions['forward_specific']->option .= acym_info('ACYM_INCLUDE_IN_TEMPLATE_DESC');

        $actions['forward_list'] = new stdClass();
        $actions['forward_list']->name = acym_translation('ACYM_FORWARD_TO_A_LIST');
        $actions['forward_list']->option = '<div class="intext_select_mailbox cell">';
        $actions['forward_list']->option .= acym_select(
            $lists,
            'acym_action[__num__][forward_list][list_id]',
            null,
            ['class' => 'acym__select']
        );
        $actions['forward_list']->option .= '</div><br /><br />';
        $actions['forward_list']->option .= acym_translationSprintf(
            'ACYM_INCLUDE_IN_TEMPLATE',
            '<div class="intext_select_mailbox cell">'.acym_select(
                $templates,
                'acym_action[__num__][forward_list][template_id]',
                null,
                ['class' => 'acym__select'],
                'id',
                'name'
            ).'</div>'
        );
        $actions['forward_list']->option .= acym_info('ACYM_INCLUDE_IN_TEMPLATE_DESC');
    }

    public function onAcymMailboxActionSummaryListing(&$action, &$result)
    {
        if (!empty($action['forward_list'])) {
            $listClass = new ListClass();
            $list = $listClass->getOneById($action['forward_list']['list_id']);

            if (empty($list)) {
                return;
            }

            $result[] = acym_translationSprintf('ACYM_FORWARD_TO_A_LIST_X', $list->name);
        }

        if (!empty($action['forward_specific'])) {
            $result[] = acym_translationSprintf('ACYM_FORWARD_EMAIL_X', $action['forward_specific']['addresses']);
        }
    }

    private function prepareForward($action, &$report, &$executedActions, $mailboxHelper)
    {
        $newMail = new stdClass();

        if (!isset($action['template_id'])) {
            $executedActions = false;
            $report[] = [
                'message' => acym_translation('ACYM_COULD_NOT_FIND_TEMPLATE'),
                'success' => false,
            ];

            return $newMail;
        }

        // We're going to forward the email, save its attachments and embed images
        $mailboxHelper->decodeMessage(true);

        $subject = $mailboxHelper->_message->subject;

        //If the option remove subject is set
        if (!empty($mailboxHelper->action->conditions['subject_remove'])) {
            if (!empty($mailboxHelper->action->conditions['subject_text'])) {
                $subject = str_replace($mailboxHelper->action->conditions['subject_text'], '', $mailboxHelper->_message->subject);
            } elseif (!empty($mailboxHelper->action->conditions['subject_regex'])) {
                $subject = preg_replace($mailboxHelper->action->conditions['subject_regex'], '', $mailboxHelper->_message->subject);
            }
        }

        if (empty($action['template_id'])) {
            $newMail->name = acym_translationSprintf('ACYM_FORWARD_SUBJECT', $subject);
            $newMail->body = $mailboxHelper->_message->html;
        } else {
            $mailClass = new MailClass();
            $newMail = $mailClass->getOneById($action['template_id']);

            if (strpos($newMail->body, '{emailcontent}') === false) {
                $executedActions = false;
                $report[] = [
                    'message' => acym_translation('ACYM_TEMPLATE_DOES_NOT_CONTAIN_EMAILCONTENT'),
                    'success' => false,
                ];

                return;
            } else {
                $newMail->body = str_replace('{emailcontent}', $mailboxHelper->_message->html, $newMail->body);
            }
            unset($newMail->id);
        }

        $newMail->subject = $subject;
        $newMail->type = mailClass::TYPE_STANDARD;
        $newMail->creation_date = acym_date('now', 'Y-m-d H:i:s', false);

        if (!empty($mailboxHelper->action->senderfrom)) {
            $newMail->from_email = $mailboxHelper->decodeHeader($mailboxHelper->_message->header->from_email);
            $newMail->from_name = strip_tags($mailboxHelper->decodeHeader($mailboxHelper->_message->header->from_name));
        }

        if (!empty($this->action->senderto)) {
            $newMail->reply_to_email = $mailboxHelper->decodeHeader($mailboxHelper->_message->header->from_email);
            $newMail->reply_to_name = strip_tags($mailboxHelper->decodeHeader($mailboxHelper->_message->header->from_name));
        }

        if (!empty($mailboxHelper->attachments)) {
            $newMail->attachments = $mailboxHelper->getAttachments();
        }

        return $newMail;
    }

    public function saveNewMail($newMail)
    {
        $mailClass = new MailClass();
        $newMail->id = $mailClass->save($newMail);

        if (empty($newMail->id)) {
            return false;
        }

        return $newMail->id;
    }

    public function onAcymMailboxAction_forward_specific(&$action, &$report, &$executedActions, $mailboxHelper)
    {
        $newMail = $this->prepareForward($action, $report, $executedActions, $mailboxHelper);

        if (empty($newMail)) {
            $report[] = [
                'message' => acym_translation('ACYM_COULD_NOT_FORWARD_EMAIL'),
                'success' => false,
            ];

            return;
        }

        $newMail->id = $this->saveNewMail($newMail);

        if (empty($newMail->id)) {
            return;
        }

        $receivers = explode(',', $action['addresses']);
        $mailHelper = new MailerHelper();
        $mailHelper->autoAddUser = true;
        $mailInfo = $newMail->subject.' ('.$newMail->id.')';
        foreach ($receivers as $oneReceiver) {
            if ($mailHelper->sendOne($newMail->id, $oneReceiver)) {
                $report[] = [
                    'message' => acym_translationSprintf('ACYM_SEND_SUCCESS', $mailInfo, acym_escape($oneReceiver)),
                    'success' => true,
                ];
            } else {
                $report[] = [
                    'message' => acym_translationSprintf('ACYM_SEND_ERROR', $mailInfo, acym_escape($oneReceiver)),
                    'success' => false,
                ];
            }
        }
    }

    public function onAcymMailboxAction_forward_list(&$action, &$report, &$executedActions, $mailboxHelper)
    {
        $newMail = $this->prepareForward($action, $report, $executedActions, $mailboxHelper);

        if (empty($newMail)) {
            $report[] = [
                'message' => acym_translation('ACYM_COULD_NOT_FORWARD_EMAIL'),
                'success' => false,
            ];

            return;
        }

        if (!empty($mailboxHelper->_message->fromaddress)) {
            $newMail->body = str_replace('{mailheader:from}', htmlspecialchars($mailboxHelper->_message->fromaddress, ENT_QUOTES, 'UTF-8'), $newMail->body);
        }
        if (!empty($mailboxHelper->_message->toaddress)) {
            $newMail->body = str_replace('{mailheader:to}', htmlspecialchars($mailboxHelper->_message->toaddress, ENT_QUOTES, 'UTF-8'), $newMail->body);
        }

        if (!empty($mailboxHelper->_message->ccaddress)) {
            $newMail->body = str_replace('{mailheader:cc}', htmlspecialchars($mailboxHelper->_message->ccaddress, ENT_QUOTES, 'UTF-8'), $newMail->body);
        }

        if (!empty($mailboxHelper->_message->Date)) {
            $newMail->body = str_replace('{mailheader:date}', date('d.m.Y H:i', strtotime($mailboxHelper->_message->Date)), $newMail->body);
        }

        // Clean tags if we couldn't replace them
        $newMail->body = str_replace(
            [
                '{mailheader:from}',
                '{mailheader:to}',
                '{mailheader:cc}',
                '{mailheader:date}',
            ],
            '',
            $newMail->body
        );

        $newMail->id = $this->saveNewMail($newMail);

        if (!$newMail->id) {
            return;
        }

        $newCampaign = new stdClass();
        $newCampaign->mail_id = $newMail->id;
        $newCampaign->sending_date = acym_date('now', 'Y-m-d H:i:s', false);
        $newCampaign->sending_type = CampaignClass::SENDING_TYPE_NOW;

        $campaignClass = new CampaignClass();
        $newCampaign->id = $campaignClass->save($newCampaign);

        if (empty($newCampaign->id)) {
            $report[] = [
                'message' => acym_translation('ACYM_COULD_NOT_CREATE_CAMPAIGN'),
                'success' => false,
            ];

            return;
        }

        $listAssigned = $campaignClass->manageListsToCampaign([$action['list_id']], $newMail->id);

        if (!$listAssigned) {
            $report[] = [
                'message' => acym_translation('ACYM_COULD_NOT_ASSIGN_LIST'),
                'success' => false,
            ];

            return;
        }

        $statusSent = $campaignClass->send($newCampaign->id);

        if (!$statusSent) {
            $report[] = [
                'message' => acym_translation('ACYM_COULD_NOT_SEND_CAMPAIGN'),
                'success' => false,
            ];
        } else {
            $report[] = [
                'message' => acym_translation('ACYM_CAMPAIGN_ADDED_QUEUE'),
                'success' => true,
            ];
        }
    }
}
