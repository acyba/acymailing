<?php

namespace AcyMailing\Helpers;

use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\QueueClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\UserStatClass;
use AcyMailing\Libraries\acymObject;

class CronHelper extends acymObject
{
    var $report = false;
    var $messages = [];
    var $detailMessages = [];

    // Did the cron process anything?
    var $processed = false;

    // Has the cron system been executed or did we block it?
    var $executed = false;

    // Save the main message
    var $mainmessage = '';

    // Is there any error in the process
    var $errorDetected = false;

    // Process we should skip... bounce,send,filters,schedule,autonews,plugins
    var $skip = [];

    // Type of emails we should send... news, followup
    var $emailtypes = [];

    // If we call the cron just to send a batch of emails
    var $startQueue = 0;

    // If we call the cron just to send a batch of emails
    var $cronLastOnCron = 0;

    public function __construct()
    {
        parent::__construct();
        $this->startQueue = acym_getVar('int', 'startqueue', 0);
        if (!empty($this->startQueue)) $this->skip = ['schedule', 'cleanqueue', 'bounce', 'automation', 'campaign', 'specific', 'followup', 'delete_history'];
    }

    public function cron()
    {
        // Step 1: Check the last cron launched...
        $time = time();

        //If we do not have run the cron we set it to yesterday so it will be trigger
        $this->cronLastOnCron = $this->config->get('cron_last', $time - 86400);

        $firstMessage = acym_translationSprintf('ACYM_CRON_TRIGGERED', acym_date('now', 'd F Y H:i'));
        $this->messages[] = $firstMessage;
        if ($this->report) {
            acym_display($firstMessage, 'info');
        }

        if (empty($this->startQueue)) {
            if ($this->config->get('cron_next') > $time) {
                if ($this->config->get('cron_next') > ($time + $this->config->get('cron_frequency'))) {
                    //There is something wrong here... so we put back the normal time
                    $newConfig = new \stdClass();
                    $newConfig->cron_next = $time + $this->config->get('cron_frequency');
                    $this->config->save($newConfig);
                }

                $nottime = acym_translationSprintf('ACYM_CRON_NEXT', acym_date($this->config->get('cron_next'), 'd F Y H:i'));
                $this->messages[] = $nottime;
                if ($this->report) {
                    //We dont need to trigger anything, it's not time to do it.
                    acym_display($nottime, 'info');
                }

                return false;
            }

            // Step 2: we update the next cron and the last cron dates
            $newConfig = new \stdClass();
            $newConfig->cron_last = $time;
            $newConfig->cron_fromip = acym_getIP();
            $newConfig->cron_next = $this->config->get('cron_next') + $this->config->get('cron_frequency');

            //We update the next cron properly
            if ($newConfig->cron_next <= $time || $newConfig->cron_next > $time + $this->config->get('cron_frequency')) {
                $newConfig->cron_next = $time + $this->config->get('cron_frequency');
            }

            $this->config->save($newConfig);
        }

        // We should trigger the cron now...
        $this->executed = true;


        // Step 3: Enqueue the scheduled campaigns
        if (!in_array('schedule', $this->skip)) {
            $queueClass = new QueueClass();
            $nbScheduled = $queueClass->scheduleReady();
            if ($nbScheduled) {
                $this->messages[] = acym_translationSprintf('ACYM_NB_SCHEDULED', $nbScheduled);
                $this->detailMessages = array_merge($this->detailMessages, $queueClass->messages);
                $this->processed = true;
            }
        }

        // Step 4: Clean the queue
        if (!in_array('cleanqueue', $this->skip)) {
            $deletedNb = $queueClass->cleanQueue();

            if (!empty($deletedNb)) {
                $this->messages[] = acym_translationSprintf('ACYM_EMAILS_REMOVED_QUEUE_CLEAN', $deletedNb);
                $this->processed = true;
            }
        }

        // Step 5: We send the queued emails that are ready
        if ($this->config->get('queue_type') != 'manual' && !in_array('send', $this->skip)) {
            $this->multiCron();
            $queueHelper = new QueueHelper();
            $queueHelper->send_limit = (int)$this->config->get('queue_nbmail_auto');
            $queueHelper->report = false;
            $queueHelper->emailtypes = $this->emailtypes;
            $queueHelper->process();
            if (!empty($queueHelper->messages)) {
                $this->detailMessages = array_merge($this->detailMessages, $queueHelper->messages);
            }
            if (!empty($queueHelper->nbprocess)) {
                $this->processed = true;
            }
            $this->mainmessage = acym_translationSprintf('ACYM_CRON_PROCESS', $queueHelper->nbprocess, $queueHelper->successSend, $queueHelper->errorSend);
            $this->messages[] = $this->mainmessage;

            if (!empty($queueHelper->errorSend)) {
                $this->errorDetected = true;
            }
            //Check on the time limitation so we stop the process if we reached it
            if (!empty($queueHelper->stoptime) && time() > $queueHelper->stoptime) {
                return true;
            }
        }

        // Step 6: run automatic bounce handling!
        if (!in_array('bounce', $this->skip) && acym_level(2) && $this->config->get('auto_bounce', 0) && $time > (int)$this->config->get(
                'auto_bounce_next',
                0
            ) && (empty($queueHelper->stoptime) || time() < $queueHelper->stoptime - 5)) {

            //First we update the config
            $newConfig = new \stdClass();
            $newConfig->auto_bounce_next = $time + (int)$this->config->get('auto_bounce_frequency', 0);
            $newConfig->auto_bounce_last = $time;
            $this->config->save($newConfig);
            $bounceHelper = new BounceHelper();
            $bounceHelper->report = false;
            $queueHelper = new QueueHelper();
            $bounceHelper->stoptime = $queueHelper->stoptime;
            $newConfig = new \stdClass();
            if ($bounceHelper->init() && $bounceHelper->connect()) {
                $nbMessages = $bounceHelper->getNBMessages();
                $nbMessagesReport = acym_translationSprintf('ACYM_NB_MAIL_MAILBOX', $nbMessages);
                $this->messages[] = $nbMessagesReport;
                $newConfig->auto_bounce_report = $nbMessagesReport;
                $this->detailMessages[] = $nbMessagesReport;
                if (!empty($nbMessages)) {
                    $bounceHelper->handleMessages();
                    $this->processed = true;
                }
                $this->detailMessages = array_merge($this->detailMessages, $bounceHelper->messages);
            } else {
                $bounceErrors = $bounceHelper->getErrors();
                $newConfig->auto_bounce_report = implode('<br />', $bounceErrors);
                //We add "bounce handling" just before the error so the user knows where it comes from...
                if (!empty($bounceErrors[0])) {
                    $bounceErrors[0] = acym_translation('ACYM_BOUNCE_HANDLING').' : '.$bounceErrors[0];
                }
                $this->messages = array_merge($this->messages, $bounceErrors);
                $this->processed = true;
                $this->errorDetected = true;
            }
            $this->config->save($newConfig);

            //Check on the time limitation so we stop the process if we reached it
            if (!empty($queueHelper->stoptime) && time() > $queueHelper->stoptime) {
                return true;
            }
        }

        // Step 7: Automations
        if (!in_array('automation', $this->skip) && acym_level(2)) {
            $automationClass = new AutomationClass();
            $automationClass->trigger('classic');

            $userStatusCheckTriggers = [];
            acym_trigger('onAcymDefineUserStatusCheckTriggers', [&$userStatusCheckTriggers]);
            $automationClass->trigger($userStatusCheckTriggers);

            if (!empty($automationClass->report)) {
                if ($automationClass->didAnAction) $this->processed = true;
                $this->messages = array_merge($this->messages, $automationClass->report);
            }

            //Check on the time limitation so we stop the process if we reached it
            if (!empty($queueHelper->stoptime) && time() > $queueHelper->stoptime) {
                return true;
            }
        }

        // Step 8: Automatic campaign
        if (!in_array('campaign', $this->skip) && acym_level(2)) {
            $campaignClass = new CampaignClass();
            $campaignClass->triggerAutoCampaign();
            if (!empty($campaignClass->messages)) {
                $this->messages = array_merge($this->messages, $campaignClass->messages);
                $this->processed = true;
            }
        }

        // Step 9: Specific emails
        if (!in_array('specific', $this->skip)) {
            $campaignClass = new CampaignClass();
            $specialTypes = [];
            acym_trigger('getCampaignTypes', [&$specialTypes]);
            $specialMail = $campaignClass->getCampaignsByTypes($specialTypes, true);
            acym_trigger('filterSpecificMailsToSend', [&$specialMail, $time]);
            foreach ($specialMail as $onespecialMail) {
                $campaignClass->send($onespecialMail->id);
            }
        }

        //Step 10 followups with trigger like birthday (1 per call day)
        if (!in_array('followup', $this->skip)) {
            $followupClass = new FollowupClass();
            $followups = $followupClass->getFollowupDailyBases();

            // Only once a day
            $dailyHour = $this->config->get('daily_hour', '12');
            $dailyMinute = $this->config->get('daily_minute', '00');
            // The day it is currently based on the timezone specified in the CMS configuration
            $dayBasedOnCMSTimezone = acym_date('now', 'Y-m-d');
            // The UTC timestamp of the current day based on the CMS timezone, at the specified hour
            $dayBasedOnCMSTimezoneAtSpecifiedHour = acym_getTimeFromCMSDate($dayBasedOnCMSTimezone.' '.$dailyHour.':'.$dailyMinute);
            $time = time();

            foreach ($followups as $followup) {
                $lastTrigger = empty($followup->last_trigger) ? '' : acym_date($followup->last_trigger, 'Y-m-d');
                if ($time >= $dayBasedOnCMSTimezoneAtSpecifiedHour && $lastTrigger != $dayBasedOnCMSTimezone) {
                    if (!empty($followup->condition)) $followup->condition = json_decode($followup->condition, true);
                    acym_trigger('onAcymFollowupDailyBasesNeedToBeTriggered', [$followup]);
                }
            }
        }

        // Step 11: Delete data
        if (!in_array('delete_history', $this->skip) && $this->isDailyCron()) {
            $userStatsClass = new UserStatClass();
            $userClass = new UserClass();

            $userDetailedStatsDeleted = $userStatsClass->deleteDetailedStatsPeriod();
            $userHistoryDeleted = $userClass->deleteHistoryPeriod();
            if (!empty($userDetailedStatsDeleted['message'])) {
                $this->messages[] = $userDetailedStatsDeleted['message'];
                $this->processed = true;
            }
            if (!empty($userHistoryDeleted['message'])) {
                $this->messages[] = $userHistoryDeleted['message'];
                $this->processed = true;
            }
        }

        return true;
    }

