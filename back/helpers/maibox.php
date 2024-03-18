<?php

namespace AcyMailing\Helpers;

use AcyMailing\Classes\UserClass;
use Couchbase\User;

class MailboxHelper extends BounceHelper
{
    public $mailboxConfig = [];
    public $conditions;

    public function buildConfigFromMailbox(): bool
    {
        $this->mailboxConfig = [
            'server' => $this->action->server,
            'username' => $this->action->username,
            'password' => $this->action->password,
            'port' => $this->action->port,
            'connect_method' => $this->action->connection_method,
            'secure_method' => $this->action->secure_method,
            'self_signed' => $this->action->self_signed,
            'timeout' => 10,
        ];

        return $this->isConfigurationValid();
    }

    private function isConfigurationValid(): bool
    {
        $error = false;
        foreach ($this->mailboxConfig as $key => $oneConfig) {
            if (empty($oneConfig) && !in_array($key, ['self_signed', 'secure_method'])) {
                $error = true;
                break;
            }
        }

        return !$error;
    }

    public function isConnectionValid($mailbox, $close = true): bool
    {
        $this->action = $mailbox;
        if (!$this->buildConfigFromMailbox()) {
            return false;
        }

        if (!$this->init($this->mailboxConfig) || !$this->connect()) {
            return false;
        }

        if ($close) {
            $this->close();
        }

        return true;
    }

    private function senderConditionPass(): bool
    {
        $conditions = $this->action->conditions;
        $fromEmail = $this->_message->header->from_email;

        if ($conditions['sender'] == 'specific') {
            $allowedSenders = explode(',', $conditions['specific']);
            if (empty($allowedSenders) || !in_array($fromEmail, $allowedSenders)) {
                $this->display(acym_translationSprintf('ACYM_SENDER_NOT_ALLOWED_X', $fromEmail));

                return false;
            }
        }

        if ($conditions['sender'] == 'group') {
            $cmsUserId = acym_getCmsUserIdByEmail($fromEmail);
            if (empty($cmsUserId)) {
                $this->display(acym_translationSprintf('ACYM_SENDER_NOT_ALLOWED_X_CMS', $fromEmail, ACYM_CMS_TITLE));

                return false;
            }

            $groups = acym_getGroupsByUser($cmsUserId, false);

            if (!in_array($conditions['groups'], $groups)) {
                $this->display(acym_translationSprintf('ACYM_SENDER_NOT_ALLOWED_X_GROUP', $fromEmail, implode(', ', $conditions['groups'])));

                return false;
            }
        }

        if ($conditions['sender'] == 'lists' && !empty($conditions['lists'])) {
            $userClass = new UserClass();
            $user = $userClass->getOneByEmail($fromEmail);

            if (empty($user)) {
                $this->display(acym_translationSprintf('ACYM_SENDER_NOT_ALLOWED_X_NOT_EXISTS', $fromEmail));

                return false;
            }

            $subscriptions = $userClass->getSubscriptionStatus($user->id, $conditions['lists'], 1);

            if (empty($subscriptions)) {
                $this->display(acym_translationSprintf('ACYM_SENDER_NOT_ALLOWED_X_LIST', $fromEmail));

                return false;
            }
        }

        return true;
    }

    private function subjectConditionPass(): bool
    {
        $conditions = $this->action->conditions;
        $subject = $this->_message->subject;

        $passSubject = true;
        if (empty($subject)) {
            $passSubject = false;
        } elseif ($conditions['subject'] == 'begins' && strpos($subject, $conditions['subject_text']) !== 0) {
            $passSubject = false;
        } elseif ($conditions['subject'] == 'ends' && strpos(
                $subject,
                $conditions['subject_text']
            ) !== strlen($subject) - strlen($conditions['subjectvalue'])) {
            $passSubject = false;
        } elseif ($conditions['subject'] == 'contains' && strpos($subject, $conditions['subject_text']) === false) {
            $passSubject = false;
        } elseif ($conditions['subject'] == 'regex' && !preg_match($conditions['subject_regex'], $subject)) {
            $passSubject = false;
        }

        if (!$passSubject) {
            $this->display(acym_translation('ACYM_SUBJECT_DOESNT_MATCH'));

            return false;
        }

        return true;
    }

    public function conditionsPass(): bool
    {
        if (!empty($this->action->conditions['sender']) && !$this->senderConditionPass()) {
            return false;
        }

        if (!empty($this->action->conditions['subject']) && !$this->subjectConditionPass()) {
            return false;
        }

        return true;
    }

