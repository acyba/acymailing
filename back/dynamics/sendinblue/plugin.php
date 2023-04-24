<?php

use AcyMailing\Classes\MailClass;
use AcyMailing\Libraries\acymPlugin;

class plgAcymSendinblue extends acymPlugin
{
    const SENDING_METHOD_ID = 'sendinblue';
    const SENDING_METHOD_NAME = 'Sendinblue';
    const SENDING_METHOD_API_URL = 'https://api.sendinblue.com/v3/';

    private $credentials;
    private $integration;
    private $transactional;
    private $campaign;
    private $list;
    private $users;
    private $sender;
    private $webhooks;

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = 'Sendinblue';

        include_once __DIR__.DS.'sendinblue.php';
        include_once __DIR__.DS.'credentials.php';
        include_once __DIR__.DS.'integration.php';
        include_once __DIR__.DS.'transactional.php';
        include_once __DIR__.DS.'campaign.php';
        include_once __DIR__.DS.'list.php';
        include_once __DIR__.DS.'users.php';
        include_once __DIR__.DS.'sender.php';
        include_once __DIR__.DS.'webhooks.php';

        $this->credentials = new SendinblueCredentials($this);
        $headers = $this->credentials->getHeadersSendingMethod(self::SENDING_METHOD_ID);

        $this->integration = new SendinblueIntegration($this);
        $this->transactional = new SendinblueTransactional($this, $headers);
        $this->list = new SendinblueList($this, $headers);
        $this->users = new SendinblueUsers($this, $headers, $this->list);
        $this->sender = new SendinblueSender($this, $headers);
        $this->campaign = new SendinblueCampaign($this, $headers, $this->sender, $this->users, $this->list);
        $this->webhooks = new SendinblueWebhooks($this, $headers);
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/sendinblue.png',
            'recommended' => true,
        ];
    }

    public function onAcymGetSendingMethodsHtmlSetting(&$data)
    {
        $this->credentials->getSendingMethodsHtmlSetting($data);
    }

    public function onAcymTestCredentialSendingMethod($sendingMethod, $credentials)
    {
        $this->credentials->testCredentialSendingMethod($sendingMethod, $credentials);
    }

    public function onAcymSendEmail(&$response, $mailerHelper, $to, $from, $reply_to, $bcc = [], $attachments = [])
    {
        $this->transactional->sendTransactionalEmail(
            $response,
            $mailerHelper->externalMailer,
            $to,
            $mailerHelper->Subject,
            $from,
            $reply_to,
            $mailerHelper->Body,
            $bcc,
            $attachments
        );
    }

    public function onAcymGetCreditRemainingSendingMethod(&$html, $reloading = false)
    {
        $this->credentials->getCreditRemainingSendingMethod($html);
    }

    public function onAcymGetSettingsSendingMethodFromPlugin(&$data, $plugin, $method)
    {
        $this->integration->getSettingsSendingMethodFromPlugin($data, $plugin, $method);
    }

    public function onAcymProcessQueueExternalSendingCampaign(&$externalSending, $transactional = false)
    {
        if ($this->config->get('mailer_method') == self::SENDING_METHOD_ID && !$transactional) $externalSending = true;
    }

    public function onAcymInitExternalSendingMethodBeforeSend(&$listId, $mailId)
    {
        if ($this->config->get('mailer_method') != self::SENDING_METHOD_ID) return;

        $this->list->getListExternalSendingMethod($listId, $mailId);
        $this->users->createAttribute($mailId);
    }

    public function onAcymRegisterReceiverContentAndList(&$result, $subjectContent, $htmlContent, $receiverEmail, $mailId, &$warnings)
    {
        if ($this->config->get('mailer_method') != self::SENDING_METHOD_ID) return;

        $result = $this->users->addUserToList($receiverEmail, $mailId, $warnings);
        if ($result) {
            // The API returns null every time so we have no clue if this went well
            $this->users->addAttributeToUser($receiverEmail, $subjectContent, $htmlContent, $mailId);
        }
    }

    public function onAcymAfterUserModify($user, &$oldUser)
    {
        $this->users->createUser($user);
    }

    public function onAcymAfterUserCreate($user)
    {
        $this->users->createUser($user);
    }

    public function onAcymUserImport($users)
    {
        $this->users->importUsers($users);
    }

    public function onAcymBeforeUserDelete($users)
    {
        $this->users->deleteUsers($users);
    }

    public function onAcymSendCampaignOnExternalSendingMethod($mailId, $content): bool
    {
        if ($this->config->get('mailer_method') != self::SENDING_METHOD_ID) return true;

        $mailClass = new MailClass();
        $mail = $mailClass->getOneById($mailId, true);

        if (empty($mail)) return false;

        $this->campaign->createNewCampaign($mail, $content);
        $this->webhooks->addWebhooks();

        return true;
    }

    public function onAcymAfterCMSUserImport($source)
    {
        $this->users->createFromImportedSource($source);
    }

    public function onAcymAfterDatabaseUserImport($source)
    {
        $this->users->createFromImportedSource($source);
    }

    public function onAcymCleanDataExternalSendingMethod(): bool
    {
        if ($this->config->get('mailer_method') != self::SENDING_METHOD_ID) return true;

        return $this->campaign->cleanCampaigns();
    }

    public function onAcymSynchronizeExistingUsers($sendingMethod)
    {
        if ($sendingMethod !== self::SENDING_METHOD_ID) return;

        $this->users->synchronizeExistingUsers();
    }

    public function onAcymResendCampaign($mailId)
    {
        if ($this->config->get('mailer_method') != self::SENDING_METHOD_ID) return;
        $this->users->removeUserFromList($mailId);
    }

    public function onAcymSendingMethodOptions(&$data)
    {
        $data['embedImage'][self::SENDING_METHOD_ID] = false;
        $data['embedAttachment'][self::SENDING_METHOD_ID] = false;
    }
}
