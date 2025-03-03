<?php

namespace AcyMailing\Helpers\Update;

use AcyMailing\Classes\ActionClass;
use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\ConditionClass;
use AcyMailing\Classes\ConfigurationClass;
use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\FormClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\RuleClass;
use AcyMailing\Classes\SegmentClass;
use AcyMailing\Controllers\ConfigurationController;

trait Patchv7
{
    private function updateFor700(ConfigurationClass $config): void
    {
        if ($this->isPreviousVersionAtLeast('7.0.0')) {
            return;
        }

        $socialIcons = json_decode($config->get('social_icons', '{}'), true);
        $socialMedias = acym_getSocialMedias();
        foreach ($socialIcons as $oneSocial => $imagePath) {
            if (!in_array($oneSocial, $socialMedias)) {
                unset($socialIcons[$oneSocial]);
            }
        }

        $newConfig = new \stdClass();
        $newConfig->social_icons = json_encode($socialIcons);
        $config->save($newConfig);

        $this->updateQuery('ALTER TABLE `#__acym_form` ADD `redirection_options` TEXT');
        $formClass = new FormClass();
        $forms = $formClass->getAll();
        if (!empty($forms)) {
            foreach ($forms as $oneForm) {
                $oneForm->redirection_options = json_encode(['after_subscription' => '', 'confirmation_message' => '']);
                $formClass->save($oneForm);
            }
        }

        if (!empty($config->get('active_cron', 0))) {
            $licenseKey = $config->get('license_key', '');
            if (!empty($licenseKey)) {
                $configurationController = new ConfigurationController();
                $configurationController->modifyCron('activateCron', $licenseKey);
            }
        }

        $segmentClass = new SegmentClass();
        $segmentClass->updateSegments();
    }

    private function updateFor710(): void
    {
        if ($this->isPreviousVersionAtLeast('7.1.0')) {
            return;
        }

        // Welcome and unsubscribe emails have template = 1 for some reason
        $this->updateQuery(
            'UPDATE #__acym_mail 
            SET `type` = '.acym_escapeDB(MailClass::TYPE_TEMPLATE).' 
            WHERE `template` = 1 
                AND `type` = '.acym_escapeDB(MailClass::TYPE_STANDARD)
        );

        $this->updateQuery('ALTER TABLE #__acym_mail DROP `template`');

        $fieldClass = new FieldClass();
        $fieldClass->insertLanguageField();
    }

    private function updateFor720(ConfigurationClass $config): void
    {
        if ($this->isPreviousVersionAtLeast('7.2.0')) {
            return;
        }

        $config->save(['built_by_update' => 1]);
        $config->save(['display_built_by' => 0]);
        $this->updateQuery('ALTER TABLE #__acym_user CHANGE `language` `language` VARCHAR(20) NOT NULL DEFAULT ""');
        $this->updateQuery('ALTER TABLE #__acym_mail CHANGE `language` `language` VARCHAR(20) NOT NULL DEFAULT ""');
        $this->updateQuery('ALTER TABLE #__acym_mail CHANGE `links_language` `links_language` VARCHAR(20) NOT NULL DEFAULT ""');

        $this->updateQuery('ALTER TABLE #__acym_list ADD `translation` LONGTEXT NULL');
        $this->updateQuery('ALTER TABLE #__acym_field ADD `translation` LONGTEXT NULL');
        $this->updateQuery('ALTER TABLE #__acym_mail ADD `translation` TEXT NULL');
    }

