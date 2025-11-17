<?php

namespace AcyMailing\Helpers;

use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\MailArchiveClass;
use AcyMailing\Classes\MailboxClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\QueueClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\UserStatClass;
use AcyMailing\Core\AcymObject;

class CronHelper extends AcymObject
{
    const STEP_SCHEDULE = 'schedule';
    const STEP_CLEAN_QUEUE = 'cleanqueue';
    const STEP_SEND = 'send';
    const STEP_BOUNCE = 'bounce';
    const STEP_AUTOMATION = 'automation';
    const STEP_CAMPAIGN = 'campaign';
    const STEP_SPECIFIC = 'specific';
    const STEP_FOLLOWUP = 'followup';
    const STEP_DELETE_HISTORY = 'delete_history';
    const STEP_CLEAN_DATA_EXTERNAL_SENDING_METHOD = 'clean_data_external_sending_method';
    const STEP_CLEAN_EXPORT_CHANGES = 'clean_export_changes';
    const STEP_MAILBOX_ACTION = 'mailbox_action';
    const STEP_ABTEST = 'abtest';
    const STEP_SCENARIO = 'scenario';

    const ALL_STEPS = [
        self::STEP_SCHEDULE,
        self::STEP_CLEAN_QUEUE,
        self::STEP_SEND,
        self::STEP_BOUNCE,
        self::STEP_AUTOMATION,
        self::STEP_CAMPAIGN,
        self::STEP_SPECIFIC,
        self::STEP_FOLLOWUP,
        self::STEP_DELETE_HISTORY,
        self::STEP_CLEAN_DATA_EXTERNAL_SENDING_METHOD,
        self::STEP_CLEAN_EXPORT_CHANGES,
        self::STEP_MAILBOX_ACTION,
        self::STEP_ABTEST,
        self::STEP_SCENARIO,
    ];

    const SEND_REPORT_NO = 0;
    const SEND_REPORT_EACH_TIME = 1;
    const SEND_REPORT_ONLY_ON_ACTION = 2;
    const SEND_REPORT_ONLY_ON_ERROR = 3;

    private array $messages = [];
    private array $detailMessages = [];
    private array $emailTypes = [];
    private array $skip = [];

    private int $cronTimeLimit = 0;
    private bool $cronTimeLimitReached = false;
    private int $startQueue;

    // Save the main message
    private string $mainMessage = '';

    // Did the cron process anything?
    private bool $processed = false;

    // Is there any error in the process
    private bool $errorDetected = false;

    // If we call the cron just to send a batch of emails
    private bool $externalSendingActivated = false;
    private bool $externalSendingRepeat;
    private bool $externalSendingNotFinished = false;
    private bool $isSendingCall = false;

    public function __construct()
    {
        parent::__construct();

        acym_trigger('onAcymProcessQueueExternalSendingCampaign', [&$this->externalSendingActivated]);

        $callType = acym_getVar('string', 'type', '');
        $this->startQueue = acym_getVar('int', 'startqueue', 0);
        $this->externalSendingRepeat = !empty(acym_getVar('int', 'external_sending_repeat', 0));

        if ($callType === 'sending' || !empty($this->startQueue) || !empty($this->externalSendingRepeat)) {
            $this->skip = array_diff(self::ALL_STEPS, [self::STEP_SEND]);
            $this->isSendingCall = true;
        }
    }

    public function addSkipFromString(string $skipVar): void
    {
        if (empty($skipVar)) {
            return;
        }

        $skipVar = explode(',', $skipVar);

        if (empty($skipVar)) {
            return;
        }

        $this->skip = array_unique(array_merge($this->skip, $skipVar));
    }

