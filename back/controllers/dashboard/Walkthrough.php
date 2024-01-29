<?php

namespace AcyMailing\Controllers\Dashboard;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Controllers\ConfigurationController;
use AcyMailing\Controllers\MailsController;
use AcyMailing\Helpers\EditorHelper;
use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Helpers\UpdateHelper;
use AcyMailing\Helpers\UpdatemeHelper;

trait Walkthrough
{
    public function stepSubscribe()
    {
        acym_setVar('layout', 'walk_through');

        $data = [
            'step' => 'subscribe',
            'email' => acym_currentUserEmail(),
        ];

        parent::display($data);
    }

    public function saveStepSubscribe()
    {
        $this->saveWalkthrough(['step' => 'stepLicense']);
        $this->stepLicense();
    }

    public function stepEmail()
    {
        acym_setVar('layout', 'walk_through');

        $walkthroughParams = json_decode($this->config->get('walkthrough_params', '[]'), true);

        $mailClass = new MailClass();
        $updateHelper = new UpdateHelper();

        $mail = empty($walkthroughParams['mail_id'])
            ? $mailClass->getOneByName(acym_translation($updateHelper::FIRST_EMAIL_NAME_KEY))
            : $mailClass->getOneById(
                $walkthroughParams['mail_id']
            );

        if (empty($mail)) {
            if (!$updateHelper->installNotifications()) {
                $this->stepSubscribe();

                return;
            }
            $mail = $mailClass->getOneByName(acym_translation($updateHelper::FIRST_EMAIL_NAME_KEY));
        }

        $editor = new EditorHelper();
        $editor->content = $mail->body;
        $editor->autoSave = '';
        $editor->settings = $mail->settings;
        $editor->stylesheet = $mail->stylesheet;
        $editor->editor = 'acyEditor';
        $editor->mailId = $mail->id;
        $editor->walkThrough = true;

        $data = [
            'step' => 'email',
            'editor' => $editor,
            'social_icons' => $this->config->get('social_icons', '{}'),
            'mail' => $mail,
            'mailClass' => $mailClass,
        ];

        parent::display($data);
    }

    public function saveAjax()
    {
        $mailController = new MailsController();
        $result = $mailController->store(true);

        if ($result) {
            acym_sendAjaxResponse('', ['result' => $result]);
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR_SAVING'), [], false);
        }
    }

    public function saveStepEmail()
    {
        $mailController = new MailsController();

        $mailId = $mailController->store();

        if (empty($mailId)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
            $this->passWalkThrough();
        } else {
            $this->saveWalkthrough(['step' => 'stepList', 'mail_id' => $mailId]);
            $this->stepList();
        }
    }

    public function stepList()
    {
        acym_setVar('layout', 'walk_through');
        $listClass = new ListClass();
        $walkthroughParams = json_decode($this->config->get('walkthrough_params', '[]'), true);

        $users = empty($walkthroughParams['list_id'])
            ? []
            : $listClass->getSubscribersForList(
                [
                    'listIds' => [$walkthroughParams['list_id']],
                    'offset' => 0,
                    'limit' => 500,
                ]
            );
        $usersReturn = [];
        if (!empty($users)) {
            foreach ($users as $user) {
                $usersReturn[] = $user->email;
            }
        }

        if (empty($usersReturn)) $usersReturn[] = acym_currentUserEmail();

        $data = [
            'step' => 'list',
            'users' => $usersReturn,
        ];

        parent::display($data);
    }

    public function saveStepList()
    {
        $walkthroughParams = json_decode($this->config->get('walkthrough_params', '[]'), true);
        if (empty($walkthroughParams['list_id'])) {
            $testingList = new \stdClass();
            $testingList->name = acym_translation('ACYM_TESTING_LIST');
            $testingList->visible = 0;
            $testingList->active = 1;
            $testingList->color = '#94d4a6';

            $listClass = new ListClass();
            $listId = $listClass->save($testingList);
        } else {
            $listId = $walkthroughParams['list_id'];
        }

        if (empty($listId)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVE_LIST'), 'error');
            $this->passWalkThrough();

            return;
        }

        $userClass = new UserClass();

        $addresses = acym_getVar('array', 'addresses', []);
        $addresses = array_unique($addresses);
        $wrongAddresses = [];
        foreach ($addresses as $oneAddress) {
            if (!acym_isValidEmail($oneAddress)) {
                $wrongAddresses[] = $oneAddress;
                continue;
            }

            $existing = $userClass->getOneByEmail($oneAddress);
            if (empty($existing)) {
                $newUser = new \stdClass();
                $newUser->email = $oneAddress;
                $newUser->confirmed = 1;
                acym_setVar('acy_source', 'walkthrough');
                $userId = $userClass->save($newUser);
            } else {
                $userId = $existing->id;
            }

            $userClass->subscribe($userId, $listId);
        }

        if (!empty($wrongAddresses)) acym_enqueueMessage(acym_translationSprintf('ACYM_WRONG_ADDRESSES', implode(', ', $wrongAddresses)), 'warning');

        $nextStep = 'stepPhpmail';

        if (!empty($this->config->get('acymailer_apikey'))) {
            $nextStep = 'stepAcyMailer';
        }

        $this->saveWalkthrough(['step' => $nextStep, 'list_id' => $listId]);
        $this->$nextStep();
    }

