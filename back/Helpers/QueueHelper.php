<?php

namespace AcyMailing\Helpers;

use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\QueueClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\UserStatClass;
use AcyMailing\Core\AcymObject;

class QueueHelper extends AcymObject
{
    const MESSAGE_TYPE_SUCCESS = 1;
    const MESSAGE_TYPE_WARNING = 2;
    const MESSAGE_TYPE_ERROR = 3;

    public bool $report = true;
    public bool $finish = false;
    public bool $fromManual = false;
    public int $id = 0;
    public int $send_limit = 0;
    public int $nbprocess = 0;
    public int $startSendingFrom = 0;
    public int $start = 0;
    public int $stoptime = 0;
    public int $successSend = 0;
    public int $errorSend = 0;
    public int $pause = 0;
    public int $total;
    public array $messages = [];
    public array $emailTypes = [];

    private int $consecutiveError = 0;
    // Check apache module "mod_security" to avoid flush issue
    private bool $mod_security2 = false;
    // Number of ob_end_flush used in the process
    private int $obend = 0;

    private UserClass $userClass;
    private QueueClass $queueClass;

    public function __construct()
    {
        parent::__construct();

        $this->queueClass = new QueueClass();
        $this->userClass = new UserClass();

        $this->send_limit = (int)$this->config->get('queue_nbmail', 40);

        // The default is 60, which is too much for email sending as it wastes time
        @ini_set('default_socket_timeout', 10);

        // We call cron URLs but don't wait for the result, we still want the script to execute
        @ignore_user_abort(true);

        // Increase the limit to have enough time to send, and prevent infinite process
        $timeLimit = acym_increasePerf();
        if (empty($timeLimit)) {
            $timeLimit = 600;
        }

        // We keep 4 seconds to finish the process properly
        $this->stoptime = time() + $timeLimit - 4;
    }