    public function cron(): void
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            exit;
        }
        //__END__demo_

        $this->handleUnlinkLicenseCalls();
        $this->triggeredMessage();
        if (!$this->checkCronFrequency()) {
            return;
        }

        $this->queueScheduledCampaigns();
        $this->cleanQueue();
        $this->handleQueue();
        $this->handleBounceMessages();
        $this->handleAutomations();
        $this->handleAutomaticCampaigns();
        $this->handleSpecificEmails();
        $this->handleFollowups();
        $this->handleMailboxActions();
        $this->cleanData();
        $this->handleABTestCampaigns();
        $this->handleScenario();
        $this->continueCronCall();
        $this->handleCronReport();
    }

    public function saveReport(array $messages = [], array $detailMessages = []): void
    {
        $saveReport = $this->config->get('cron_savereport');
        $reportPath = $this->config->get('cron_savepath');

        if (empty($saveReport) || empty($reportPath)) {
            return;
        }

        if (!(($saveReport == 2 && $this->processed) || $saveReport == 1 || ($saveReport == 3 && $this->errorDetected))) {
            return;
        }

        if (!empty($messages)) {
            $this->messages = $messages;
        }

        if (!empty($detailMessages)) {
            $this->detailMessages = $detailMessages;
        }

        // Prepare the cron file path
        $reportPath = str_replace(['{year}', '{month}'], [date('Y'), date('m')], $reportPath);
        $reportPath = acym_cleanPath(ACYM_ROOT.trim(html_entity_decode($reportPath)));
        acym_createDir(dirname($reportPath), true, true);

        $lr = "\r\n";
        // Catch warnings
        ob_start();
        file_put_contents(
            $reportPath,
            $lr.$lr.'******************** '.acym_getDate(time()).' UTC ********************'.$lr.implode($lr, $this->messages),
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

    /**
     * Sets the types of emails that will be sent by the cron
     */
    public function setEmailTypes(array $emailTypes): void
    {
        $this->emailTypes = $emailTypes;
    }

    public function addMessage(string $message): void
    {
        $this->messages[] = $message;
    }

    public function handleCronReport(): void
    {
        $sendReport = (int)$this->config->get('cron_sendreport');

        if (
            ($sendReport === self::SEND_REPORT_ONLY_ON_ACTION && $this->processed)
            || $sendReport === self::SEND_REPORT_EACH_TIME
            || ($sendReport === self::SEND_REPORT_ONLY_ON_ERROR && $this->errorDetected)
        ) {
            $mailerHelper = new MailerHelper();
            $mailerHelper->report = false;
            $mailerHelper->autoAddUser = true;
            $mailerHelper->addParam('report', implode('<br />', $this->messages));
            $mailerHelper->addParam('mainreport', $this->mainMessage);
            $mailerHelper->addParam('detailreport', implode('<br />', $this->detailMessages));

            $receiverString = $this->config->get('cron_sendto');
            $receivers = [];
            if (substr_count($receiverString, '@') > 1) {
                $receivers = explode(' ', trim(preg_replace('# +#', ' ', str_replace([';', ','], ' ', $receiverString))));
            } else {
                $receivers[] = trim($receiverString);
            }

            if (!empty($receivers)) {
                $mailClass = new MailClass();
                foreach ($receivers as $oneReceiver) {
                    if (empty($oneReceiver)) {
                        continue;
                    }

                    try {
                        $reportEmail = $mailClass->getOneByName('acy_report');
                        if (!empty($reportEmail)) {
                            $mailerHelper->sendOne($reportEmail->id, $oneReceiver);
                        }
                    } catch (\Exception $e) {
                        acym_logError('Error while sending the cron report to '.$oneReceiver.' : '.$e->getMessage());
                    }
                }
            }
        }

        $this->saveReport();

        $newConfig = [
            'cron_report' => implode("\n", $this->messages),
        ];

        if (strlen($newConfig['cron_report']) > 800) {
            $newConfig['cron_report'] = substr($newConfig['cron_report'], 0, 795).'...';
        }

        $this->config->saveConfig($newConfig);
    }

    /**
     * Call made by the API to remove the API key when the user unlinks the website from their account page on our website
     */
    private function handleUnlinkLicenseCalls(): void
    {
        if (acym_getVar('int', 'unlink', 0) !== 1) {
            return;
        }

        $callLicenseKey = acym_getVar('string', 'licenseKey');
        $configLicenseKey = $this->config->get('license_key');

        if (!empty($configLicenseKey) && $configLicenseKey === $callLicenseKey) {
            $this->config->saveConfig(['license_key' => '', 'active_cron' => 0]);
        }

        exit;
    }

    /**
     * Displays the message shown on the cron URL page
     */
    private function triggeredMessage(): void
    {
        $firstMessage = acym_translationSprintf('ACYM_CRON_TRIGGERED', acym_date('now', 'd F Y H:i'));
        $this->messages[] = $firstMessage;
        acym_display($firstMessage, 'info');
    }

    /**
     * Checks the cron frequency in the configuration to make sure the call is wanted
     */
    private function checkCronFrequency(): bool
    {
        if ($this->isSendingCall) {
            return true;
        }

        $time = time();
        $nextCronTime = $this->config->get('cron_next', 0);
        $cronFrequency = $this->config->get('cron_frequency', 900);

        if ($nextCronTime > $time) {
            if ($nextCronTime > ($time + $cronFrequency)) {
                // The next cron time is too far in the future, should not happen but we'll handle the case, so we reset the next cron time
                $this->config->saveConfig(['cron_next' => $time + $cronFrequency]);
            }

            $notTimeMessage = acym_translationSprintf('ACYM_CRON_NEXT', acym_date($this->config->get('cron_next'), 'd F Y H:i'));
            $this->messages[] = $notTimeMessage;
            // We don't need to trigger anything, it's not time yet
            acym_display($notTimeMessage, 'info');

            return false;
        }

        // We update the next cron and the last cron dates
        $newConfig = [
            'cron_last' => $time,
            'cron_fromip' => acym_getIP(),
            'cron_next' => $nextCronTime + $cronFrequency,
        ];

        if ($newConfig['cron_next'] <= $time || $newConfig['cron_next'] > $time + $cronFrequency) {
            $newConfig['cron_next'] = $time + $cronFrequency;
        }

        $this->config->saveConfig($newConfig);

        return true;
    }

    /**
     * Adds the scheduled emails to the queue when the date is reached
     */
    private function queueScheduledCampaigns(): void
    {
        if (in_array(self::STEP_SCHEDULE, $this->skip)) {
            return;
        }

        $queueClass = new QueueClass();
        $nbScheduled = $queueClass->scheduleReady();
        if (!empty($nbScheduled)) {
            $this->messages[] = acym_translationSprintf('ACYM_NB_SCHEDULED', $nbScheduled);
            $this->detailMessages = array_merge($this->detailMessages, $queueClass->messages);
            $this->processed = true;
        }
    }

    /**
     * Cleans the emails that are stuck in the queue for too long (disabled/unconfirmed recipients)
     */
    private function cleanQueue(): void
    {
        if (in_array(self::STEP_CLEAN_QUEUE, $this->skip)) {
            return;
        }

        $queueClass = new QueueClass();
        $deletedNb = $queueClass->cleanQueue();

        if (!empty($deletedNb)) {
            $this->messages[] = acym_translationSprintf('ACYM_EMAILS_REMOVED_QUEUE_CLEAN', $deletedNb);
            $this->processed = true;
        }
    }

    private function handleQueue(): void
    {
        if (in_array(self::STEP_SEND, $this->skip) || !$this->canSendQueuedEmails()) {
            return;
        }

        if ($this->isSendingCall || !acym_level(ACYM_ENTERPRISE) || !function_exists('fsockopen')) {
            $this->sendQueuedEmails();
        } else {
            $this->handleMultiCron();
        }
    }

    /**
     * Makes sure the current process doesn't reach the server time limit
     */
    private function checkTimeRemaining(): void
    {
        $time = time();
        if (empty($this->cronTimeLimit) || $time < $this->cronTimeLimit) {
            return;
        }

        $this->messages[] = acym_translation('ACYM_CRON_TIME_LIMIT_REACHED');

        $this->cronTimeLimitReached = true;
    }

    /**
     * If the configuration is set to send multiple batches of emails at the same time
     */
    private function handleMultiCron(): void
    {
        $emailsPerBatches = $this->config->get('queue_nbmail_auto', 70);
        if (empty($emailsPerBatches)) {
            return;
        }

        $cronParams = '&t='.time();
        if (!empty($this->config->get('cron_security', 0)) && !empty($this->config->get('cron_key'))) {
            $cronParams .= '&cronKey='.$this->config->get('cron_key');
        }

        $nbBatches = $this->config->get('queue_batch_auto', 1);
        $nbBatches = intval($nbBatches);

        $urls = [];
        for ($i = 0; $i < $nbBatches; $i++) {
            $urls[] = acym_frontendLink('cron&task=cron&type=sending&startqueue='.($emailsPerBatches * $i).$cronParams);
        }

        acym_asyncUrlCalls($urls);
    }

    private function handleBounceMessages(): void
    {
        $this->checkTimeRemaining();
        $time = time();
        $autoBounceHandlingActive = (int)$this->config->get('auto_bounce', 0) === 1;
        $autoBounceHandlingNextTime = (int)$this->config->get('auto_bounce_next', 0);
        $autoBounceHandlingFrequency = (int)$this->config->get('auto_bounce_frequency', 0);
        $isEnterprise = acym_level(ACYM_ENTERPRISE);

        if (in_array(self::STEP_BOUNCE, $this->skip) || $this->cronTimeLimitReached || !$isEnterprise || !$autoBounceHandlingActive || $time < $autoBounceHandlingNextTime) {
            return;
        }

        $this->config->saveConfig(
            [
                'auto_bounce_last' => $time,
                'auto_bounce_next' => $time + $autoBounceHandlingFrequency,
            ]
        );

        $bounceHelper = new BounceHelper();
        $bounceHelper->report = false;
        $bounceHelper->stoptime = $this->cronTimeLimit;

        $newConfig = [];
        if ($bounceHelper->init() && $bounceHelper->connect()) {
            $nbMessages = $bounceHelper->getNBMessages();
            $nbMessagesReport = acym_translationSprintf('ACYM_NB_MAIL_MAILBOX', $nbMessages);
            $this->messages[] = $nbMessagesReport;
            $newConfig['auto_bounce_report'] = $nbMessagesReport;
            $this->detailMessages[] = $nbMessagesReport;
            if (!empty($nbMessages)) {
                $bounceHelper->handleMessages();
                $this->processed = true;
            }
            $this->detailMessages = array_merge($this->detailMessages, $bounceHelper->messages);
        } else {
            $bounceErrors = $bounceHelper->getErrors();
            $newConfig['auto_bounce_report'] = implode('<br />', $bounceErrors);
            //We add "bounce handling" just before the error so the user knows where it comes from...
            if (!empty($bounceErrors[0])) {
                $bounceErrors[0] = acym_translation('ACYM_BOUNCE_HANDLING').' : '.$bounceErrors[0];
            }
            $this->messages = array_merge($this->messages, $bounceErrors);
            $this->processed = true;
            $this->errorDetected = true;
        }

        $this->config->saveConfig($newConfig);
    }

    /**
     * Triggers the automations based on time frequency
     */
    private function handleAutomations(): void
    {
        $this->checkTimeRemaining();
        if (in_array(self::STEP_AUTOMATION, $this->skip) || $this->cronTimeLimitReached || !acym_level(ACYM_ENTERPRISE)) {
            return;
        }

        $automationClass = new AutomationClass();
        $automationClass->trigger('classic');

        $userStatusCheckTriggers = [];
        acym_trigger('onAcymDefineUserStatusCheckTriggers', [&$userStatusCheckTriggers]);
        $automationClass->trigger($userStatusCheckTriggers);

        if (!empty($automationClass->report)) {
            if ($automationClass->didAnAction) {
                $this->processed = true;
            }
            $this->messages = array_merge($this->messages, $automationClass->report);
        }
    }

    private function handleAutomaticCampaigns(): void
    {
        $this->checkTimeRemaining();
        if (in_array(self::STEP_CAMPAIGN, $this->skip) || $this->cronTimeLimitReached || !acym_level(ACYM_ENTERPRISE)) {
            return;
        }

        $campaignClass = new CampaignClass();
        $campaignClass->triggerAutoCampaign();

        if (!empty($campaignClass->messages)) {
            $this->messages = array_merge($this->messages, $campaignClass->messages);
            $this->processed = true;
        }
    }

    /**
     * Sends special emails such as birthday or WooCommerce reminders
     */
    private function handleSpecificEmails(): void
    {
        $this->checkTimeRemaining();
        if (in_array(self::STEP_SPECIFIC, $this->skip) || $this->cronTimeLimitReached) {
            return;
        }

        $campaignClass = new CampaignClass();
        $specialTypes = [];

        acym_trigger('getCampaignTypes', [&$specialTypes]);

        $specialMail = $campaignClass->getCampaignsByTypes($specialTypes, true);
        $time = time();

        acym_trigger('filterSpecificMailsToSend', [&$specialMail, $time]);

        foreach ($specialMail as $onespecialMail) {
            $campaignClass->send($onespecialMail->id);
        }
    }

    /**
     * Sends follow-ups based on time frequency like birthdays
     */
    private function handleFollowups(): void
    {
        $this->checkTimeRemaining();
        if (in_array(self::STEP_FOLLOWUP, $this->skip) || $this->cronTimeLimitReached) {
            return;
        }

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
                if (!empty($followup->condition)) {
                    $followup->condition = json_decode($followup->condition, true);
                }

                acym_trigger('onAcymFollowupDailyBasesNeedToBeTriggered', [$followup]);
            }
        }
    }

    /**
     * Checks messages received on mailboxes and triggers actions accordingly
     */
    private function handleMailboxActions(): void
    {
        $this->checkTimeRemaining();
        if (in_array(self::STEP_MAILBOX_ACTION, $this->skip) || $this->cronTimeLimitReached || !acym_level(ACYM_ENTERPRISE)) {
            return;
        }

        $mailboxActionClass = new MailboxClass();
        $mailboxes = $mailboxActionClass->getAllActiveReadyWithActions();

        if (empty($mailboxes)) {
            return;
        }

        $mailboxHelper = new MailboxHelper();
        $mailboxHelper->report = false;
        foreach ($mailboxes as $oneMailboxAction) {
            $this->processed = true;

            if (!$mailboxHelper->isConnectionValid($oneMailboxAction, false)) {
                $this->messages[] = acym_translationSprintf('ACYM_CONNECTION_FAILED_MAILBOX_X', $oneMailboxAction->id.'-'.$oneMailboxAction->name);
                $this->errorDetected = true;
                continue;
            }

            $nbMessages = $mailboxHelper->getNBMessages();
            if (!$nbMessages) {
                $this->messages[] = acym_translationSprintf('ACYM_NO_MESSAGE_IN_MAILBOX_X', $oneMailboxAction->id.'-'.$oneMailboxAction->name);
                $mailboxHelper->close();
                continue;
            }

            $mailboxHelper->handleAction();
            $mailboxHelper->close();


            // Update next trigger
            $oneMailboxAction->nextdate = time() + $oneMailboxAction->frequency;
            unset($oneMailboxAction->conditions);
            unset($oneMailboxAction->actions);
            $mailboxActionClass->save($oneMailboxAction);
        }
    }

    private function cleanData(): void
    {
        // Clean the history and detailed stats based on the configuration
        if (!in_array(self::STEP_DELETE_HISTORY, $this->skip) && $this->isDailyCron()) {
            $userStatClass = new UserStatClass();
            $userDetailedStatsDeleted = $userStatClass->deleteDetailedStatsPeriod();
            if (!empty($userDetailedStatsDeleted['message'])) {
                $this->messages[] = $userDetailedStatsDeleted['message'];
                $this->processed = true;
            }

            $userClass = new UserClass();
            $userHistoryDeleted = $userClass->deleteHistoryPeriod();
            if (!empty($userHistoryDeleted['message'])) {
                $this->messages[] = $userHistoryDeleted['message'];
                $this->processed = true;
            }

            $mailArchiveClass = new MailArchiveClass();
            $archiveDeleted = $mailArchiveClass->deleteArchivePeriod();
            if (!empty($archiveDeleted['message'])) {
                $this->messages[] = $archiveDeleted['message'];
                $this->processed = true;
            }
        }

        // Clean data on external sending method
        if (!in_array(self::STEP_CLEAN_DATA_EXTERNAL_SENDING_METHOD, $this->skip) && $this->isDailyCron()) {
            acym_trigger('onAcymCleanDataExternalSendingMethod');
        }

        // Clean export changes
        if (!in_array(self::STEP_CLEAN_EXPORT_CHANGES, $this->skip) && $this->isDailyCron()) {
            $exportHelper = new ExportHelper();
            $exportHelper->cleanExportChangesFile();
        }

        if ($this->isDailyCron()) {
            $time = time();
            $this->config->saveConfig(['cron_last_daily' => $time]);
        }
    }

    private function isDailyCron(): bool
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
        $lastCronDayBasedOnCMSTimezone = acym_date($this->config->get('cron_last_daily', $time - 86400), 'Y-m-d');

        return $time >= $dayBasedOnCMSTimezoneAtSpecifiedHour && $lastCronDayBasedOnCMSTimezone != $dayBasedOnCMSTimezone;
    }

    private function handleABTestCampaigns(): void
    {
        $this->checkTimeRemaining();
        if (in_array(self::STEP_ABTEST, $this->skip) || $this->cronTimeLimitReached) {
            return;
        }

        $campaignClass = new CampaignClass();
        $abTestCampaigns = $campaignClass->getAllAbTestCampaignsToFinishSending();
        foreach ($abTestCampaigns as $campaign) {
            if ($campaignClass->finishAbTestCampaign($campaign)) {
                $this->messages[] = acym_translationSprintf('ACYM_ABTEST_CAMPAIGN_X_FINAL_VERSION_SENT', $campaign->id);
                $this->processed = true;
            }
        }
    }

    private function handleScenario(): void
    {
        $this->checkTimeRemaining();
        if (in_array(self::STEP_SCENARIO, $this->skip) || $this->cronTimeLimitReached) {
            return;
        }

        $scenarioHelper = new ScenarioHelper();
        $scenarioHelper->executeAvailableSteps();
        $scenarioHelper->triggerTimeScenarios();
    }

    /**
     * We reached the maximum time allowed so the tasks are not finished, we begin a new process to finish it up
     */
    private function continueCronCall(): void
    {
        if ($this->externalSendingNotFinished) {
            $cronKey = '';
            if (!empty($this->config->get('cron_security', 0)) && !empty($this->config->get('cron_key'))) {
                $cronKey = '&cronKey='.$this->config->get('cron_key');
            }
            acym_makeCurlCall(acym_frontendLink('cron&task=cron&external_sending_repeat=1&t='.time().$cronKey), ['verifySsl' => false]);
        }
    }

    private function canSendQueuedEmails(): bool
    {
        if ($this->config->get('queue_type') === 'manual') {
            return false;
        }

        $dayOfWeek = acym_date('now', 'N');
        if ($this->config->get('queue_stop_weekend', 0) && $dayOfWeek >= 6) {
            return false;
        }

        // Make sure we are within the sending hours defined in the configuration
        $fromHour = $this->config->get('queue_send_from_hour', '00');
        $fromMinute = $this->config->get('queue_send_from_minute', '00');
        $toHour = $this->config->get('queue_send_to_hour', '23');
        $toMinute = $this->config->get('queue_send_to_minute', '59');
        $time = time();

        if ($fromHour != '00' || $fromMinute != '00' || $toHour != '23' || $toMinute != '59') {
            // The day it is currently based on the timezone specified in the CMS configuration
            $dayBasedOnCMSTimezone = acym_date('now', 'Y-m-d');
            // The UTC timestamp of the current day based on the CMS timezone, at the specified hour
            $fromBasedOnCMSTimezoneAtSpecifiedHour = acym_getTimeFromCMSDate($dayBasedOnCMSTimezone.' '.$fromHour.':'.$fromMinute);
            $toBasedOnCMSTimezoneAtSpecifiedHour = acym_getTimeFromCMSDate($dayBasedOnCMSTimezone.' '.$toHour.':'.$toMinute);
            // In case we want to send during the night and the FROM is superior to the TO (from 8pm to 4am), we should change the day of ones of the limits
            if ($fromBasedOnCMSTimezoneAtSpecifiedHour > $toBasedOnCMSTimezoneAtSpecifiedHour) {
                // TO becomes tomorrow as we are not passed midnight
                if ($time > $fromBasedOnCMSTimezoneAtSpecifiedHour) {
                    $toBasedOnCMSTimezoneAtSpecifiedHour = acym_getTimeFromCMSDate(acym_date('tomorrow', 'Y-m-d').' '.$toHour.':'.$toMinute);
                } elseif ($time < $toBasedOnCMSTimezoneAtSpecifiedHour) {
                    // FROM becomes yesterday as we are passed midnight
                    $fromBasedOnCMSTimezoneAtSpecifiedHour = acym_getTimeFromCMSDate(acym_date('yesterday', 'Y-m-d').' '.$fromHour.':'.$fromMinute);
                }
            }

            if ($time < $fromBasedOnCMSTimezoneAtSpecifiedHour || $time > $toBasedOnCMSTimezoneAtSpecifiedHour) {
                return false;
            }
        }

        return true;
    }

    private function sendQueuedEmails(): void
    {
        $queueHelper = new QueueHelper();
        $queueHelper->send_limit = (int)$this->config->get('queue_nbmail_auto');
        $queueHelper->report = false;
        $queueHelper->emailTypes = $this->emailTypes;
        $queueHelper->startSendingFrom = $this->startQueue;
        $queueHelper->process();

        if (!empty($queueHelper->messages)) {
            $this->detailMessages = array_merge($this->detailMessages, $queueHelper->messages);
        }

        if (!empty($queueHelper->nbprocess)) {
            $this->processed = true;

            if (!$queueHelper->finish && $this->externalSendingActivated) {
                $this->externalSendingNotFinished = true;
            }
        }

        $this->mainMessage = acym_translationSprintf(
            'ACYM_CRON_PROCESS',
            $queueHelper->nbprocess,
            $queueHelper->successSend,
            $queueHelper->errorSend
        );
        $this->messages[] = $this->mainMessage;

        if (!empty($queueHelper->errorSend)) {
            $this->errorDetected = true;
        }

        $this->cronTimeLimit = $queueHelper->stoptime;
    }
}
