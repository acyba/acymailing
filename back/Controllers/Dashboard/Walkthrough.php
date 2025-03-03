<?php

namespace AcyMailing\Controllers\Dashboard;

use AcyMailing\Classes\MailClass;
use AcyMailing\Controllers\ConfigurationController;
use AcyMailing\Controllers\MailsController;
use AcyMailing\Helpers\EditorHelper;
use AcyMailing\Helpers\UpdateHelper;
use AcyMailing\Helpers\UpdatemeHelper;
use AcyMailing\Types\StepsType;

trait Walkthrough
{
    public function walkthrough(): bool
    {
        if ($this->config->get('walk_through', 0) != 1) {
            return false;
        }

        $walkthroughParams = json_decode($this->config->get('walkthrough_params', '[]'), true);
        if (empty($walkthroughParams['step']) || !method_exists($this, $walkthroughParams['step'])) {
            $this->stepThankYou();
        } else {
            $this->{$walkthroughParams['step']}();
        }

        return true;
    }

    public function stepThankYou(): void
    {
        acym_setVar('layout', 'step_thank_you');

        $data = [
            'level' => $this->config->get('level'),
        ];

        parent::display($data);
    }

    public function saveStepThankYou(): void
    {
        $this->saveWalkthrough(['step' => 'stepSenderInformation']);
        $this->stepSenderInformation();
    }

    public function stepSenderInformation(): void
    {
        acym_setVar('layout', 'step_sender_information');

        $data = [
            'stepsType' => new StepsType(),
            'siteName' => $this->config->get('from_name', acym_getCMSConfig('sitename')),
            'userEmail' => $this->config->get('from_email', acym_currentUserEmail()),
        ];

        parent::display($data);
    }

    public function saveStepSenderInformation(): void
    {
        $newConfiguration = [
            'from_name' => acym_getVar('string', 'from_name'),
            'from_email' => acym_getVar('string', 'from_email'),
        ];

        if (empty($newConfiguration['from_name']) || empty($newConfiguration['from_email'])) {
            acym_enqueueMessage(acym_translation('ACYM_FILL_ALL_INFORMATION'), 'error');
            $this->stepSenderInformation();

            return;
        }

        $this->config->save($newConfiguration);

        $this->saveWalkthrough(['step' => 'stepLicense']);
        $this->stepLicense();
    }

    public function stepLicense(): void
    {
        acym_setVar('layout', 'step_license');

        $data = [
            'stepsType' => new StepsType(),
            'level' => $this->config->get('level'),
        ];

        parent::display($data);
    }

    public function ajaxAttachLicense(): void
    {
        acym_checkToken();

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

    public function ajaxActivateCron(): void
    {
        acym_checkToken();

        $licenseKey = $this->config->get('license_key');
        if (empty($licenseKey)) {
            acym_sendAjaxResponse(acym_translation('ACYM_LICENSE_NOT_FOUND'), [], false);
        }

        $data = [
            'domain' => ACYM_LIVE,
            'cms' => ACYM_CMS,
            'version' => $this->config->get('version', ''),
            'level' => $this->config->get('level', ''),
            'activate' => true,
        ];
        $result = UpdatemeHelper::call('api/crons/modify', 'POST', $data);

        $configurationController = new ConfigurationController();
        $result['message'] = $configurationController->displayMessage($result['message'] ?? 'CRON_NOT_SAVED', true);
        $success = !empty($result['success']);

        if ($success) {
            $this->config->save(['active_cron' => 1]);
        }

        acym_sendAjaxResponse($result['message']['message'], [], $success);
    }

    public function saveStepLicense(): void
    {
        $this->saveWalkthrough(['step' => 'stepFinal']);
        $this->stepFinal();
    }

    public function stepFinal(): void
    {
        $acyMailerApiKey = $this->config->get('acymailer_apikey');
        acym_setVar('layout', empty($acyMailerApiKey) ? 'step_final' : 'step_acymailer');

        $data = [
            'stepsType' => new StepsType(),
            'pricingPage' => ACYM_ACYMAILING_WEBSITE.'pricing',
        ];

        if (!empty($acyMailerApiKey)) {
            $data['suggestedDomain'] = acym_getDomain($this->config->get('from_email', acym_currentUserEmail()));
        }

        parent::display($data);
    }

    public function startUsing(bool $campaigns = false): void
    {
        $this->config->save(['walk_through' => 0]);
        $redirectUrl = $campaigns ? 'campaigns' : 'users&task=import';
        acym_redirect(acym_completeLink($redirectUrl, false, true));
    }

    public function tryEditor(): void
    {
        $this->saveWalkthrough(['step' => 'stepEditor']);
        $this->stepEditor();
    }

    public function stepEditor(): void
    {
        acym_setVar('layout', 'step_editor');

        $mailClass = new MailClass();
        $mail = $mailClass->getOneByName(acym_translation(UpdateHelper::FIRST_EMAIL_NAME_KEY));

        if (empty($mail)) {
            $updateHelper = new UpdateHelper();
            $updateHelper->installNotifications();
            $mail = $mailClass->getOneByName(acym_translation(UpdateHelper::FIRST_EMAIL_NAME_KEY));
            if (empty($mail)) {
                $this->startUsing(true);

                return;
            }
        }

        $editorHelper = new EditorHelper();
        $editorHelper->content = $mail->body;
        $editorHelper->autoSave = '';
        $editorHelper->settings = $mail->settings;
        $editorHelper->stylesheet = $mail->stylesheet;
        $editorHelper->editor = 'acyEditor';
        $editorHelper->mailId = $mail->id;
        $editorHelper->walkThrough = true;

        $data = [
            'mail' => $mail,
            'social_icons' => $this->config->get('social_icons', '{}'),
            'editor' => $editorHelper,
        ];

        parent::display($data);
    }

    public function saveAjax(): void
    {
        $mailController = new MailsController();
        $mailId = $mailController->store(true);

        if (!empty($mailId)) {
            acym_sendAjaxResponse('', ['result' => $mailId]);
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR_SAVING'), [], false);
        }
    }

    private function saveWalkthrough(array $params): void
    {
        $newParams = json_decode($this->config->get('walkthrough_params', '[]'), true);
        foreach ($params as $key => $value) {
            $newParams[$key] = $value;
        }
        $this->config->save(['walkthrough_params' => json_encode($newParams)]);
    }
}