    public function stepLicense()
    {
        acym_setVar('layout', 'walk_through');
        $licenseKey = $this->config->get('license_key', '');

        if (!empty($licenseKey)) {
            // Go to next step
            $this->passStepLicence();

            return;
        }

        $data = [
            'step' => 'license',
            'version' => $this->config->get('version', ''),
            'level' => $this->config->get('level', ''),
        ];

        parent::display($data);
    }

    //Function called in Ajax that's why we exit
    public function stepLicenseAttachLicense()
    {
        $licenseKey = acym_getVar('string', 'licenseKey', '');

        if (empty($licenseKey)) {
            acym_sendAjaxResponse(acym_translation('ACYM_LICENSE_NOT_FOUND'), [], false);
        }

        $this->config->save(['license_key' => $licenseKey]);

        $configurationController = new ConfigurationController();
        $return = $configurationController->attachLicenseOnUpdateMe($licenseKey);

        if (empty($return['success'])) {
            $this->config->save(['license_key' => '']);
        }

        $originalMessage = $return['message'];
        $return['message'] = $configurationController->displayMessage($originalMessage, true);
        $ajaxSuccess = !empty($return['message']['type']) && $return['message']['type'] !== 'error';

        acym_sendAjaxResponse($return['message']['message'] ?? $originalMessage, [], $ajaxSuccess);
    }

    //Function called in Ajax that's why we exit
    public function stepLicenseActivateCron()
    {
        $licenseKey = acym_getVar('string', 'licenseKey', '');

        if (empty($licenseKey)) {
            acym_sendAjaxResponse(acym_translation('ACYM_LICENSE_NOT_FOUND'), [], false);
        }

        $data = [
            'domain' => ACYM_LIVE,
            'cms' => ACYM_CMS,
            'version' => $this->config->get('version', ''),
            'level' => $this->config->get('level', ''),
            'activate' => true,
            'url_version' => 'secured',
        ];
        $result = UpdatemeHelper::call('api/crons/modify', 'POST', $data);

        if ($result['type'] !== 'error') {
            $this->config->save(['active_cron' => 1]);
        }

        $configurationController = new ConfigurationController();
        $result['message'] = $configurationController->displayMessage($result['message'], true);
        $success = $result['message']['type'] !== 'error';

        acym_sendAjaxResponse($result['message']['message'], [], $success);
    }

    public function passStepLicence()
    {
        $nextStep = 'stepEmail';
        $this->saveWalkthrough(['step' => $nextStep]);
        $this->$nextStep();
    }

    public function stepPhpmail()
    {
        acym_setVar('layout', 'walk_through');

        $data = [
            'step' => 'phpmail',
            'userEmail' => $this->config->get('from_email', acym_currentUserEmail()),
            'siteName' => $this->config->get('from_name', acym_getCMSConfig('sitename')),
        ];

        $data['sendingMethods'] = [];

        acym_trigger('onAcymGetSendingMethods', [&$data]);
        acym_trigger('onAcymGetSendingMethodsSelected', [&$data]);

        $data['sendingMethodsHtmlSettings'] = [];
        acym_trigger('onAcymGetSendingMethodsHtmlSetting', [&$data]);

        if (!empty($this->errorMailer)) $data['error'] = $this->errorMailer;

        parent::display($data);
    }

    public function loginForAuth2()
    {
        $configurationController = new ConfigurationController();
        $configurationController->loginForAuth2();
    }

    public function saveStepPhpmail()
    {
        if (!$this->saveFrom()) {
            $this->stepPhpmail();

            return;
        }
        $config = acym_getVar('array', 'config', []);

        if (empty($config)) $config = ['mailer_method' => 'phpmail'];

        if (false === $this->config->save($config)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING', 'error'));
            $this->stepPhpmail();

            return;
        }

        if (false === $this->sendFirstEmail()) {
            $this->stepPhpmail();

            return;
        }

        $this->saveWalkthrough(['step' => 'stepResult']);
        $this->stepResult();
    }

