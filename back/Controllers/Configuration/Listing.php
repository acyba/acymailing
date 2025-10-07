<?php

namespace AcyMailing\Controllers\Configuration;

use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Helpers\EncodingHelper;
use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Helpers\TabHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Helpers\UpdatemeHelper;
use AcyMailing\Core\AcymPlugin;
use AcyMailing\Types\AclType;
use AcyMailing\Types\DelayType;
use AcyMailing\Types\FailActionType;

trait Listing
{
    public function listing(): void
    {
        acym_setVar('layout', 'listing');

        // We check if we have to store a token from an OAuth provider
        $this->handleOauthAuthentication();

        $data = [];
        $data['tab'] = new TabHelper();
        $this->prepareLanguages($data);
        $this->prepareLists($data);
        $this->prepareNotifications($data);
        $this->prepareAcl($data);
        $this->prepareClass($data);
        $this->prepareDataTab($data);
        $this->prepareSecurity($data);
        $this->checkConfigMail();
        $this->prepareToolbar($data);
        $this->prepareHoursMinutes($data);
        //__START__starter_
        $this->resetQueueProcess();
        //__END__starter_

        //__START__wordpress_
        if ($data['wp_mail_smtp_installed']) {
            $pluginClass = new AcymPlugin();
            $data['button_copy_settings_from'] = $pluginClass->getCopySettingsButton($data, 'from_options', 'wp_mail_smtp');
        }
        //__END__wordpress_

        $this->prepareMailSettings($data);
        $this->prepareMultilingualOption($data);

        $this->loadSurveyAnswers($data);

        parent::display($data);
    }

    private function checkConfigMail(): void
    {
        $queueType = $this->config->get('queue_type');
        $batchesNumber = $this->config->get('queue_batch_auto', 1);
        $emailsPerBatch = $this->config->get('queue_nbmail_auto', 70);
        $cronFrequency = $this->config->get('cron_frequency', 900);
        if ($queueType !== 'manual') {
            if (($batchesNumber > 1 || $cronFrequency < 900) && !function_exists('curl_multi_exec')) {
                $notification = [
                    'name' => 'curl_multi_exec',
                    'removable' => 0,
                ];
                acym_enqueueMessage(acym_translation('ACYM_NEED_CURL_MULTI'), 'error', true, [$notification]);
            } elseif ($batchesNumber <= 1 && $cronFrequency >= 900) {
                acym_removeDashboardNotification('curl_multi_exec');
            }

            $remindme = json_decode($this->config->get('remindme', '[]'), true);
            if (!in_array('sendoverload', $remindme) && ($batchesNumber > 4 || $emailsPerBatch > 300 || $cronFrequency < 300)) {
                $text = acym_translation('ACYM_SEND_CONFIGURATION_WARNING');
                $text .= '<p class="acym__do__not__remindme" title="sendoverload">'.acym_translation('ACYM_DO_NOT_REMIND_ME').'</p>';
                $notification = [
                    'name' => 'sendoverload',
                    'removable' => 1,
                ];
                acym_enqueueMessage($text, 'warning', true, [$notification]);
            } else {
                acym_removeDashboardNotification('sendoverload');
            }
        }
    }

    private function prepareMailSettings(array &$data): void
    {
        $data['sendingMethodsType'] = [
            'server' => acym_translation('ACYM_USING_YOUR_SERVER'),
            'external' => acym_translation('ACYM_USING_AN_EXTERNAL_SERVER'),
        ];

        $data['sendingMethods'] = [];
        acym_trigger('onAcymGetSendingMethods', [&$data]);
        acym_trigger('onAcymGetSendingMethodsSelected', [&$data]);

        $data['sendingMethodsHtmlSettings'] = [];
        acym_trigger('onAcymGetSendingMethodsHtmlSetting', [&$data]);

        $data['embedImage'] = [];
        $data['embedAttachment'] = [];
        acym_trigger('onAcymSendingMethodOptions', [&$data]);
    }