    public function report()
    {
        //Send the report
        $sendreport = $this->config->get('cron_sendreport');
        $mailer = new MailerHelper();

        if (($sendreport == 2 && $this->processed) || $sendreport == 1 || ($sendreport == 3 && $this->errorDetected)) {
            $mailer->report = false;
            $mailer->autoAddUser = true;
            $mailer->checkConfirmField = false;
            $mailer->addParam('report', implode('<br />', $this->messages));
            $mailer->addParam('mainreport', $this->mainmessage);
            $mailer->addParam('detailreport', implode('<br />', $this->detailMessages));

            $receiverString = $this->config->get('cron_sendto');
            $receivers = [];
            if (substr_count($receiverString, '@') > 1) {
                $receivers = explode(' ', trim(preg_replace('# +#', ' ', str_replace([';', ','], ' ', $receiverString))));
            } else {
                $receivers[] = trim($receiverString);
            }

            if (!empty($receivers)) {
                foreach ($receivers as $oneReceiver) {
                    $mailer->sendOne('acy_report', $oneReceiver);
                }
            }
        }

        if (!$this->executed) {
            return;
        }

        if ($this->processed) {
            $this->saveReport();
        }

        $newConfig = new \stdClass();
        $newConfig->cron_report = implode("\n", $this->messages);
        if (strlen($newConfig->cron_report) > 800) {
            $newConfig->cron_report = substr($newConfig->cron_report, 0, 795).'...';
        }
        $this->config->save($newConfig);
    }