    public function stepResult()
    {
        acym_setVar('layout', 'walk_through');

        $data = [
            'step' => 'result',
        ];

        parent::display($data);
    }

    public function saveStepResult()
    {
        $result = acym_getVar('boolean', 'result');

        $walkthroughParams = json_decode($this->config->get('walkthrough_params', '[]'), true);

        $stepFail = !empty($walkthroughParams['step_fail']) ? 'stepFaillocal' : 'stepFail';

        $nextStep = $result ? 'stepSuccess' : $stepFail;
        $this->saveWalkthrough(['step' => $nextStep]);

        $this->$nextStep();
    }

    public function stepSuccess()
    {
        acym_setVar('layout', 'walk_through');

        $data = [
            'step' => 'success',
        ];

        parent::display($data);
    }

    public function saveStepSuccess()
    {
        $this->passWalkThrough();
    }

    public function stepFaillocal()
    {
        acym_setVar('layout', 'walk_through');
        $data = [
            'step' => 'faillocal',
            'email' => acym_currentUserEmail(),
        ];
        parent::display($data);
    }

    public function saveStepFaillocal()
    {
        $this->_handleContactMe('stepFaillocal');
    }

    public function stepFail()
    {
        acym_setVar('layout', 'walk_through');

        $data = [
            'step' => 'fail',
            'email' => acym_currentUserEmail(),
        ];

        parent::display($data);
    }

    public function saveStepFail()
    {
        $this->_handleContactMe('stepFail');
    }

    private function _handleContactMe($fromFunction)
    {
        $email = acym_getVar('string', 'email');
        if (empty($email) || !acym_isValidEmail($email)) {
            acym_enqueueMessage(acym_translation('ACYM_PLEASE_ADD_YOUR_EMAIL'), 'error');
            $this->$fromFunction();

            return;
        }

        if ($fromFunction == 'stepFaillocal') {
            $fromMessage = 'GMAIL_PHP_TRY';
        } else {
            $fromMessage = $this->config->get('mailer_method', 'phpmail');
        }

        $data = [
            'email' => $email,
            'cms' => ACYM_CMS,
            'version' => $this->config->get('version', '6'),
            'message_key' => $fromMessage,
        ];

        $output = UpdatemeHelper::call('public/sendHelpMail', 'GET', $data);

        if ($output['success'] === false) {
            acym_enqueueMessage(acym_translation('ACYM_SOMETHING_WENT_WRONG_CONTACT_ON_ACYBA'), 'error');
            $this->passWalkThrough();
        } else {
            $this->saveWalkthrough(['step' => 'stepSupport']);
            $this->stepSupport();
        }
    }

    public function stepSupport()
    {
        acym_setVar('layout', 'walk_through');

        $data = [
            'step' => 'support',
        ];

        parent::display($data);
    }

    public function saveStepSupportImport()
    {
        $this->passWalkThrough();
    }

    public function saveStepSupport()
    {
        $this->passWalkThrough();
    }

    public function saveStepSupportSubForm()
    {
        $this->passWalkThrough('forms&task=newForm');
    }

    public function passWalkThrough($page = '')
    {
        $newConfig = new \stdClass();

        if (empty($page)) {
            $page = 'users&task=import';
        }

        if (acym_getVar('cmd', 'skip') === 'acymailer') {
            // If it's the SMTP method, we already copied the site's configuration
            $mailMethod = acym_getCMSConfig('mailer', 'phpmail');
            $data['sendingMethods'] = [];
            acym_trigger('onAcymGetSendingMethods', [&$data]);
            if (!in_array($mailMethod, array_keys($data['sendingMethods']))) {
                $mailMethod = 'phpmail';
            }
            $newConfig->mailer_method = $mailMethod;
            $page = 'configuration';
        }

        $newConfig->walk_through = 0;
        $this->config->save($newConfig);

        acym_redirect(acym_completeLink($page, false, true));
    }

    public function walkthrough()
    {
        if ($this->config->get('walk_through') == 1) {
            $walkthroughParams = json_decode($this->config->get('walkthrough_params', '[]'), true);
            if (empty($walkthroughParams['step'])) {
                $this->stepSubscribe();
            } else {
                $this->{$walkthroughParams['step']}();
            }

            return true;
        }

        return false;
    }