    public function prepareToolbar(array &$data): void
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addButton(
            acym_translation('ACYM_SEND_TEST'),
            [
                'acym-data-before' => 'jQuery.acymConfigSave();',
                'data-task' => 'test',
            ]
        );
        $toolbarHelper->addButton(
            acym_translation('ACYM_SAVE'),
            [
                'acym-data-before' => 'jQuery.acymConfigSave();',
                'data-task' => 'save',
            ],
            '',
            true
        );

        $data['toolbar'] = $toolbarHelper;
    }

    private function prepareClass(array &$data): void
    {
        $data['typeDelay'] = new DelayType();
        $data['failaction'] = new FailActionType();
        $data['encodingHelper'] = new EncodingHelper();
    }

    private function prepareLanguages(array &$data): void
    {
        $langs = acym_getLanguages();
        $data['languages'] = [];

        foreach ($langs as $lang => $obj) {
            if ($lang === 'xx-XX') continue;

            $oneLanguage = new \stdClass();
            $oneLanguage->language = $lang;
            $oneLanguage->name = $obj->name;

            $linkEdit = acym_completeLink('language&task=displayLanguage&code='.$lang, true);
            $icon = $obj->exists ? 'edit' : 'add';
            $idModalLanguage = 'acym_modal_language_'.$lang;
            $oneLanguage->edit = acym_modal(
                '<i class="acymicon-'.$icon.' cursor-pointer acym__color__blue" data-open="'.$idModalLanguage.'" data-ajax="false" data-iframe="'.$linkEdit.'" data-iframe-class="acym__iframe_language" id="image'.$lang.'"></i>',
                '', //<iframe src="'.$linkEdit.'"></iframe>
                $idModalLanguage,
                'data-reveal-larger',
                '',
                false
            );

            $data['languages'][] = $oneLanguage;
        }

        usort(
            $data['languages'],
            function ($a, $b) {
                return strtolower($a->name) > strtolower($b->name) ? 1 : -1;
            }
        );


        $data['content_translation'] = acym_getTranslationTools();

        $data['user_languages'] = array_merge(
            [
                (object)['language' => 'current_language', 'name' => acym_translation('ACYM_BROWSING_LANGUAGE')],
            ],
            $data['languages']
        );
    }

    private function prepareLists(array &$data): void
    {
        $listClass = new ListClass();
        try {
            $lists = $listClass->getAllWithoutManagement();
        } catch (\Exception $exception) {
            $lists = [];
        }
        foreach ($lists as $i => $oneList) {
            if ($oneList->active == 0) {
                unset($lists[$i]);
            }
        }
        $data['lists'] = $lists;
    }

    private function prepareNotifications(array &$data): void
    {
        $data['notifications'] = [
            'acy_notification_create' => [
                'label' => 'ACYM_NOTIFICATION_CREATE',
                'tooltip' => '',
            ],
            'acy_notification_unsub' => [
                'label' => 'ACYM_NOTIFICATION_UNSUB',
                'tooltip' => '',
            ],
            'acy_notification_unsuball' => [
                'label' => 'ACYM_NOTIFICATION_UNSUBALL',
                'tooltip' => '',
            ],
            'acy_notification_subform' => [
                'label' => 'ACYM_NOTIFICATION_SUBFORM',
                'tooltip' => '',
            ],
            'acy_notification_profile' => [
                'label' => 'ACYM_NOTIFICATION_PROFILE',
                'tooltip' => '',
            ],
            'acy_notification_confirm' => [
                'label' => 'ACYM_NOTIFICATION_CONFIRM',
                'tooltip' => '',
            ],
        ];
    }

    private function prepareAcl(array &$data): void
    {
        $data['acl'] = acym_cmsPermission();
        $data['acl_advanced'] = acym_getPagesForAcl();
        $data['aclType'] = new AclType();
    }

    private function prepareSecurity(array &$data): void
    {
        $data['wp_mail_smtp_installed'] = ACYM_CMS === 'wordpress' && acym_isExtensionActive('wp-mail-smtp/wp_mail_smtp.php');
        $data['acychecker_installed'] = acym_isAcyCheckerInstalled();
        $data['acychecker_get_link'] = ACYM_ACYCHECKER_WEBSITE.'?utm_source=acymailing_plugin&utm_campaign=get_acychecker&utm_medium=button_configuration_security';

        $data['level'] = acym_level(ACYM_ESSENTIAL);
        $data['labelDropdownCaptcha'] = acym_translation('ACYM_CONFIGURATION_CAPTCHA');

        $captchaOptions = array_replace(
            [
                'none' => acym_translation('ACYM_NONE'),
                'acym_hcaptcha' => acym_translation('ACYM_HCAPTCHA'),
                'acym_ireCaptcha' => acym_translation('ACYM_CAPTCHA_INVISIBLE'),
                'acym_reCaptcha_v3' => acym_translation('ACYM_CAPTCHA_V3'),
            ],
            acym_getCmsCaptcha()
        );

        $data['captchaOptions'] = $captchaOptions;

        if (!acym_level(ACYM_ESSENTIAL)) {
            $data['labelDropdownCaptcha'] .= ' '.acym_translation('ACYM_PRO_VERSION_ONLY');
            $data['captchaOptions'] = [];
        }
    }

    private function prepareDataTab(array &$data): void
    {
        $fieldClass = new FieldClass();
        $data['fields'] = $fieldClass->getAll();

        $data['export_data_changes_fields'] = $this->config->get('export_data_changes_fields', []);
        if (!is_array($data['export_data_changes_fields'])) {
            $data['export_data_changes_fields'] = explode(',', $data['export_data_changes_fields']);
        }
    }

    private function prepareHoursMinutes(array &$data): void
    {
        $listHours = [];
        for ($i = 0; $i < 24; $i++) {
            $value = $i < 10 ? '0'.$i : $i;
            $listHours[] = acym_selectOption($value, $value);
        }
        $listMinutes = [];
        for ($i = 0; $i < 60; $i += 5) {
            $value = $i < 10 ? '0'.$i : $i;
            $listMinutes[] = acym_selectOption($value, $value);
        }
        $listAllMinutes = [];
        for ($i = 0; $i < 60; $i++) {
            $value = $i < 10 ? '0'.$i : $i;
            $listAllMinutes[] = acym_selectOption($value, $value);
        }
        $data['listHours'] = $listHours;
        $data['listMinutes'] = $listMinutes;
        $data['listAllMinutes'] = $listAllMinutes;
    }

    private function handleEmails(array &$formData): void
    {
        $formData['from_email'] = acym_strtolower($formData['from_email']);
        $formData['replyto_email'] = acym_strtolower($formData['replyto_email']);
        $formData['bounce_email'] = acym_strtolower($formData['bounce_email']);
    }

    public function store(): void
    {
        acym_checkToken();

        $formData = acym_getVar('array', 'config', []);
        if (empty($formData)) {
            return;
        }

        $this->handleReplyTo($formData);
        $this->handleWordWrap($formData);
        $this->handleDemoSite($formData);
        $this->handleAcl($formData);
        $this->handleSelect2Fields($formData);
        $this->handleAcyChecker($formData);
        $this->handleNewDkim($formData);
        $this->handleEmails($formData);
        $this->handleUnsubSurvey($formData);

        acym_trigger('onBeforeSaveConfigFields', [&$formData]);

        // Don't move this line in the if, we need to do it before the save
        $licenseKeyBeforeSave = $this->config->get('license_key');

        if ($this->config->save($formData)) {
            $this->handleWebsiteLinking($formData, $licenseKeyBeforeSave);

            acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'));
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
        }

        $this->handleMultilingual($formData);

        $this->config->load();
    }

    private function handleReplyTo(array &$formData): void
    {
        if ($formData['from_as_replyto'] == 1) {
            if (isset($formData['from_name'])) {
                $formData['replyto_name'] = $formData['from_name'];
            }
            $formData['replyto_email'] = $formData['from_email'];
        }
    }

    private function handleWordWrap(array &$formData): void
    {
        if (empty($formData['mailer_wordwrap']) || $formData['mailer_wordwrap'] < 0) {
            $formData['mailer_wordwrap'] = 0;
        }

        if ($formData['mailer_wordwrap'] > 998) {
            $formData['mailer_wordwrap'] = 998;
        }
    }

    private function handleDemoSite(array &$formData): void
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $formData['wp_access'] = 'demo';
            $formData['license_key'] = '';
        }
        //__END__demo_
    }

    private function handleAcl(array &$formData): void
    {
        if (ACYM_PRODUCTION) {
            $aclPages = array_keys(acym_getPagesForAcl());
            foreach ($aclPages as $page) {
                if (empty($formData['acl_'.$page])) {
                    $formData['acl_'.$page] = ['all'];
                }
            }
        }
    }

    private function handleSelect2Fields(array &$formData): void
    {
        $select2Fields = [
            'regacy_lists',
            'regacy_checkedlists',
            'regacy_autolists',
            'acy_notification_create',
            'acy_notification_unsub',
            'acy_notification_unsuball',
            'acy_notification_subform',
            'acy_notification_profile',
            'acy_notification_confirm',
            'wp_access',
            'multilingual_languages',
            'allowed_hosts',
            'unsub_survey',
        ];

        foreach ($select2Fields as $oneField) {
            if ($oneField === 'unsub_survey' && !empty($formData[$oneField])) {
                $formData[$oneField] = json_encode($formData[$oneField]);
            }
            if (empty($formData[$oneField])) {
                $formData[$oneField] = [];
            }
        }
    }

    private function handleAcyChecker(array &$formData): void
    {
        if (empty($formData['email_verification'])) {
            return;
        }

        $disabledOptions = true;
        $verificationOptions = [
            'email_verification_non_existing',
            'email_verification_disposable',
            'email_verification_free',
            'email_verification_role',
            'email_verification_acceptall',
        ];

        foreach ($verificationOptions as $oneOption) {
            if (!empty($formData[$oneOption])) {
                $disabledOptions = false;
            }
        }

        if ($disabledOptions) {
            $formData['email_verification'] = false;
            acym_enqueueMessage(acym_translation('ACYM_ACYCHECKER_AUTO_DISABLED'), 'info');
        }
    }

    private function handleNewDkim(array &$formData): void
    {
        // The user set DKIM as disabled
        if (empty($formData['dkim'])) {
            return;
        }

        $privateKey = $this->config->get('dkim_private');
        $publicKey = $this->config->get('dkim_public');

        // We're manually submitting keys
        if (!empty($formData['dkim_private']) || !empty($formData['dkim_public'])) {
            return;
        }

        // We don't submit keys and stored keys are not empty
        if (!isset($formData['dkim_private']) && !empty($privateKey) && !empty($publicKey)) {
            return;
        }

        $newDkimKeys = UpdatemeHelper::call('public/dkim');
        if (empty($newDkimKeys['private_key'])) {
            return;
        }

        $formData['dkim_private'] = $newDkimKeys['private_key'];
        $formData['dkim_public'] = $newDkimKeys['public_key'];
    }

    private function handleWebsiteLinking(array $formData, string $licenseKeyBeforeSave): void
    {
        $isLicenseKeyUpdated = isset($formData['license_key']) && $licenseKeyBeforeSave !== $formData['license_key'];

        //__START__production_
        if ($isLicenseKeyUpdated && ACYM_PRODUCTION) {
            // If we add a key or edit it, we try to attach it
            if (!empty($formData['license_key'])) {
                $resultAttachLicenseOnUpdateMe = $this->attachLicenseOnUpdateMe($formData['license_key']);

                if (!empty($resultAttachLicenseOnUpdateMe['message'])) {
                    $this->displayMessage($resultAttachLicenseOnUpdateMe['message']);
                }
            } else {
                // If we remove a key, we try to unlink it
                $resultUnlinkLicenseOnUpdateMe = $this->unlinkLicenseOnUpdateMe($licenseKeyBeforeSave);

                if (!empty($resultUnlinkLicenseOnUpdateMe['message'])) {
                    $this->displayMessage($resultUnlinkLicenseOnUpdateMe['message']);
                }
            }
        }
        //__END__production_
    }

    private function handleMultilingual(array $formData): void
    {
        // Remove unused email translations
        $removed = array_diff(
            explode(',', acym_getVar('string', 'previous_multilingual_languages', '')),
            $formData['multilingual_languages']
        );

        if (!empty($removed)) {
            $mailClass = new MailClass();
            $mailClass->deleteByTranslationLang($removed);
        }
    }

    public function test(): void
    {
        $this->store();

        $mailerHelper = new MailerHelper();
        $addedName = $this->config->get('add_names', true) ? $mailerHelper->cleanText(acym_currentUserName()) : '';

        $mailerHelper->AddAddress(acym_currentUserEmail(), $addedName);
        $mailerHelper->Subject = 'Test e-mail from '.ACYM_LIVE;
        $mailerHelper->Body = acym_translation('ACYM_TEST_EMAIL');
        $mailerHelper->SMTPDebug = 1;
        $mailerHelper->isTest = true;
        //We set the full error reporting if we are in debug mode
        if (acym_isDebug()) {
            $mailerHelper->SMTPDebug = 2;
        }

        $mailerHelper->isHTML(false);
        $result = $mailerHelper->send();

        if (!$result) {
            $sendingMethod = $this->config->get('mailer_method');

            if ($sendingMethod === 'smtp') {
                if ($this->config->get('smtp_secured') === 'ssl' && !function_exists('openssl_sign')) {
                    acym_enqueueMessage(acym_translation('ACYM_OPENSSL'), 'notice');
                }

                if (!$this->config->get('smtp_auth') && strlen($this->config->get('smtp_password')) > 1) {
                    acym_enqueueMessage(acym_translation('ACYM_ADVICE_SMTP_AUTH'), 'notice');
                }

                if ($this->config->get('smtp_port') && !in_array($this->config->get('smtp_port'), [25, 2525, 465, 587])) {
                    acym_enqueueMessage(acym_translationSprintf('ACYM_ADVICE_PORT', $this->config->get('smtp_port')), 'notice');
                }
            }

            if (acym_isLocalWebsite() && in_array($sendingMethod, ['sendmail', 'qmail', 'mail'])) {
                acym_enqueueMessage(acym_translation('ACYM_ADVICE_LOCALHOST'), 'notice');
            }

            $creditsLeft = 10000;
            acym_trigger('onAcymCreditsLeft', [&$creditsLeft]);

            $bounce = $this->config->get('bounce_email');
            if (!empty($creditsLeft) && !empty($bounce) && !in_array($sendingMethod, ['smtp', 'elasticemail'])) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_ADVICE_BOUNCE', '<b>'.$bounce.'</b>'), 'notice');
            }
        }

        $this->listing();
    }

    public function handleUnsubSurvey(array &$formData): void
    {
        if (isset($formData['unsub_survey'])) {
            $unsubSurvey = json_decode($formData['unsub_survey'], true);
            if (is_array($unsubSurvey)) {
                foreach ($unsubSurvey as $key => $value) {
                    if (is_string($value)) {
                        $unsubSurvey[$key] = strip_tags($value);
                    }
                }
                $formData['unsub_survey'] = json_encode($unsubSurvey);
            }
        }

        if (isset($formData['unsub_survey_translation'])) {
            $unsubSurveyTranslation = json_decode($formData['unsub_survey_translation'], true);
            if (is_array($unsubSurveyTranslation)) {
                foreach ($unsubSurveyTranslation as $lang => $unsubSurvey) {
                    if (is_array($unsubSurvey) && isset($unsubSurvey['unsub_survey'])) {
                        foreach ($unsubSurvey['unsub_survey'] as $key => $value) {
                            if (is_string($value)) {
                                $unsubSurveyTranslation[$lang]['unsub_survey'][$key] = strip_tags($value);
                            }
                        }
                    }
                }
                $formData['unsub_survey_translation'] = json_encode($unsubSurveyTranslation);
            }
        }
    }

    //__START__starter_
    private function resetQueueProcess(): void
    {
        if (!acym_level(ACYM_ESSENTIAL) && $this->config->get('queue_type', 'manual') !== 'manual') {
            $this->config->save(['queue_type' => 'manual']);
        }
    }

    //__END__starter_

    public function addNewSml(): void
    {
        acym_trigger('onConfigurationAddSml');
        $this->listing();
    }

    public function deleteSml(): void
    {
        acym_trigger('onConfigurationDeleteSml');
        $this->listing();
    }
}
