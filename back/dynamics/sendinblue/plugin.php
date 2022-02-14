<?php

use AcyMailing\Classes\MailClass;
use AcyMailing\Libraries\acymPlugin;

class plgAcymSendinblue extends acymPlugin
{
    const SENDING_METHOD_ID = 'sendinblue';
    const SENDING_METHOD_NAME = 'Sendinblue';
    const SENDING_METHOD_API_URL = 'https://api.sendinblue.com/v3/';

    var $credentials;
    var $integration;
    var $transactional;
    var $campaign;
    var $list;
    var $users;
    var $sender;

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

        $this->credentials = new SendinblueCredentials($this);
        $headers = $this->credentials->getHeadersSendingMethod(self::SENDING_METHOD_ID);

        $this->integration = new SendinblueIntegration($this);
        $this->transactional = new SendinblueTransactional($this, $headers);
        $this->list = new SendinblueList($this, $headers);
        $this->users = new SendinblueUsers($this, $headers, $this->list);
        $this->sender = new SendinblueSender($this, $headers);
        $this->campaign = new SendinblueCampaign($this, $headers, $this->sender, $this->users, $this->list);
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/sendinblue.png',
            'premium' => true,
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

    public function onAcymSendEmail(&$response, $sendingMethod, $to, $subject, $from, $reply_to, $body, $bcc = [], $attachments = [], $mailId = null)
    {
        $this->transactional->sendTransactionalEmail($response, $sendingMethod, $to, $subject, $from, $reply_to, $body, $bcc = [], $attachments = []);
    }

    public function onAcymGetCreditRemainingSendingMethod(&$html)
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

    public function onAcymRegisterReceiverContentAndList(&$result, $htmlContent, $receiverEmail, $mailId, &$warnings)
    {
        if ($this->config->get('mailer_method') != self::SENDING_METHOD_ID) return;

        $result = $this->users->addUserToList($receiverEmail, $mailId, $warnings);
        // The API returns null every time so we have no clue if this went well
        $this->users->addAttributeToUser($receiverEmail, $htmlContent, $mailId);
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

    public function onAcymSendCampaignOnExternalSendingMethod($mailId)
    {
        if ($this->config->get('mailer_method') != self::SENDING_METHOD_ID) return true;
        $mailClass = new MailClass();
        $mail = $mailClass->getOneById($mailId, true);

        if (empty($mail)) return false;

        $this->campaign->createNewCampaign($mail);

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

    public function onAcymCleanDataExternalSendingMethod()
    {
        if ($this->config->get('mailer_method') != self::SENDING_METHOD_ID) return true;

        return $this->campaign->cleanCampaigns();
    }

    public function onAcymSendingMethodEmbedAttachment(&$data)
    {
        $data['embedAttachment'][self::SENDING_METHOD_ID] = false;
    }

    public function onAcymSynchronizeExistingUsers($sendingMethod)
    {
        if ($sendingMethod !== self::SENDING_METHOD_ID) return;

        $this->users->synchronizeExistingUsers();
    }
}