    public function saveReport()
    {
        $saveReport = $this->config->get('cron_savereport');
        $reportPath = $this->config->get('cron_savepath');
        if (empty($saveReport) || empty($reportPath)) return;

        // Prepare the cron file path
        $reportPath = str_replace(['{year}', '{month}'], [date('Y'), date('m')], $reportPath);
        $reportPath = acym_cleanPath(ACYM_ROOT.trim(html_entity_decode($reportPath)));
        acym_createDir(dirname($reportPath), true, true);

        $lr = "\r\n";
        // Catch warnings
        ob_start();
        file_put_contents(
            $reportPath,
            $lr.$lr.'********************     '.acym_getDate(time()).'     ********************'.$lr.implode($lr, $this->messages),
            FILE_APPEND
        );

        if ($saveReport == 2 && !empty($this->detailMessages)) {
            file_put_contents(
                $reportPath,
                $lr.'---- Details ----'.$lr.implode($lr, $this->detailMessages),
                FILE_APPEND
            );
        }
        $potentialWarnings = ob_get_clean();

        if (!empty($potentialWarnings)) {
            $this->messages[] = $potentialWarnings;
        }
    }

    private function multiCron()
    {
        if (!empty($this->startQueue) || !acym_level(2)) return;
        $emailsBatches = $this->config->get('queue_batch_auto', 1);
        $emailsBatches = intval($emailsBatches);
        $emailsPerBatches = $this->config->get('queue_nbmail_auto', 70);
        if ($emailsBatches < 2 || empty($emailsPerBatches)) return;

        $urls = [];

        for ($i = 1 ; $i <= $emailsBatches - 1 ; $i++) {
            $urls[] = acym_frontendLink('cron&startqueue='.($emailsPerBatches * $i));
        }

        acym_asyncCurlCall($urls);
    }

    private function isDailyCron()
    {

        // Only once a day
        $dailyHour = $this->config->get('daily_hour', '12');
        $dailyMinute = $this->config->get('daily_minute', '00');
        // The day it is currently based on the timezone specified in the CMS configuration
        $dayBasedOnCMSTimezone = acym_date('now', 'Y-m-d');
        // The UTC timestamp of the current day based on the CMS timezone, at the specified hour
        $dayBasedOnCMSTimezoneAtSpecifiedHour = acym_getTimeFromCMSDate($dayBasedOnCMSTimezone.' '.$dailyHour.':'.$dailyMinute);

        $time = time();

        //If we do not have run the cron we set it to yesterday so it will be trigger
        $lastCronDayBasedOnCMSTimezone = acym_date($this->cronLastOnCron, 'Y-m-d');

        return $time >= $dayBasedOnCMSTimezoneAtSpecifiedHour && $lastCronDayBasedOnCMSTimezone != $dayBasedOnCMSTimezone;
    }
}