    /**
     * Save 'from name' and 'from address mail' on creating email step in walkthrough
     *
     * @return bool
     */
    private function saveFrom()
    {
        $fromName = acym_getVar('string', 'from_name', 'Test');
        $fromAddress = acym_getVar('string', 'from_address', 'test@test.com');

        $mailClass = new MailClass();
        $updateHelper = new UpdateHelper();

        $firstMail = $mailClass->getOneByName(acym_translation($updateHelper::FIRST_EMAIL_NAME_KEY));

        if (empty($firstMail)) {
            acym_enqueueMessage(acym_translation('ACYM_PLEASE_REINSTALL_ACYMAILING'), 'error');

            return false;
        }

        $newConfig = [
            'from_name' => $fromName,
            'from_email' => $fromAddress,
            'replyto_name' => $fromName,
            'replyto_email' => $fromAddress,
        ];

        $this->config->save($newConfig);

        $firstMail->from_name = $fromName;
        $firstMail->from_email = $fromAddress;

        if ($this->config->get('replyto_email') === '') {
            $firstMail->reply_to_name = $fromName;
            $firstMail->reply_to_email = $fromAddress;
        }

        $statusSaveMail = $mailClass->save($firstMail);

        if (empty($statusSaveMail)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');

            return false;
        }

        return true;
    }

    /**
     * Send the first email
     *
     * @return bool
     */
    private function sendFirstEmail(): bool
    {
        $walkthroughParams = json_decode($this->config->get('walkthrough_params', '[]'), true);
        $listClass = new ListClass();
        $mailClass = new MailClass();
        $updateHelper = new UpdateHelper();
        $mailerHelper = new MailerHelper();

        $testingList = empty($walkthroughParams['list_id'])
            ? $listClass->getOneByName(acym_translation('ACYM_TESTING_LIST'))
            : $listClass->getOneById(
                $walkthroughParams['list_id']
            );
        $firstMail = empty($walkthroughParams['mail_id'])
            ? $mailClass->getOneByName(acym_translation($updateHelper::FIRST_EMAIL_NAME_KEY))
            : $mailClass->getOneById(
                $walkthroughParams['mail_id']
            );

        if (empty($testingList)) {
            acym_enqueueMessage(acym_translation('ACYM_CANT_RETRIEVE_TESTING_LIST'), 'error');

            return false;
        }

        if (empty($firstMail)) {
            acym_enqueueMessage(acym_translation('ACYM_CANT_RETRIEVE_TEST_EMAIL'), 'error');
        }

        $subscribersTestingListIds = $listClass->getSubscribersIdsById($testingList->id);

        $nbSent = 0;
        foreach ($subscribersTestingListIds as $subscriberId) {
            if ($mailerHelper->sendOne($firstMail->id, $subscriberId, true)) $nbSent++;
        }

        if (!empty($mailerHelper->ErrorInfo)) {
            $this->errorMailer = $mailerHelper->ErrorInfo;
        }

        return $nbSent !== 0;
    }

    private function saveWalkthrough($params)
    {
        $newParams = json_decode($this->config->get('walkthrough_params', '[]'), true);
        foreach ($params as $key => $value) {
            $newParams[$key] = $value;
        }
        $this->config->save(['walkthrough_params' => json_encode($newParams)]);
    }

    public function stepAcyMailer()
    {
        acym_setVar('layout', 'walk_through');

        $spinner = '<i class="acymicon-circle-o-notch acymicon-spin"></i>';

        $data = [
            'step' => 'acymailer',
            'userEmail' => $this->config->get('from_email', acym_currentUserEmail()),
            'siteName' => $this->config->get('from_name', acym_getCMSConfig('sitename')),
            'domain' => empty($_SERVER['HTTP_HOST']) ? '' : $_SERVER['HTTP_HOST'],
            'CnameRecords' => [['name' => $spinner, 'value' => $spinner]],
            'status' => 'PENDING',
        ];

        if (!empty($data['userEmail'])) {
            $data['domain'] = acym_getDomain($data['userEmail']);
        }

        $data['sentDomains'] = $this->config->get('acymailer_domains', []);
        if (!empty($data['sentDomains'])) {
            $data['sentDomains'] = @json_decode($data['sentDomains'], true);
        }

        if (!empty($data['sentDomains'][$data['domain']]['CnameRecords'])) {
            $data['CnameRecords'] = $data['sentDomains'][$data['domain']]['CnameRecords'];
            $data['status'] = $data['sentDomains'][$data['domain']]['status'];
        }

        if (!empty($this->errorMailer)) {
            $data['error'] = $this->errorMailer;
        }

        parent::display($data);
    }

    public function saveStepAcyMailer()
    {
        if (!$this->saveFrom()) {
            $this->stepAcyMailer();

            return;
        }

        if (false === $this->sendFirstEmail()) {
            $this->stepAcyMailer();

            return;
        }

        $this->saveWalkthrough(['step' => 'stepResult']);
        $this->stepResult();
    }
}
