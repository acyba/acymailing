<?php

namespace AcyMailing\Controllers\Configuration;

use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Helpers\EncodingHelper;
use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Helpers\TabHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Types\AclType;
use AcyMailing\Types\DelayType;
use AcyMailing\Types\FailactionType;

trait Listing
{
    public function listing()
    {
        acym_setVar('layout', 'listing');

        $data = [];
        $data['tab'] = new TabHelper();
        $this->prepareLanguages($data);
        $this->prepareLists($data);
        $this->prepareNotifications($data);
        $this->prepareAcl($data);
        $this->prepareClass($data);
        $this->prepareDataTab($data);
        $this->prepareSecurity($data);
        $this->checkConfigMail($data);
        $this->prepareToolbar($data);
        $this->prepareHoursMinutes($data);
        //__START__starter_
        $this->resetQueueProcess();
        //__END__starter_

        //__START__wordpress_
        if (ACYM_CMS == 'wordpress' && acym_isExtensionActive('wp-mail-smtp/wp_mail_smtp.php')) {
            $pluginClass = new acymPlugin();
            $data['button_copy_settings_from'] = $pluginClass->getCopySettingsButton($data, 'from_options', 'wp_mail_smtp');
        }
        //__END__wordpress_

        $this->prepareMailSettings($data);
        $this->prepareMultilingualOption($data);

        parent::display($data);
    }

    private function checkConfigMail(&$data)
    {
        $queueType = $this->config->get('queue_type');
        $batchesNumber = $this->config->get('queue_batch_auto', 1);
        $emailsPerBatch = $this->config->get('queue_nbmail_auto', 70);
        $cronFrequency = $this->config->get('cron_frequency', 900);
        if ($queueType !== 'manual') {
            if (($batchesNumber > 1 || $cronFrequency < 900) && !function_exists('curl_multi_exec')) {
                acym_enqueueMessage(acym_translation('ACYM_NEED_CURL_MULTI'), 'error');
            }

            if ($batchesNumber > 4 || $emailsPerBatch > 300 || $cronFrequency < 300) {
                $text = acym_translation('ACYM_SEND_CONFIGURATION_WARNING');
                $text .= '<p class="acym__do__not__remindme" title="sendoverload">'.acym_translation('ACYM_DO_NOT_REMIND_ME').'</p>';
                acym_enqueueMessage($text, 'warning');
            }
        }
    }

    private function prepareMailSettings(&$data)
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

    public function prepareToolbar(&$data)
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

    private function prepareClass(&$data)
    {
        $data['typeDelay'] = new DelayType();
        $data['failaction'] = new FailactionType();
        $data['encodingHelper'] = new EncodingHelper();
    }

    private function prepareLanguages(&$data)
    {
        $langs = acym_getLanguages();
        $data['languages'] = [];

        foreach ($langs as $lang => $obj) {
            if ($lang == "xx-XX") continue;

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

    private function prepareLists(&$data)
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

    private function prepareNotifications(&$data)
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

    private function prepareAcl(&$data)
    {
        $data['acl'] = acym_cmsPermission();
        $data['acl_advanced'] = acym_getPagesForAcl();
        $data['aclType'] = new AclType();
    }

    private function prepareSecurity(&$data)
    {
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

    private function prepareDataTab(&$data)
    {
        $fieldClass = new FieldClass();
        $data['fields'] = $fieldClass->getAll();

        $data['export_data_changes_fields'] = $this->config->get('export_data_changes_fields', []);
        if (!is_array($data['export_data_changes_fields'])) {
            $data['export_data_changes_fields'] = explode(',', $data['export_data_changes_fields']);
        }
    }

    private function prepareHoursMinutes(&$data)
    {
        $listHours = [];
        for ($i = 0 ; $i < 24 ; $i++) {
            $value = $i < 10 ? '0'.$i : $i;
            $listHours[] = acym_selectOption($value, $value);
        }
        $listMinutes = [];
        for ($i = 0 ; $i < 60 ; $i += 5) {
            $value = $i < 10 ? '0'.$i : $i;
            $listMinutes[] = acym_selectOption($value, $value);
        }
        $listAllMinutes = [];
        for ($i = 0 ; $i < 60 ; $i++) {
            $value = $i < 10 ? '0'.$i : $i;
            $listAllMinutes[] = acym_selectOption($value, $value);
        }
        $data['listHours'] = $listHours;
        $data['listMinutes'] = $listMinutes;
        $data['listAllMinutes'] = $listAllMinutes;
    }

    public function store()
    {
        acym_checkToken();

        $formData = acym_getVar('array', 'config', []);
        if (empty($formData)) return false;

        if ($formData['from_as_replyto'] == 1) {
            $formData['replyto_name'] = $formData['from_name'];
            $formData['replyto_email'] = $formData['from_email'];
        }

        if (empty($formData['mailer_wordwrap']) || $formData['mailer_wordwrap'] < 0) $formData['mailer_wordwrap'] = 0;
        if ($formData['mailer_wordwrap'] > 998) $formData['mailer_wordwrap'] = 998;

        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $formData['wp_access'] = 'demo';
            foreach ($formData as $index => $data) {
                if (strpos($index, 'acl') !== false) {
                    unset($formData[$index]);
                }
            }
        }
        //__END__demo_

        // Handle reset select2 fields
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
        ];

        foreach ($select2Fields as $oneField) {
            if (empty($formData[$oneField])) {
                $formData[$oneField] = [];
            }
        }

        if (ACYM_PRODUCTION) {
            $aclPages = array_keys(acym_getPagesForAcl());
            foreach ($aclPages as $page) {
                if (empty($formData['acl_'.$page])) {
                    $formData['acl_'.$page] = ['all'];
                }
            }
        }

        $licenseKeyBeforeSave = $this->config->get('license_key');
        $isLicenseKeyUpdated = isset($formData['license_key']) && $licenseKeyBeforeSave !== $formData['license_key'];

        if (!empty($formData['email_verification'])) {
            $verificationOptions = [
                'email_verification_non_existing',
                'email_verification_disposable',
                'email_verification_free',
                'email_verification_role',
                'email_verification_acceptall',
            ];
            $disabledOptions = true;
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

        // Handle reset select2 fields from addon
        acym_trigger('onBeforeSaveConfigFields', [&$formData]);

        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $formData['license_key'] = '';
        }
        //__END__demo_

        $status = $this->config->save($formData);

        if ($status) {
            acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');

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
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
        }

        // Remove unused email translations
        $removed = array_diff(
            explode(',', acym_getVar('string', 'previous_multilingual_languages', '')),
            $formData['multilingual_languages']
        );
        if (!empty($removed)) {
            $mailClass = new MailClass();
            $mailClass->deleteByTranslationLang($removed);
        }

        $this->config->load();

        return true;
    }

    public function test()
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

    //__START__starter_
    private function resetQueueProcess()
    {
        if (!acym_level(ACYM_ESSENTIAL) && $this->config->get('queue_type', 'manual') !== 'manual') {
            $this->config->save(['queue_type' => 'manual']);
        }
    }
    //__END__starter_
}