    public function handleAction()
    {
        if (empty($this->action)) {
            return;
        }

        $maxMessages = min($this->nbMessages, 100);

        if ($this->report) {
            if (function_exists('apache_get_modules')) {
                $modules = apache_get_modules();
                $this->mod_security2 = in_array('mod_security2', $modules);
            }

            /*This is to avoid the blank page... and it apparently works! ;) */
            @ini_set('output_buffering', 'off');
            @ini_set('zlib.output_compression', 0);

            if (!headers_sent()) {
                while (ob_get_level() > 0 && $this->obend++ < 3) {
                    @ob_end_flush();
                }
            }

            //We prepare the area where we will add informations...
            $disp = "<div style='position:fixed; top:3px;left:3px;background-color : white;border : 1px solid grey; padding : 3px;font-size:14px'>";
            $disp .= acym_translation('ACYM_MAILBOX_ACTIONS');
            $disp .= ':  <span id="counter">0</span> / '.$maxMessages;
            $disp .= '</div>';
            $disp .= '<script type="text/javascript" language="javascript">';
            $disp .= 'var mycounter = document.getElementById("counter");';
            $disp .= 'function setCounter(val){ mycounter.innerHTML=val;}';
            $disp .= '</script>';
            echo $disp;
            if (function_exists('ob_flush')) {
                @ob_flush();
            }
            if (!$this->mod_security2) {
                @flush();
            }
        }

        $msgNB = $maxMessages;

        while (($msgNB > 0) && ($this->_message = $this->getMessage($msgNB))) {
            if ($this->report) {
                echo '<script type="text/javascript" language="javascript">setCounter('.($maxMessages - $msgNB + 1).')</script>';
                if (function_exists('ob_flush')) {
                    @ob_flush();
                }
                if (!$this->mod_security2) {
                    @flush();
                }
            }
            $this->_message->messageNB = $msgNB;
            $msgNB--;

            if (empty($this->_message->subject)) {
                $this->_message->subject = '';
            }

            //We could not retrieve the message... we continue with the next message
            if (!$this->decodeMessage()) {
                $this->display(acym_translation('ACYM_ERROR_RETRIEVING_MESSAGE'), false, $maxMessages - $this->_message->messageNB + 1);
                continue;
            }

            if (empty($this->_message->html)) {
                $this->_message->html = nl2br($this->_message->text);
            }
            $stripedHtml = strip_tags($this->_message->html);

            if (strlen($stripedHtml) < 3) {
                $this->display(acym_translation('ACYM_EMPTY_EMAIL_X', acym_escape($this->_message->subject)));
                if ($this->action->delete_wrong_emails) {
                    $this->deleteMessage($this->_message->messageNB);
                }
                continue;
            }

            if (!$this->conditionsPass()) {
                $this->display(acym_translation('ACYM_INVALID_EMAIL', $this->_message->subject));
                if ($this->action->delete_wrong_emails) {
                    $this->deleteMessage($this->_message->messageNB);
                }
                continue;
            }

            if ($this->executeActions()) {
                $this->display(acym_translation('ACYM_MESSAGE_DELETED'));
                $this->deleteMessage($this->_message->messageNB);
            }

            //We don't have time to finish the process? Ok, we stop it now!
            if (!empty($this->stoptime) && time() > $this->stoptime) {
                break;
            }
        }
    }

    public function executeActions(): bool
    {
        $executedActions = [];

        foreach ($this->action->actions as $actionKey => $oneAction) {

            $this->display('<strong>'.acym_translationSprintf('ACYM_ACTION_X', intval($actionKey) + 1).'</strong>');

            $actionId = array_keys($oneAction)[0];

            if (empty($actionId)) {
                continue;
            }
            $executedActions[$actionKey] = true;

            $reportMessages = [];
            acym_trigger('onAcymMailboxAction_'.$actionId, [&$oneAction[$actionId], &$reportMessages, &$executedActions[$actionKey], $this]);

            foreach ($reportMessages as $reportMessage) {
                $this->display($reportMessage['message'], $reportMessage['success']);
            }

            $this->attachments = [];
        }

        $allActionsExecuted = true;
        foreach ($executedActions as $oneActionExecuted) {
            $allActionsExecuted = $allActionsExecuted && $oneActionExecuted;
        }

        // If false one or more actions were not executed
        return $allActionsExecuted;
    }

    public function getAttachments()
    {
        $newAttachments = [];
        foreach ($this->attachments as $attachment) {
            unset($attachment->size);
            $newAttachments[] = $attachment;
        }

        return json_encode($newAttachments);
    }
}