    private function updateFor721(): void
    {
        if ($this->isPreviousVersionAtLeast('7.2.1')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_mail CHANGE `translation` `translation` TEXT NULL');
    }

    private function updateFor740(ConfigurationClass $config): void
    {
        if ($this->isPreviousVersionAtLeast('7.4.0')) {
            return;
        }

        $actionClass = new ActionClass();
        $actions = $actionClass->getAll();
        foreach ($actions as $action) {
            if (strpos($action->filters, 'hikareminder')) {
                $action->filters = preg_replace_callback(
                    '/"hikareminder":{"days":"(\d+)"/',
                    [$this, 'replaceHikaReminder'],
                    $action->filters
                );
                $actionClass->save($action);
            }
        }

        $conditionClass = new ConditionClass();
        $conditions = $conditionClass->getAll();
        foreach ($conditions as $condition) {
            if (strpos($condition->conditions, 'hikareminder')) {
                $condition->conditions = preg_replace_callback(
                    '/"hikareminder":{"days":"(\d+)"/',
                    [$this, 'replaceHikaReminder'],
                    $condition->conditions
                );
                $conditionClass->save($condition);
            }
        }

        $segmentClass = new SegmentClass();
        $segments = $segmentClass->getAll();
        foreach ($segments as $segment) {
            if (strpos($segment->filters, 'hikareminder')) {
                $segment->filters = preg_replace_callback(
                    '/"hikareminder":{"days":"(\d+)"/',
                    [$this, 'replaceHikaReminder'],
                    $segment->filters
                );
                $segmentClass->save($segment);
            }
        }

        $campaignClass = new CampaignClass();
        $campaigns = $campaignClass->getAll();
        foreach ($campaigns as $campaign) {
            $campaign->sending_params = json_encode($campaign->sending_params);
            if (strpos($campaign->sending_params, 'hikareminder')) {
                $campaign->sending_params = preg_replace_callback(
                    '/"hikareminder":{"days":"(\d+)"/',
                    [$this, 'replaceHikaReminder'],
                    $campaign->sending_params
                );
                $campaign->sending_params = json_decode($campaign->sending_params);
                $campaignClass->save($campaign);
            }
        }

        $rule = new \stdClass();
        $rule->name = 'ACYM_LIST_UNSUBSCRIBE_HANDLING';
        $rule->ordering = 1;
        $rule->regex = 'Please unsubscribe user ID \\d+';
        $rule->executed_on = '["body"]';
        $rule->action_message = '["delete_message"]';
        $rule->action_user = '["unsubscribe_user"]';
        $rule->active = 1;
        $rule->increment_stats = 0;
        $rule->execute_action_after = 0;

        $ruleClass = new RuleClass();
        $ruleClass->save($rule);

        $captchaOption = $config->get('captcha', 0);
        $config->save([
            'captcha' => intval($captchaOption) === 1 ? 'acym_ireCaptcha' : 'none',
        ]);

        $this->updateQuery('ALTER TABLE #__acym_mail CHANGE `from_name` `from_name` VARCHAR(100) NULL');
        $this->updateQuery('ALTER TABLE #__acym_mail CHANGE `from_email` `from_email` VARCHAR(100) NULL');
        $this->updateQuery('ALTER TABLE #__acym_mail CHANGE `reply_to_name` `reply_to_name` VARCHAR(100) NULL');
        $this->updateQuery('ALTER TABLE #__acym_mail CHANGE `reply_to_email` `reply_to_email` VARCHAR(100) NULL');
    }

    private function updateFor750(ConfigurationClass $config): void
    {
        if ($this->isPreviousVersionAtLeast('7.5.0')) {
            return;
        }

        $languageFieldId = $config->get(FieldClass::LANGUAGE_FIELD_ID_KEY, 0);
        if (!empty($languageFieldId)) {
            $this->updateQuery('UPDATE `#__acym_field` SET `namekey` = "acym_language" WHERE `id` = '.intval($languageFieldId));
        }

        $news = $config->get('last_news', '');
        if (!empty($news)) {
            $config->save(
                [
                    'last_news' => base64_encode($news),
                ],
                false
            );
        }

        $this->updateQuery('ALTER TABLE `#__acym_user_has_list` ADD INDEX `index_#__acym_user_has_list3` (`subscription_date` ASC)');
        $this->updateQuery('ALTER TABLE `#__acym_user_has_list` ADD INDEX `index_#__acym_user_has_list4` (`unsubscribe_date` ASC)');
        $this->updateQuery('ALTER TABLE #__acym_mail_stat ADD `tracking_sale` FLOAT NULL');
        $this->updateQuery('ALTER TABLE #__acym_mail_stat ADD `currency` VARCHAR(5) NULL');

        $mailStatsClass = new MailStatClass();
        $mailStatsClass->migrateTrackingSale();
    }

    private function updateFor755(): void
    {
        if ($this->isPreviousVersionAtLeast('7.5.5')) {
            return;
        }

        $automationClass = new AutomationClass();
        $adminAutomations = $automationClass->getAutomationsAdmin();

        if (empty($adminAutomations)) {
            return;
        }

        $mailClass = new MailClass();
        foreach ($adminAutomations as $oneAutomation) {
            $actions = $automationClass->getActionsByAutomationId(intval($oneAutomation->id));
            foreach ($actions as $oneAction) {
                $oneAction->actions = json_decode($oneAction->actions, true);
                foreach ($oneAction->actions as $action) {
                    if (empty($action['acy_add_queue']['mail_id'])) continue;

                    $mail = $mailClass->getOneById($action['acy_add_queue']['mail_id']);
                    $mail->body = str_replace('{subtag:', '{subscriber:', $mail->body);

                    $mailClass->save($mail);
                }
            }
        }
    }

    private function updateFor759(): void
    {
        if ($this->isPreviousVersionAtLeast('7.5.9')) {
            return;
        }

        $fieldClass = new FieldClass();
        $fields = $fieldClass->getAll();
        foreach ($fields as $field) {
            if (empty($field->option)) continue;

            $field->option = json_decode($field->option, true);
            $options = array_keys($field->option);
            if (in_array('editable_user_creation', $options) || in_array('editable_user_modification', $options)) {
                unset($field->option['editable_user_creation']);
                unset($field->option['editable_user_modification']);

                $field->option = json_encode($field->option);
                $fieldClass->save($field);
            }
        }
    }

    private function updateFor7510(): void
    {
        if ($this->isPreviousVersionAtLeast('7.5.10') || ACYM_CMS !== 'joomla') {
            return;
        }

        $dynamicsToDelete = [
            'gravityforms',
            'page',
            'post',
            'theeventscalendar',
            'ultimatemember',
            'woocommerce',
            'jomsocial',
        ];

        foreach ($dynamicsToDelete as $dynamicFolder) {
            if (file_exists(ACYM_BACK.'dynamics'.DS.$dynamicFolder)) {
                acym_deleteFolder(ACYM_BACK.'dynamics'.DS.$dynamicFolder);
            }
        }
    }

    private function updateFor760(): void
    {
        if ($this->isPreviousVersionAtLeast('7.6.0')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_user CHANGE `key` `key` VARCHAR(30) NULL');
        $this->updateQuery('ALTER TABLE #__acym_mail DROP `library`');
        $this->updateQuery('ALTER TABLE #__acym_user_has_list ADD `unsubscribe_reason` TEXT NULL');
    }

    private function updateFor761(): void
    {
        if ($this->isPreviousVersionAtLeast('7.6.1')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_user_has_list DROP COLUMN `unsubscribe_reason`');
        $this->updateQuery('ALTER TABLE #__acym_history ADD `unsubscribe_reason` TEXT NULL');

        $fieldClass = new FieldClass();
        $dateCustomFields = $fieldClass->getFieldsByType('date');

        // Convert the already saved data into the new format
        foreach ($dateCustomFields as $oneField) {
            $oneField->option = json_decode($oneField->option);

            $formatToDisplay = explode('%', $oneField->option->format);
            unset($formatToDisplay[0]);

            $values = $fieldClass->getFieldsValueByFieldId($oneField->id);
            foreach ($values as $oneValue) {
                $userDate = explode('/', $oneValue->value);
                $year = '0000';
                $month = '00';
                $day = '00';
                $i = 0;
                foreach ($formatToDisplay as $one) {
                    // This is a value stored when the format was "%m%y" for example and the format changed to something else
                    if (!isset($userDate[$i])) continue 2;

                    if ($one === 'd') {
                        $day = $userDate[$i];
                    }
                    if ($one === 'm') {
                        $month = $userDate[$i];
                    }
                    if ($one === 'y') {
                        $year = $userDate[$i];
                    }
                    $i++;
                }

                acym_query(
                    'UPDATE #__acym_user_has_field 
                    SET `value` = '.acym_escapeDB($year.'-'.$month.'-'.$day).' 
                    WHERE `field_id` = '.intval($oneValue->field_id).' AND `user_id` = '.intval($oneValue->user_id)
                );
            }
        }

        $this->updateQuery('ALTER TABLE #__acym_user CHANGE `automation` `automation` VARCHAR(50) NOT NULL DEFAULT ""');
    }

    private function updateFor762(): void
    {
        if ($this->isPreviousVersionAtLeast('7.6.2')) {
            return;
        }

        $this->updateQuery('ALTER TABLE `#__acym_form` ADD `message_options` TEXT');
        $this->updateQuery('ALTER TABLE `#__acym_history` CHANGE `ip` `ip` VARCHAR(50)');
        $this->updateQuery('ALTER TABLE `#__acym_user` CHANGE `confirmation_ip` `confirmation_ip` VARCHAR(50)');
    }

    private function updateFor776(): void
    {
        if ($this->isPreviousVersionAtLeast('7.7.6')) {
            return;
        }

        $this->updateQuery('ALTER TABLE `#__acym_list` ADD `display_name` VARCHAR(255) NULL');
    }

    private function updateFor781(): void
    {
        if ($this->isPreviousVersionAtLeast('7.8.1')) {
            return;
        }

        $this->updateQuery('ALTER TABLE `#__acym_form` ADD `display_options` TEXT');
        $popupDelays = acym_loadObjectList('SELECT id, delay FROM #__acym_form WHERE `type` = "popup"', 'id');
        if (!empty($popupDelays)) {
            foreach ($popupDelays as $formId => $formDelay) {
                $newData = json_encode(['delay' => (int)$formDelay->delay, 'scroll' => 0]);
                $this->updateQuery('UPDATE `#__acym_form` SET  display_options = '.acym_escapeDB($newData).' WHERE id = '.(int)$formId);
            }
        }
        $this->updateQuery('ALTER TABLE `#__acym_form` DROP `delay`');

        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_custom_zone` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `content` TEXT NOT NULL,
                PRIMARY KEY (`id`)
            )'
        );
    }

    private function updateFor792(): void
    {
        if ($this->isPreviousVersionAtLeast('7.9.2')) {
            return;
        }

        $zones = acym_loadObjectList('SELECT `id`, `content` FROM #__acym_custom_zone');
        if (!empty($zones)) {
            foreach ($zones as $oneZone) {
                $oneZone->content = base64_encode(acym_utf8Decode($oneZone->content));
                $this->updateQuery('UPDATE `#__acym_custom_zone` SET `content` = '.acym_escapeDB($oneZone->content).' WHERE `id` = '.intval($oneZone->id));
            }
        }
    }

    private function updateFor793(): void
    {
        if ($this->isPreviousVersionAtLeast('7.9.3')) {
            return;
        }

        $automationClass = new AutomationClass();
        $automations = $automationClass->getAll();
        if (!empty($automations)) {
            foreach ($automations as $oneAutomation) {
                $translated = acym_translation($oneAutomation->name);
                if ($translated === $oneAutomation->name) continue;

                $oneAutomation->name = $translated;
                $oneAutomation->description = acym_translation($oneAutomation->description);
                $automationClass->save($oneAutomation);
            }
        }
    }

    private function updateFor794(): void
    {
        if ($this->isPreviousVersionAtLeast('7.9.4')) {
            return;
        }

        $this->updateQuery('ALTER TABLE `#__acym_custom_zone` ADD `image` VARCHAR(255) NULL');
    }

    private function updateFor796(): void
    {
        if ($this->isPreviousVersionAtLeast('7.9.6') || !file_exists(ACYM_ADDONS_FOLDER_PATH.'couryeah')) {
            return;
        }

        unlink(ACYM_ADDONS_FOLDER_PATH.'couryeah');
        $this->updateQuery('UPDATE #__acym_configuration SET `name` = "acymailer_domains" WHERE `name` = "couryeah_domains"');
    }

    private function replaceHikaReminder(array $matches): string
    {
        $val = (int)$matches[1] * 86400;

        return '"hikareminder":{"days":"'.$val.'"';
    }
}
