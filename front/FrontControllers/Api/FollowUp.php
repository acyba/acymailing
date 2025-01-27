<?php

namespace AcyMailing\FrontControllers\Api;

use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\MailClass;

trait FollowUp
{
    public function createOrUpdateFollowUp(): void
    {
        $decodedData = acym_getJsonData();

        $allowedTriggers = [
            'hikashop_purchase',
            'user_subscribe',
            'birthday',
            'user_creation',
            'woocommerce_purchase',
        ];

        if (!empty($decodedData['trigger']) && !in_array($decodedData['trigger'], $allowedTriggers)) {
            $this->sendJsonResponse(['message' => 'Invalid trigger.'], 422);
        }

        $name = '';
        $displayName = '';
        $condition = [];
        $trigger = '';
        $active = 0;
        $sendOnce = 1;
        $loop = 0;
        $loopDelay = 0;
        $loopMailSkip = [];

        $followUpClass = new FollowupClass();
        if (isset($decodedData['followUpId'])) {
            $followUp = $followUpClass->getOneById($decodedData['followUpId']);
            if (empty($followUp)) {
                $this->sendJsonResponse(['message' => 'Follow-up not found.'], 404);
            }
            $name = $followUp->name;
            $displayName = $followUp->display_name;
            $trigger = $followUp->trigger;
            $active = $followUp->active;
            $sendOnce = $followUp->send_once;
            $condition = $followUp->condition;
            $loop = $followUp->loop;
            $loopDelay = $followUp->loop_delay;
            $loopMailSkip = $followUp->loop_mail_skip;
        } else {
            if (empty($decodedData['name'])) {
                $this->sendJsonResponse(['message' => 'Follow-up name missing.'], 422);
            }

            if (empty($decodedData['trigger'])) {
                $this->sendJsonResponse(['message' => 'Follow-up trigger missing.'], 422);
            }

            if (empty($decodedData['display_name'])) {
                $this->sendJsonResponse(['message' => 'Follow-up display name missing.'], 422);
            }

            $followUp = new \stdClass();
            $followUp->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
        }

        $followUp->name = $decodedData['name'] ?? $name;
        $followUp->display_name = $decodedData['display_name'] ?? $displayName;
        $followUp->trigger = $decodedData['trigger'] ?? $trigger;
        $followUp->active = $decodedData['active'] ?? $active;
        $followUp->send_once = $decodedData['send_once'] ?? $sendOnce;
        $followUp->condition = $decodedData['condition'] ?? $condition;
        $followUp->loop = $decodedData['loop'] ?? $loop;
        $followUp->loop_delay = $decodedData['loop_delay'] ?? $loopDelay;
        $followUp->loop_mail_skip = $decodedData['loop_mail_skip'] ?? $loopMailSkip;

        $followUpId = $followUpClass->save($followUp);

        if (empty($followUpId)) {
            $this->sendJsonResponse(['message' => 'Error creating follow-up.', 'errors' => $followUpClass->errors], 500);
        }

        $this->sendJsonResponse(['followUpId' => $followUpId], 201);
    }