    public function process(): bool
    {
        // Check if the current sending method has credits left and load the correct number of emails to send for this batch
        $creditsLeft = 10000;
        acym_trigger('onAcymCreditsLeft', [&$creditsLeft]);

        if (empty($creditsLeft)) {
            $this->finish = true;
            if ($this->report) {
                acym_display(acym_translation('ACYM_NOT_ENOUGH_CREDITS'), 'info');
            }

            return false;
        }

        if ($this->send_limit > $creditsLeft) {
            $this->send_limit = $creditsLeft;
        }

        $queueClass = new QueueClass();
        $queueClass->emailtypes = $this->emailTypes;
        $queueElements = $queueClass->getReady($this->startSendingFrom, $this->send_limit, $this->id);

        if (empty($queueElements)) {
            $this->finish = true;
            if ($this->report) {
                acym_display(acym_translation('ACYM_NO_PROCESS'), 'info');
            }

            return true;
        }

        //We stop the flush
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

            //We prepare the area where we will add information
            $disp = '<html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8" />';
            $disp .= '<title>'.acym_translation('ACYM_SEND_PROCESS').'</title>';
            $disp .= '<style>body{font-size:12px;font-family: Arial,Helvetica,sans-serif;}</style></head><body>';
            $disp .= '<div style="margin-bottom: 18px;padding: 8px !important; background-color: #fcf8e3; border: 1px solid #fbeed5; border-radius: 4px;"><p style="margin:0;">'.acym_translation(
                    'ACYM_DONT_CLOSE'
                ).'</p></div>';
            $disp .= "<div style='display: inline;background-color : white;border : 1px solid grey; padding : 3px;font-size:14px'>";
            $disp .= "<span id='divpauseinfo' style='padding:10px;margin:5px;font-size:16px;font-weight:bold;display:none;background-color:black;color:white;'> </span>";
            $disp .= acym_translation('ACYM_SEND_PROCESS').': <span id="counter" >'.$this->start.'</span> / '.$this->total;
            $disp .= '</div>';
            $disp .= "<div id='divinfo' style='display:none; position:fixed; bottom:3px;left:3px;background-color : white; border : 1px solid grey; padding : 3px;'> </div>";
            $disp .= '<br /><br />';
            $url = acym_completeLink('queue&task=continuesend&id='.$this->id.'&totalsend='.$this->total, true, true).'&alreadysent=';
            $disp .= '<script type="text/javascript" language="javascript">';
            $disp .= 'var mycounter = document.getElementById("counter");';
            $disp .= 'var divinfo = document.getElementById("divinfo");
					var divpauseinfo = document.getElementById("divpauseinfo");
					function setInfo(message){ divinfo.style.display = \'block\';divinfo.innerHTML=message; }
					function setPauseInfo(nbpause){ divpauseinfo.style.display = \'\';divpauseinfo.innerHTML=nbpause;}
					function setCounter(val){ mycounter.innerHTML=val;}
					var scriptpause = '.$this->pause.';
					function handlePause(){
						setPauseInfo(scriptpause);
						if(scriptpause > 0){
							scriptpause = scriptpause - 1;
							setTimeout(\'handlePause()\',1000);
						}else{
							document.location.href=\''.$url.'\'+mycounter.innerHTML;
						}
					}
					</script>';
            echo $disp;
            if (function_exists('ob_flush')) {
                @ob_flush();
            }
            if (!$this->mod_security2) {
                @flush();
            }
        }

        $mailerHelper = new MailerHelper();
        $mailerHelper->report = false;
        if ($this->config->get('smtp_keepalive', 1) || $this->config->get('mailer_method') === 'elasticemail') {
            $mailerHelper->SMTPKeepAlive = true;
        }

        //To delete some entries from the queue
        //$queueDelete[mailing][] = subid;
        $queueDelete = [];
        //Update some entries with a new try and a new delay
        //$queueUpdate[mailing][] = subid;
        $queueUpdate = [];
        //Add some stats because we deleted the Newsletter
        //$statsAdd[mailing][status (1 for success/0 for fail)][] = subid;
        $statsAdd = [];

        //Maximum number of try...
        $maxTry = (int)$this->config->get('queue_try', 0);

        $currentMail = $this->start;
        $this->nbprocess = 0;

        if (count($queueElements) < $this->send_limit) {
            $this->finish = true;
        }

        $externalSending = false;
        acym_trigger('onAcymProcessQueueExternalSendingCampaign', [&$externalSending]);

        if ($externalSending) {
            $listExternalSending = [];
            if (!empty($this->id)) {
                acym_trigger('onAcymInitExternalSendingMethodBeforeSend', [&$listExternalSending, $this->id]);
            } else {
                $mailIds = [];
                foreach ($queueElements as $oneElement) {
                    if (!in_array($oneElement->mail_id, $mailIds)) {
                        $mailIds[] = $oneElement->mail_id;
                        acym_trigger('onAcymInitExternalSendingMethodBeforeSend', [&$listExternalSending, $oneElement->mail_id]);
                    }
                }
            }
        }

        $sentEmails = [];
        $emailFrequency = $this->fromManual ? 0 : intval($this->config->get('email_frequency', 0));
        if ($emailFrequency < 0) {
            $emailFrequency = 0;
        }

        $userLists = [];
        $userClass = new UserClass();
        $isSendingMethodByListActive = false;
        acym_trigger('sendingMethodByListActive', [&$isSendingMethodByListActive]);
        $mailerHelper->isSendingMethodByListActive = $isSendingMethodByListActive;

        $statisticsByBatch = $this->config->get('queue_statistics_by_batch', 1) == 1;

        foreach ($queueElements as $oneQueue) {
            if ($isSendingMethodByListActive && empty($userLists[$oneQueue->user_id])) {
                $userSubscriptions = $userClass->getUserStandardListIdById($oneQueue->user_id);
                $userLists[$oneQueue->user_id] = empty($userSubscriptions) ? [] : $userSubscriptions;
            }
            if (!empty($emailFrequency)) {
                sleep($emailFrequency);
            }

            $currentMail++;
            $this->nbprocess++;
            if ($this->report) {
                echo '<script type="text/javascript">setCounter('.$currentMail.')</script>';
                if (function_exists('ob_flush')) {
                    @ob_flush();
                }
                if (!$this->mod_security2) {
                    @flush();
                }
            }

            $isAbTesting = false;

            if (!empty($oneQueue->sending_params)) {
                $sendingParams = json_decode($oneQueue->sending_params, true);

                $isAbTesting = isset($sendingParams['abtest']);
            }

            $this->triggerSendHook((int)$oneQueue->mail_id);
            try {
                $mailerHelper->isAbTest = $isAbTesting;
                $mailerHelper->listsIds = empty($userLists[$oneQueue->user_id]) ? [] : $userLists[$oneQueue->user_id];
                $mailerHelper->setSendingMethodSetting();
                $result = $mailerHelper->sendOne($oneQueue->mail_id, $oneQueue->user_id);
            } catch (\Exception $e) {
                $result = false;
                $this->displayMessage([$e->getMessage()], self::MESSAGE_TYPE_ERROR, $e->getCode());
            }

            if (empty($sentEmails[$oneQueue->mail_id])) {
                $sentEmails[$oneQueue->mail_id] = $mailerHelper->Body;
            }

            $queueDeleteOk = true;
            $otherMessage = '';

            if ($result === true) {
                $this->successSend++;
                $this->consecutiveError = 0;
                $queueDelete[$oneQueue->mail_id][] = $oneQueue->user_id;
                $statsAdd[$oneQueue->mail_id][1][] = $oneQueue->user_id;

                //In case of the e-mail has been sent now, we immediately process the update/delete and stats
                $queueDeleteOk = $this->deleteQueue($queueDelete);
                $mailerHelper->triggerFollowUpAgain($oneQueue->mail_id, $oneQueue->user_id);
                $queueDelete = [];

                //We only update the queue and add the stats every 10 emails so that we can group things and avoid queries
                if (!$statisticsByBatch || $this->nbprocess % 10 == 0) {
                    $this->statsAdd($statsAdd);
                    $this->queueUpdate($queueUpdate);
                    $statsAdd = [];
                    $queueUpdate = [];
                }
            } elseif ($mailerHelper->dtextsFailed) {
                $this->consecutiveError = 0;
                $queueDelete[$oneQueue->mail_id][] = $oneQueue->user_id;
                //In case of the e-mail has been sent now, we immediately process the update/delete and stats
                $queueDeleteOk = $this->deleteQueue($queueDelete);
                $queueDelete = [];
            } else {
                acym_trigger('onAcymFailedSendingEmail', [$oneQueue->mail_id, $oneQueue->user_id, $mailerHelper->errorNumber]);
                $this->errorSend++;

                $shouldTrySendingLater = false;
                if (in_array($mailerHelper->errorNumber, MailerHelper::NEW_TRY_ERRORS)) {
                    if (empty($maxTry) || $oneQueue->try < $maxTry - 1) {
                        $shouldTrySendingLater = true;
                        if ($mailerHelper->failedCounting) {
                            $otherMessage = acym_translationSprintf('ACYM_QUEUE_NEXT_TRY', 60);
                        }
                    }

                    if ($mailerHelper->errorNumber === 1) {
                        $this->consecutiveError++;
                    }

                    //If we have 2 consecutive errors, we pause the process a little bit to avoid possible other issues.
                    if ($this->consecutiveError === 2) {
                        sleep(1);
                    }
                }

                //We delete the queue entry if it's more than number of try allowed
                if (!$shouldTrySendingLater) {
                    $queueDelete[$oneQueue->mail_id][] = $oneQueue->user_id;
                    $statsAdd[$oneQueue->mail_id][0][] = $oneQueue->user_id;
                    if ($mailerHelper->errorNumber === 1 && $this->config->get('bounce_action_maxtry')) {
                        //We have to delete the queue now otherwise we may have problems!
                        $queueDeleteOk = $this->deleteQueue($queueDelete);
                        $queueDelete = [];
                        //Let's execute an action on this subscriber if we have something to do...
                        $otherMessage .= $this->failedActions((int)$oneQueue->user_id);
                    }
                } elseif ($mailerHelper->failedCounting) {
                    $queueUpdate[$oneQueue->mail_id][] = $oneQueue->user_id;
                }

                $mailerHelper->failedCounting = true;
            }

            $messageOnScreen = '[ID '.$oneQueue->mail_id.'] '.$mailerHelper->reportMessage;
            if (!empty($otherMessage)) {
                $messageOnScreen .= ' => '.$otherMessage;
            }
            $this->displayMessage([$messageOnScreen], $result ? self::MESSAGE_TYPE_SUCCESS : self::MESSAGE_TYPE_ERROR, $currentMail);

            //We didn't delete the right number of elements so to avoid a double send process, we stop immediately
            if (!$queueDeleteOk) {
                $this->finish = true;
                break;
            }

            if (!empty($this->stoptime) && $this->stoptime < time()) {
                $this->displayMessage([acym_translation('ACYM_SEND_REFRESH_TIMEOUT')], self::MESSAGE_TYPE_WARNING);
                if ($this->nbprocess < count($queueElements)) {
                    $this->finish = false;
                }
                break;
            }

            if ($this->consecutiveError > 3 && $this->successSend > 3) {
                //We refresh the page in case of we used to send e-mails but now we can not, that may be due to a mysql close connection
                $this->displayMessage([acym_translation('ACYM_SEND_REFRESH_CONNECTION')], self::MESSAGE_TYPE_WARNING);
                break;
            }

            //After 5 consecutive send error, we stop the process and display a message
            //We stop the send process if the user closed the windows so that he can stop the send process at any time he wants
            if ($this->consecutiveError > 5 || ($this->report && connection_aborted())) {
                $this->finish = true;
                break;
            }
        }

        if ($externalSending) {
            foreach ($sentEmails as $mailId => $content) {
                if (!$queueClass->isSendingFinished($mailId)) continue;

                acym_trigger('onAcymSendCampaignOnExternalSendingMethod', [$mailId, $content]);
            }
        }

        //Update all values if it's not already done (we don't do it for each error so that's why we still have some
        $this->deleteQueue($queueDelete);
        $this->statsAdd($statsAdd);
        $this->queueUpdate($queueUpdate);

        if ($mailerHelper->SMTPKeepAlive) {
            $mailerHelper->smtpClose();
        }

        //We finished the send process
        if (!empty($this->total) && $currentMail >= $this->total) {
            $this->finish = true;
        }

        if ($this->consecutiveError > 5) {
            $this->handleError();

            return false;
        }

        if ($this->report && !$this->finish) {
            //We handle the pause in JS.
            echo '<script type="text/javascript" language="javascript">handlePause();</script>';
        }

        if ($this->report) {
            //We need to finish the current page properly
            echo '</body></html>';
            while ($this->obend-- > 0) {
                ob_start();
            }
            exit;
        }

        return true;
    }

    /**
     * Function to add/update elements in the stats
     * $statsAdd[mailing][1 for success or 0 for fail][] = subscriber id;
     * $statsAdd[mailing][1][] = 123;
     */
    public function statsAdd(array $statsAdd): void
    {
        if (empty($statsAdd)) {
            return;
        }

        $userStatClass = new UserStatClass();
        $mailStatClass = new MailStatClass();

        $currentDate = acym_date('now', 'Y-m-d H:i:s');
        $subscriberIds = [];

        foreach ($statsAdd as $mailId => $infos) {
            $mailId = intval($mailId);

            foreach ($infos as $status => $subscribers) {
                foreach ($subscribers as $oneSubscriber) {
                    $oneSubscriber = intval($oneSubscriber);

                    $userStat = new \stdClass();
                    $userStat->user_id = $oneSubscriber;
                    $userStat->mail_id = $mailId;
                    $userStat->send_date = $currentDate;
                    $userStat->fail = $status ? 0 : 1;
                    $userStat->sent = $status ? 1 : 0;
                    $userStat->statusSending = $status;

                    $userStatClass->save($userStat, true);

                    if ($status) {
                        $subscriberIds[] = $oneSubscriber;
                    }
                }
            }

            $nbSent = empty($infos[1]) ? 0 : count($infos[1]);
            $nbFail = empty($infos[0]) ? 0 : count($infos[0]);

            $mailStat = new \stdClass();
            $mailStat->mail_id = $mailId;
            $mailStat->sent = $nbSent;
            $mailStat->fail = $nbFail;

            $mailStatClass->save($mailStat);
        }

        if (!empty($subscriberIds)) {
            acym_query('UPDATE `#__acym_user` SET `last_sent_date` = '.acym_escapeDB($currentDate).' WHERE `id` IN ('.implode(',', $subscriberIds).')');
        }
    }

    private function triggerSendHook(int $mailId): void
    {
        static $triggered = [];
        if (!empty($triggered[$mailId])) {
            return;
        }

        $triggered[$mailId] = true;
        acym_triggerCmsHook('onAcymSendMail', [$mailId]);
    }

    /**
     * Function to delete elements from the queue
     * $queueDelete[mailId][] = subscriberId;
     */
    private function deleteQueue(array $queueDelete): bool
    {
        if (empty($queueDelete)) {
            return true;
        }

        $status = true;

        foreach ($queueDelete as $mailId => $userIds) {
            $nbUsers = count($userIds);
            $nbDeleted = $this->queueClass->deleteQueuedByUserIds($userIds, $mailId);

            if (!empty($this->queueClass->errors)) {
                $status = false;
                $this->displayMessage($this->queueClass->errors, self::MESSAGE_TYPE_ERROR);
            } else {
                // If we didn't delete the entry, it may have been done by another send process so we stop it immediately to avoid double send
                if ($nbDeleted !== $nbUsers) {
                    $status = false;
                    $this->displayMessage(
                        [$nbDeleted < $nbUsers ? acym_translation('ACYM_QUEUE_DOUBLE') : $nbDeleted.' emails deleted from the queue whereas we only have '.$nbUsers.' subscribers'],
                        self::MESSAGE_TYPE_ERROR
                    );
                }
            }
        }

        return $status;
    }

    /**
     * Function to update elements in the queue
     * $queueUpdate[mailing][] = subid;
     */
    private function queueUpdate(array $queueUpdate): void
    {
        if (empty($queueUpdate)) {
            return;
        }

        //Delay to requeue the e-mail in seconds
        foreach ($queueUpdate as $mailid => $subscribers) {
            $this->queueClass->delayFailed($mailid, $subscribers);
        }
    }

    private function handleError(): void
    {
        $this->finish = true;
        $message = acym_translation('ACYM_SEND_STOPED');
        $message .= '<br />';
        $message .= acym_translation('ACYM_SEND_KEPT_ALL');
        $message .= '<br />';
        //ERROR 1 : no e-mail sent at all...
        if ($this->report) {
            if (empty($this->successSend) && empty($this->start)) {
                $message .= acym_translation('ACYM_SEND_CHECKONE');
                $message .= '<br />';
                $message .= acym_translation('ACYM_SEND_ADVISE_LIMITATION');
            } else {
                //We used to send e-mails but now we can not any more... sounds like a server limitation!
                $message .= acym_translation('ACYM_SEND_REFUSE');
                $message .= '<br />';
                if (!acym_level(ACYM_ESSENTIAL)) {
                    $message .= acym_translation('ACYM_SEND_CONTINUE_COMMERCIAL');
                } else {
                    $message .= acym_translation('ACYM_SEND_CONTINUE_AUTO');
                }
            }
        }

        $this->displayMessage([$message], self::MESSAGE_TYPE_WARNING);
    }

    /**
     * If num is empty then it's a message otherwise it's a send status
     */
    private function displayMessage(array $messages, int $status, int $num = 0): void
    {
        foreach ($messages as $message) {
            $this->messages[] = strip_tags($message);
        }

        if (!$this->report) {
            return;
        }

        $color = $status === self::MESSAGE_TYPE_SUCCESS ? 'green' : ($status === self::MESSAGE_TYPE_WARNING ? 'orange' : 'red');
        foreach ($messages as $message) {
            if (!empty($num)) {
                echo '<br />'.$num.' : <span style="color:'.$color.';">'.$message.'</span>';
            } else {
                echo '<script type="text/javascript" language="javascript">setInfo(\''.addslashes($message).'\')</script>';
            }
        }

        if (function_exists('ob_flush')) {
            @ob_flush();
        }
        if (!$this->mod_security2) {
            @flush();
        }
    }

    private function failedActions(int $userId): string
    {
        $actionMaxTries = $this->config->get('bounce_action_maxtry');
        if (in_array($actionMaxTries, ['sub', 'remove', 'unsub'])) {
            $subscriptions = $this->userClass->getUserSubscriptionById($userId);
        }

        $listId = 0;
        $message = '';
        switch ($actionMaxTries) {
            case 'sub':
                $listId = $this->config->get('bounce_action_lists_maxtry');
                if (!empty($listId)) {
                    $message .= ' user '.$userId.' subscribed to list n°'.$listId;
                    $this->userClass->subscribe([$userId], [$listId]);
                }
            // There is no break here as we will remove the user from the other lists
            case 'remove':
                $unsubLists = array_diff(array_keys($subscriptions), [$listId]);
                if (!empty($unsubLists)) {
                    $message .= ' user '.$userId.' removed from lists '.implode(',', $unsubLists);
                    $this->userClass->removeSubscription([$userId], $unsubLists);
                } else {
                    $message .= ' user '.$userId.' not subscribed';
                }
                break;
            case 'unsub':
                $unsubLists = array_diff(array_keys($subscriptions), [$listId]);
                if (!empty($unsubLists)) {
                    $message .= ' user '.$userId.' unsubscribed from lists '.implode(',', $unsubLists);
                    $this->userClass->unsubscribe([$userId], $unsubLists);
                } else {
                    $message .= ' user '.$userId.' not unsubscribed';
                }
                break;
            case 'delete':
                $message .= ' user '.$userId.' deleted';
                $this->userClass->delete([$userId]);
                break;
            case 'block':
                $message .= ' user '.$userId.' blocked';
                $this->userClass->deactivate($userId);
                // We delete any other e-mail from the queue as well
                $this->queueClass->deleteQueuedByUserIds([$userId]);
                break;
        }

        return $message;
    }
}