    public function attachEmailToFollowUp(): void
    {
        $decodedData = acym_getJsonData();

        if (empty($decodedData['followUpId'])) {
            $this->sendJsonResponse(['message' => 'Follow-up ID missing.'], 422);
        }

        $followUpClass = new FollowupClass();
        $followUp = $followUpClass->getOneById($decodedData['followUpId']);

        if (empty($followUp)) {
            $this->sendJsonResponse(['message' => 'Follow-up not found.'], 404);
        }

        if (empty($decodedData['mail'])) {
            $this->sendJsonResponse(['message' => 'Mail data missing.'], 422);
        }

        if (empty($decodedData['delay'])) {
            $this->sendJsonResponse(['message' => 'Delay missing.'], 422);
        }

        if (empty($decodedData['delay_unit'])) {
            $this->sendJsonResponse(['message' => 'Delay unit missing.'], 422);
        }

        $delayUnitAllowed = ['minutes', 'hours', 'days', 'weeks', 'months'];

        if (!in_array($decodedData['delay_unit'], $delayUnitAllowed)) {
            $this->sendJsonResponse(['message' => 'Invalid delay unit.'], 422);
        }

        $mailName = '';
        $mailSubject = '';
        $mailBody = '';
        $mailBcc = '';
        $fromName = '';
        $fromEmail = '';
        $replyToName = '';
        $replyToEmail = '';
        $bounceEmail = '';
        $preHeader = '';

        // Create the Mail element
        $mailClass = new MailClass();
        if (!empty($decodedData['mail']['id'])) {
            $mail = $mailClass->getOneById($decodedData['mail']['id']);
            $mailIds = $followUpClass->getEmailsByIds($decodedData['followUpId']);

            if (empty($mail) || !in_array($decodedData['mail']['id'], $mailIds)) {
                $this->sendJsonResponse(['message' => 'Mail not found.'], 404);
            }

            $mailName = $mail->name;
            $mailSubject = $mail->subject;
            $mailBody = $mail->body;
            $mailBcc = $mail->bcc;

            $fromName = $mail->from_name;
            $fromEmail = $mail->from_email;
            $replyToName = $mail->reply_to_name;
            $replyToEmail = $mail->reply_to_email;
            $bounceEmail = $mail->bounce_email;
            $preHeader = $mail->preheader;
        } else {
            $mail = new \stdClass();
        }

        $mail->subject = $decodedData['mail']['subject'] ?? $mailSubject;
        $mail->name = $decodedData['mail']['name'] ?? $mailName;
        $mail->body = $decodedData['mail']['body'] ?? $mailBody;
        $mail->bcc = $decodedData['mail']['bcc'] ?? $mailBcc;

        $mail->from_name = $decodedData['mail']['from_name'] ?? $fromName;
        $mail->from_email = $decodedData['mail']['from_email'] ?? $fromEmail;
        $mail->reply_to_name = $decodedData['mail']['reply_to_name'] ?? $replyToName;
        $mail->reply_to_email = $decodedData['mail']['reply_to_email'] ?? $replyToEmail;
        $mail->bounce_email = $decodedData['mail']['bounce_email'] ?? $bounceEmail;
        $mail->preheader = $decodedData['mail']['preheader'] ?? $preHeader;

        $mail->type = MailClass::TYPE_FOLLOWUP;
        $mail->drag_editor = 0;

        $mailId = $mailClass->save($mail);

        switch ($decodedData['delay_unit']) {
            case 'minutes':
                $delayUnit = 60;
                break;
            case 'hours':
                $delayUnit = 3600;
                break;
            case 'weeks':
                $delayUnit = 604800;
                break;
            case 'months':
                $delayUnit = 2628000;
                break;
            default:
                $delayUnit = 86400;
        }

        $followUpData = [
            'id' => $decodedData['followUpId'],
            'delay' => $decodedData['delay'],
            'delay_unit' => $delayUnit,
        ];

        if ($followUpClass->saveDelaySettings($followUpData, $mailId)) {
            $this->sendJsonResponse(['message' => 'Email attached to follow-up.', 'mailId' => $mailId]);
        } else {
            $this->sendJsonResponse(['message' => 'Error attaching email to follow-up.'], 500);
        }
    }

    public function deleteEmailFromFollowUp(): void
    {
        $followUpId = acym_getVar('int', 'followUpId', 0);
        $mailId = acym_getVar('int', 'mailId', 0);

        if (empty($followUpId)) {
            $this->sendJsonResponse(['message' => 'Follow-up ID missing.'], 422);
        }

        $followUpClass = new FollowupClass();
        $followUp = $followUpClass->getOneById($followUpId);

        if (empty($followUp)) {
            $this->sendJsonResponse(['message' => 'Follow-up not found.'], 404);
        }

        if (empty($mailId)) {
            $this->sendJsonResponse(['message' => 'Mail ID missing.'], 422);
        }

        $mailIds = $followUpClass->getEmailsByIds($followUpId);

        if (!in_array($mailId, $mailIds)) {
            $this->sendJsonResponse(['message' => 'Mail not found.'], 404);
        }

        if ($followUpClass->deleteMail($mailId)) {
            $this->sendJsonResponse(['message' => 'Email deleted from follow-up.']);
        } else {
            $this->sendJsonResponse(['message' => 'Error deleting email from follow-up.'], 500);
        }
    }

    /**
     * @return void
     */
    public function getFollowUpById(): void
    {
        $followUpId = acym_getVar('int', 'followUpId', 0);

        if (empty($followUpId)) {
            $this->sendJsonResponse(['message' => 'Follow-up ID not provided.'], 422);
        }

        $followUpClass = new FollowupClass();
        $followUp = $followUpClass->getOneById($followUpId);

        if (empty($followUp)) {
            $this->sendJsonResponse(['message' => 'Follow-up not found.'], 404);
        }

        $mails = $followUpClass->getFollowupsWithMailsInfoByIds($followUpId);
        $followUp->mails = [];

        if (!empty($mails[$followUpId])) {
            $followUp->mails = $mails[$followUpId];
        }

        $this->sendJsonResponse([$followUp]);
    }

    public function deleteFollowUp(): void
    {
        $followUpId = acym_getVar('int', 'followUpId', 0);

        if (empty($followUpId)) {
            $this->sendJsonResponse(['message' => 'Follow-up ID not provided.'], 422);
        }

        $followUpClass = new FollowupClass();
        $followUp = $followUpClass->getOneById($followUpId);

        if (empty($followUp)) {
            $this->sendJsonResponse(['message' => 'Follow-up not found.'], 404);
        }

        if ($followUpClass->delete($followUpId)) {
            $this->sendJsonResponse(['message' => 'Follow-up deleted.']);
        } else {
            $this->sendJsonResponse(['message' => 'Error deleting follow-up.'], 500);
        }
    }

    public function getFollowUps(): void
    {
        $followUpClass = new FollowupClass();
        $followUps = $followUpClass->getXFollowups([
            'offset' => acym_getVar('int', 'offset', 0),
            'limit' => acym_getVar('int', 'limit', 100),
            'filters' => acym_getVar('array', 'filters', []),
        ]);

        $this->sendJsonResponse(array_values($followUps));
    }
}
