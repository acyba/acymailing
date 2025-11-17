<?php

namespace AcyMailing\Helpers\Update;

use AcyMailing\Classes\ActionClass;
use AcyMailing\Classes\ConditionClass;
use AcyMailing\Classes\ConfigurationClass;
use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\PluginClass;
use AcyMailing\Controllers\PluginsController;
use AcyMailing\Helpers\AutomationHelper;
use AcyMailing\Helpers\UpdateHelper;

trait Patchv6
{
    private function updateFor603(): void
    {
        if ($this->isPreviousVersionAtLeast('6.0.3')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_mail_stat ADD COLUMN `bounce_details` LONGTEXT NULL');
        $this->updateQuery('ALTER TABLE #__acym_mail_stat ADD COLUMN `bounce_unique` MEDIUMINT(8) NOT NULL');
        $this->updateQuery('ALTER TABLE #__acym_user_stat ADD COLUMN `bounce` TINYINT(4) NOT NULL');
        $this->updateQuery('ALTER TABLE #__acym_user_stat ADD COLUMN `bounce_rule` VARCHAR(255) NULL');
        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_history` (
                `user_id` INT NOT NULL,
                `date` INT NOT NULL,
                `ip` VARCHAR(16) DEFAULT NULL,
                `action` VARCHAR(50) NOT NULL,
                `data` text,
                `source` text,
                `mail_id` MEDIUMINT DEFAULT NULL,
                PRIMARY KEY (`user_id`,`date`)
            )'
        );
        $this->updateQuery('ALTER TABLE #__acym_rule CHANGE `id` `id` INT NOT NULL AUTO_INCREMENT');
        $this->updateQuery('ALTER TABLE #__acym_rule ADD COLUMN `increment_stats` TINYINT(3) NOT NULL');
        $this->updateQuery('ALTER TABLE #__acym_rule ADD COLUMN `execute_action_after` INT NOT NULL');

        // Use translation keys for name and email fields
        $this->updateQuery('UPDATE #__acym_field SET `name` = "ACYM_NAME" WHERE `id` = 1 AND `name` = "Name"');
        $this->updateQuery('UPDATE #__acym_field SET `name` = "ACYM_EMAIL" WHERE `id` = 2 AND `name` = "Email"');
        $this->updateQuery('UPDATE #__acym_field SET `backend_profile` = 1, `backend_listing` = 1 WHERE `id` IN (1, 2)');

        $this->updateQuery('UPDATE #__acym_mail SET `thumbnail` = ""');

        if (ACYM_CMS === 'wordpress') {
            $this->updateQuery('UPDATE #__acym_configuration SET `value` = '.acym_escapeDB(ACYM_UPLOAD_FOLDER).' WHERE `name` = "uploadfolder"');
            $this->updateQuery('UPDATE #__acym_configuration SET `value` = '.acym_escapeDB(ACYM_LOGS_FOLDER.'report_{year}_{month}.log').' WHERE `name` = "cron_savepath"');
        }
    }

    private function updateFor610(): void
    {
        if ($this->isPreviousVersionAtLeast('6.1.0')) {
            return;
        }

        $this->updateQuery('DROP TABLE IF EXISTS `#__acym_automation_has_step`, `#__acym_step`, `#__acym_automation`');
        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_automation` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(255) NOT NULL,
              `description` LONGTEXT NULL,
              `active` TINYINT(3) NOT NULL,
              `report` TEXT NULL,
              `tree` LONGTEXT NULL,
              PRIMARY KEY (`id`)
            )
                ENGINE = InnoDB
                /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;'
        );
        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_step` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(255) NOT NULL,
              `triggers` LONGTEXT NULL,
              `automation_id` INT NOT NULL,
              `last_execution` INT NULL,
              `next_execution` INT NULL,
              PRIMARY KEY (`id`),
              CONSTRAINT `fk#__acym__step1`
                FOREIGN KEY(`automation_id`)
                REFERENCES `#__acym_automation` (`id`)
                ON DELETE NO ACTION
                ON UPDATE NO ACTION
            )
                ENGINE = InnoDB
                /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;'
        );
        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_condition` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `step_id` INT NOT NULL,
                `conditions` LONGTEXT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_#__acym_condition1`
                FOREIGN KEY (`step_id`)
                REFERENCES `#__acym_step` (`id`)
                    ON DELETE NO ACTION
                    ON UPDATE NO ACTION
            )
                ENGINE =InnoDB
                /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;'
        );
        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_action` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `condition_id` INT NOT NULL,
                `actions` LONGTEXT NULL,
                `filters` LONGTEXT NULL,
                `order` INT NOT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_#__acym_action1`
                FOREIGN KEY (`condition_id`)
                REFERENCES `#__acym_condition` (`id`)
                    ON DELETE NO ACTION
                    ON UPDATE NO ACTION
            )
                ENGINE =InnoDB
                /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;'
        );

        $this->updateQuery('ALTER TABLE #__acym_user ADD COLUMN `automation` VARCHAR(50) NOT NULL');

        $query = 'SELECT field.type AS type, userfield.user_id AS user_id, userfield.field_id AS field_id, userfield.value AS user_value 
                    FROM #__acym_user_has_field AS userfield 
                    JOIN #__acym_field AS field ON userfield.field_id = field.id 
                    WHERE field.type IN ("checkbox", "multiple_dropdown")';
        $fieldValues = acym_loadObjectList($query);
        foreach ($fieldValues as $fieldValue) {
            $value = [];
            if ('checkbox' === $fieldValue->type) {
                $value = json_decode($fieldValue->user_value, true);
                $value = array_keys($value);
            } elseif ('multiple_dropdown' === $fieldValue->type) {
                $value = json_decode($fieldValue->user_value, true);
            }
            $value = implode(',', $value);
            $this->updateQuery(
                'UPDATE #__acym_user_has_field 
                SET `value` = '.acym_escapeDB($value).' 
                WHERE user_id = '.intval($fieldValue->user_id).' 
                    AND field_id = '.intval($fieldValue->field_id)
            );
        }
    }

    private function updateFor612(): void
    {
        if ($this->isPreviousVersionAtLeast('6.1.2')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_mail ADD `media_folder` VARCHAR(255) NULL');
        $this->updateQuery('ALTER TABLE #__acym_field ADD `namekey` VARCHAR(255) NOT NULL');

        $query = 'SELECT field.type AS type, userfield.user_id AS user_id, userfield.field_id AS field_id, userfield.value AS user_value 
                    FROM #__acym_user_has_field AS userfield 
                    JOIN #__acym_field AS field ON userfield.field_id = field.id 
                    WHERE field.type IN ("phone")';
        $fieldValues = acym_loadObjectList($query);

        if (!empty($fieldValues)) {
            foreach ($fieldValues as $fieldValue) {
                $value = json_decode($fieldValue->user_value, true);
                $value = implode(',', $value);
                $this->updateQuery(
                    'UPDATE #__acym_user_has_field 
                    SET `value` = '.acym_escapeDB($value).' 
                    WHERE user_id = '.intval($fieldValue->user_id).' 
                        AND field_id = '.intval($fieldValue->field_id)
                );
            }
        }

        $fieldClass = new FieldClass();
        $fields = acym_loadObjectList('SELECT * FROM #__acym_field');
        foreach ($fields as $field) {
            if (!empty($field->namekey)) continue;
            $field->namekey = $fieldClass->generateNamekey($field->name);
            $fieldClass->save($field);
        }
    }

    private function updateFor613(): void
    {
        if ($this->isPreviousVersionAtLeast('6.1.3')) {
            return;
        }

        $this->updateQuery('UPDATE #__acym_user_has_list SET `status` = 1 WHERE `status` = 2');
    }

    private function updateFor614(): void
    {
        if ($this->isPreviousVersionAtLeast('6.1.4')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_mail ADD `headers` TEXT NULL');
        $this->updateQuery('ALTER TABLE #__acym_mail ADD `autosave` LONGTEXT NULL');

        $columns = acym_getColumns('condition');
        if (!in_array('conditions', $columns)) {
            $this->updateQuery('ALTER TABLE #__acym_condition CHANGE `condition` `conditions` LONGTEXT NULL');
        }

        $this->updateQuery('ALTER TABLE #__acym_automation ADD `admin` TINYINT(3) NULL');
    }

    private function updateFor615(ConfigurationClass $config): void
    {
        if ($this->isPreviousVersionAtLeast('6.1.5')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_mail ADD `preheader` VARCHAR(255) NULL');

        // Fix bounce rule "blocked by recipient filters
        $wrongRuleAddresses = acym_loadObjectList('SELECT `id`, `action_message` FROM `#__acym_rule` WHERE `action_message` LIKE "%\"forward_to\":\"\'%"');
        if (!empty($wrongRuleAddresses)) {
            foreach ($wrongRuleAddresses as $oneRule) {
                $actions = json_decode($oneRule->action_message, true);
                $actions['forward_to'] = trim($actions['forward_to'], "'");
                $this->updateQuery('UPDATE `#__acym_rule` SET `action_message` = '.acym_escapeDB(json_encode($actions)).' WHERE `id` = '.intval($oneRule->id));
            }
        }

        // We remove the google+ option from the saved config
        $socialIcons = json_decode($config->get('social_icons', '{}'), true);
        if (!empty($socialIcons['google'])) {
            unset($socialIcons['google']);

            $config->saveConfig(['social_icons' => json_encode($socialIcons)]);
        }

        // Then remove the google+ button from the emails containing it
        $mailsWithGoogle = acym_loadObjectList('SELECT `id`, `body` FROM `#__acym_mail` WHERE `body` LIKE "%googleplus%"');
        foreach ($mailsWithGoogle as $oneMail) {
            $body = preg_replace('#<a [^>]*googleplus[^>]*>[^<]*<img [^>]*>[^<]*</a>#Uis', '', $oneMail->body);
            if (empty($body)) continue;

            $this->updateQuery('UPDATE `#__acym_mail` SET `body` = '.acym_escapeDB($body).' WHERE `id` = '.intval($oneMail->id));
        }

        // Add fields for confirmation
        $this->updateQuery('ALTER TABLE #__acym_user ADD `confirmation_date` DATETIME DEFAULT NULL');
        $this->updateQuery('ALTER TABLE #__acym_user ADD `confirmation_ip` VARCHAR(16) DEFAULT NULL');

        //Handle Emoji support
        $mails = acym_loadObjectList('SELECT subject, id FROM #__acym_mail');
        $mailClass = new MailClass();
        $mails = $mailClass->encode($mails);

        foreach ($mails as $oneMail) {
            $this->updateQuery('UPDATE #__acym_mail SET `subject` = '.acym_escapeDB($oneMail->subject).' WHERE `id` = '.intval($oneMail->id));
        }
    }

    private function updateFor616(): void
    {
        if ($this->isPreviousVersionAtLeast('6.1.6')) {
            return;
        }

        // Handle emojis in body, name, preheader and autosave
        $mails = acym_loadObjectList('SELECT `id`, `name`, `body`, `autosave`, `preheader` FROM #__acym_mail');
        $mailClass = new MailClass();
        $mails = $mailClass->encode($mails);

        foreach ($mails as $oneMail) {
            $this->updateQuery(
                'UPDATE #__acym_mail SET `body` = '.acym_escapeDB($oneMail->body).', `autosave` = '.acym_escapeDB($oneMail->autosave).', `name` = '.acym_escapeDB(
                    $oneMail->name
                ).', `preheader` = '.acym_escapeDB($oneMail->preheader).' WHERE `id` = '.intval($oneMail->id)
            );
        }
    }

    private function updateFor617(): void
    {
        if ($this->isPreviousVersionAtLeast('6.1.7')) {
            return;
        }

        $actionClass = new ActionClass();
        $actions = $actionClass->getAll();
        foreach ($actions as $action) {
            $action->actions = str_replace('{time}', '[time]', $action->actions);
            $action->filters = str_replace('{time}', '[time]', $action->filters);
            $actionClass->save($action);
        }

        $conditionClass = new ConditionClass();
        $conditions = $conditionClass->getAll();
        foreach ($conditions as $condition) {
            $condition->conditions = str_replace('{time}', '[time]', $condition->conditions);
            $conditionClass->save($condition);
        }
    }

    private function updateFor622(ConfigurationClass $config): void
    {
        if ($this->isPreviousVersionAtLeast('6.2.2')) {
            return;
        }

        // Get the allowed extensions
        $allowedExtensions = explode(',', $config->get('allowed_files'));
        if (!empty($allowedExtensions)) {
            // Get the files in the upload folder
            $uploadFolder = trim(acym_cleanPath(html_entity_decode(ACYM_UPLOAD_FOLDER)), DS.' ').DS;
            $uploadPath = acym_cleanPath(ACYM_ROOT.$uploadFolder.'userfiles'.DS);

            if (file_exists($uploadPath)) {
                $files = acym_getFiles($uploadPath);
                if (!empty($files)) {
                    // Delete unallowed files
                    foreach ($files as $fileName) {
                        if (!preg_match('#\.('.implode('|', $allowedExtensions).')$#Ui', $fileName)) {
                            acym_deleteFile($uploadPath.DS.$fileName);
                        }
                    }
                }
            }
        }
    }

    private function updateFor640(): void
    {
        if ($this->isPreviousVersionAtLeast('6.4.0')) {
            return;
        }

        //we add new columns
        $this->updateQuery('ALTER TABLE #__acym_campaign ADD `sending_type` VARCHAR(16) DEFAULT NULL');
        $this->updateQuery('ALTER TABLE #__acym_campaign ADD `sending_params` TEXT DEFAULT NULL');
        $this->updateQuery('ALTER TABLE #__acym_campaign ADD `parent_id` INT DEFAULT NULL');
        $this->updateQuery('ALTER TABLE #__acym_campaign ADD `last_trigger` INT DEFAULT NULL');
        $this->updateQuery('ALTER TABLE #__acym_campaign ADD `next_trigger` INT DEFAULT NULL');

        //we fill the column sending_type
        $this->updateQuery('UPDATE #__acym_campaign SET `sending_type` = "now" WHERE `scheduled` = 0');
        $this->updateQuery('UPDATE #__acym_campaign SET `sending_type` = "scheduled" WHERE `scheduled` = 1');
        //I do this because if we do that 'ALTER TABLE #__acym_campaign ADD `sending_params` TEXT DEFAULT "[]"' I have a sql error can't set default value for column sending_params in joomla
        $this->updateQuery('UPDATE #__acym_campaign SET `sending_params` = "[]"');

        //we delete the column scheduled
        $this->updateQuery('ALTER TABLE #__acym_campaign DROP `scheduled`');
    }

    private function updateFor650(): void
    {
        if ($this->isPreviousVersionAtLeast('6.5.0')) {
            return;
        }

        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_plugin` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `title` VARCHAR (100) NOT NULL,
                `folder_name` VARCHAR(100) NOT NULL,
                `version` VARCHAR (10) NULL,
                `active` INT NOT NULL,
                `category` VARCHAR (100) NOT NULL,
                `level` VARCHAR (50) NOT NULL,
                `uptodate` INT NOT NULL,
                `features` VARCHAR (255) NOT NULL,
                `description` LONGTEXT NOT NULL,
                `latest_version` VARCHAR (255) NOT NULL,
                PRIMARY KEY (`id`)
            )
                ENGINE = InnoDB
                /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;'
        );

        $pluginsBefore650 = [
            'joomla' => [
                'cbuilder' => 'com_comprofiler',
                'eventbooking' => 'com_eventbooking',
                'hikashop' => 'com_hikashop',
                'jevents' => 'com_jevents',
                'payplans' => 'com_payplans',
                'seblod' => 'com_cck',
                'virtuemart' => 'com_virtuemart',
            ],
            'wordpress' => [
                'woocommerce' => 'woocommerce',
            ],
        ];

        $pluginsController = new PluginsController();
        foreach ($pluginsBefore650[ACYM_CMS] as $plugin => $extension) {
            $install = false;
            if (defined('JPATH_ADMINISTRATOR') && file_exists(rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.$extension.DS)) {
                $install = true;
            }

            if (defined('WP_PLUGIN_DIR') && file_exists(rtrim(WP_PLUGIN_DIR, DS).DS.$extension.DS)) {
                $install = true;
            }

            if ($install) {
                $error = $pluginsController->download(false, $plugin);
                if (!empty($error)) {
                    acym_enqueueMessage($error, 'error');
                }
            }
        }
        $this->updateQuery('ALTER TABLE #__acym_campaign CHANGE `last_trigger` `last_generated` INT DEFAULT NULL');
    }

    private function updateFor660(): void
    {
        if ($this->isPreviousVersionAtLeast('6.6.0')) {
            return;
        }

        $mailClass = new MailClass();
        $firstEmail = $mailClass->getOneByName(acym_translation(UpdateHelper::FIRST_EMAIL_NAME_KEY));
        if (!empty($firstEmail)) {
            // If the user installed AcyMailing but didn't save the first template, we just complete the <em>
            $firstEmail->body = preg_replace(
                '#(<em class="acym_remove_dynamic acymicon-close">)(</em>)#Uis',
                '$1&zwj;$2',
                $firstEmail->body
            );


            // If he saved the first template, it has completely removed the dtext closing <em>

            $closing = '<em class="acym_remove_dynamic acymicon-close">&zwj;</em>';

            // 1 - the view it online
            $firstEmail->body = preg_replace(
                '#(<span[^>]+data\-dynamic="{readonline}.+<span class="acym_online">[^<]+</span>[^<]*</a>[^<]*)(</span>)#Uis',
                '$1'.$closing.'$2',
                $firstEmail->body
            );

            // 2 - the user first name
            $firstEmail->body = preg_replace(
                '#(<span[^>]+data\-dynamic="{subscriber:name[^>]+>[^<]+)(</span>)#Uis',
                '$1'.$closing.'$2',
                $firstEmail->body
            );

            // 3 - the unsubscribe link
            $firstEmail->body = preg_replace(
                '#(<span[^>]+data\-dynamic="{unsubscribe}.+<span class="acym_unsubscribe">[^<]+</span>[^<]*</a>[^<]*)(</span>)#Uis',
                '$1'.$closing.'$2',
                $firstEmail->body
            );

            $mailClass->save($firstEmail);
        }

        $joomlaPluginsBefore650 = [
            'cbuilder',
            'eventbooking',
            'hikashop',
            'jevents',
            'payplans',
            'seblod',
            'virtuemart',
        ];

        $installedAddons = acym_loadResultArray('SELECT folder_name FROM #__acym_plugin');

        foreach ($joomlaPluginsBefore650 as $folderName) {
            // The add-on is installed, there's nothing to do
            if (in_array($folderName, $installedAddons) || !file_exists(ACYM_ADDONS_FOLDER_PATH.$folderName)) continue;

            // The add-on is installed but not in the DB, this is an old version that hasn't been removed
            acym_deleteFolder(ACYM_ADDONS_FOLDER_PATH.$folderName);
        }
    }

    private function updateFor670(): void
    {
        if ($this->isPreviousVersionAtLeast('6.7.0')) {
            return;
        }

        $this->updateQuery('DELETE FROM #__acym_configuration WHERE `name` = "small_display"');
        $this->updateQuery('UPDATE #__acym_configuration SET `value` = REPLACE(REPLACE(`value`, ",", "comma"), ";", "semicol") WHERE `name` = "export_separator"');
        $this->updateQuery('ALTER TABLE #__acym_field DROP COLUMN frontend_form');
        $this->updateQuery('ALTER TABLE #__acym_field CHANGE `frontend_profile` `frontend_edition` TINYINT(3) NULL');
        $this->updateQuery('ALTER TABLE #__acym_field CHANGE `backend_profile` `backend_edition` TINYINT(3) NULL');
        $this->updateQuery('ALTER TABLE #__acym_list ADD COLUMN `front_management` INT NULL');
    }

    private function updateFor692(): void
    {
        if ($this->isPreviousVersionAtLeast('6.9.2')) {
            return;
        }

        $socialFilesFolder = ACYM_ROOT.ACYM_UPLOAD_FOLDER.'socials';
        if (file_exists($socialFilesFolder)) {
            $uploadedFiles = acym_getFiles($socialFilesFolder);
            foreach ($uploadedFiles as $oneFile) {
                $extension = acym_fileGetExt($oneFile);
                if (!in_array($extension, acym_getImageFileExtensions())) {
                    acym_deleteFile($socialFilesFolder.DS.$oneFile);
                }
            }
        }
    }

    private function updateFor6100(): void
    {
        if ($this->isPreviousVersionAtLeast('6.10.0')) {
            return;
        }

        $mailClass = new MailClass();
        $fieldColumns = acym_getColumns('field');
        if (in_array('backend_filter', $fieldColumns)) {
            $this->updateQuery('ALTER TABLE #__acym_field DROP COLUMN backend_filter');
        }
        if (in_array('frontend_filter', $fieldColumns)) {
            $this->updateQuery('ALTER TABLE #__acym_field DROP COLUMN frontend_filter');
        }
        $mails = acym_loadObjectList('SELECT * FROM #__acym_mail WHERE thumbnail LIKE "%data:image/png;base64%"');
        foreach ($mails as $mail) {
            unset($mail->thumbnail);
            $mailClass->save($mail);
        }

        $this->updateQuery('DELETE FROM #__acym_user_has_field WHERE `value` = ""');

        $phoneFields = acym_loadResultArray('SELECT `id` FROM #__acym_field WHERE `type` = "phone"');
        if (!empty($phoneFields)) {
            $this->updateQuery('DELETE FROM #__acym_user_has_field WHERE `value` LIKE "%," AND `field_id` IN ('.implode(', ', $phoneFields).')');
        }
    }

    private function updateFor6102(): void
    {
        if ($this->isPreviousVersionAtLeast('6.10.2')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_mail ADD COLUMN `links_language` VARCHAR(10) NOT NULL DEFAULT ""');
    }

    private function updateFor6104(): void
    {
        if ($this->isPreviousVersionAtLeast('6.10.4')) {
            return;
        }

        $columnsUser = acym_getColumns('user');
        if (!empty($columnsUser)) {
            foreach ($columnsUser as $i => $oneColumn) {
                $columnsUser[$i] = acym_escapeDB($oneColumn);
            }

            $customFieldsWrongNamekey = acym_loadObjectList('SELECT * FROM #__acym_field WHERE namekey IN ('.implode(', ', $columnsUser).')');
            $fieldClass = new FieldClass();

            foreach ($customFieldsWrongNamekey as $field) {
                $field->namekey = $fieldClass->generateNamekey($field->name);
                $fieldClass->save($field);
            }
        }

        $this->updateQuery('ALTER TABLE #__acym_mail CHANGE `type` `type` VARCHAR(30) NOT NULL');
        $this->updateQuery('ALTER TABLE #__acym_mail CHANGE `media_folder` `media_folder` VARCHAR(100) NULL');
        $this->updateQuery('ALTER TABLE #__acym_mail CHANGE `links_language` `links_language` VARCHAR(10) NOT NULL');
    }

    private function updateFor6110(): void
    {
        if ($this->isPreviousVersionAtLeast('6.11.0')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_mail ADD `access` VARCHAR(50) NOT NULL DEFAULT ""');
        $this->updateQuery('ALTER TABLE #__acym_list ADD `access` VARCHAR(50) NOT NULL DEFAULT ""');

        $sourceMap = [
            'wordpress_profile' => 'WordPress user profile',
            'backend_management' => 'Back-end',
            'frontend_management' => 'Front-end',
            'auto_on_sending' => 'When sending a test',
            'menu_' => 'Menu n°',
            'mod_' => 'Module n°',
            '_frontregister' => ' registration form',
            'import_' => 'Import on ',
        ];

        foreach ($sourceMap as $oldSource => $newSource) {
            acym_query('UPDATE `#__acym_user` SET `source` = REPLACE(`source`, '.acym_escapeDB($oldSource).', '.acym_escapeDB($newSource).')');
        }

        $this->updateQuery('ALTER TABLE #__acym_list ADD `description` TEXT NOT NULL DEFAULT ""');
    }

    private function updateFor6120(): void
    {
        if ($this->isPreviousVersionAtLeast('6.12.0')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_user ADD `tracking` TINYINT(1) NOT NULL DEFAULT 1');
        $this->updateQuery('ALTER TABLE #__acym_list ADD `tracking` TINYINT(1) NOT NULL DEFAULT 1');
        $this->updateQuery('ALTER TABLE #__acym_mail ADD `tracking` TINYINT(1) NOT NULL DEFAULT 1');
        $this->updateQuery('ALTER TABLE #__acym_plugin ADD `settings` LONGTEXT NULL');
    }

    private function updateFor6130(): void
    {
        if ($this->isPreviousVersionAtLeast('6.13.0')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_plugin ADD `core` TINYINT(1) NOT NULL DEFAULT 0');
        $this->updateQuery('ALTER TABLE #__acym_plugin CHANGE `latest_version` `latest_version` VARCHAR(10) NOT NULL');
    }

    private function updateFor6140(): void
    {
        if ($this->isPreviousVersionAtLeast('6.14.0')) {
            return;
        }

        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_form` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `creation_date` DATETIME NOT NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `type` VARCHAR(20) NOT NULL,
                `lists_options` LONGTEXT,
                `fields_options` LONGTEXT,
                `style_options` LONGTEXT,
                `button_options` LONGTEXT,
                `image_options` LONGTEXT,
                `delay` SMALLINT(10),
                `pages` TEXT,
                PRIMARY KEY (`id`)
            )
                ENGINE = InnoDB
                /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;'
        );

        // In a release, all the content insertion addons had the structure Volumes/workspace/acymailing/addons/folder_name/plugin.php instead of just folder_name/plugin.php
        if (file_exists(ACYM_ADDONS_FOLDER_PATH.'Volumes')) {
            $wrongAddons = acym_getFolders(ACYM_ADDONS_FOLDER_PATH.'Volumes'.DS.'workspace'.DS.'acymailing'.DS.'addons'.DS);

            $pluginClass = new PluginClass();
            foreach ($wrongAddons as $oneGoneWrong) {
                $errorMessage = $pluginClass->downloadAddon($oneGoneWrong, false);
                if (!empty($errorMessage)) {
                    acym_enqueueMessage($errorMessage, 'error');
                }
            }

            acym_deleteFolder(ACYM_ADDONS_FOLDER_PATH.'Volumes');
        }

        $this->updateQuery('ALTER TABLE #__acym_user ADD `language` VARCHAR(10) NOT NULL');
        $this->updateQuery('ALTER TABLE #__acym_mail ADD `language` VARCHAR(10) NOT NULL');
        $this->updateQuery('ALTER TABLE #__acym_mail ADD `parent_id` INT NULL');
    }

    private function updateFor6150(): void
    {
        if ($this->isPreviousVersionAtLeast('6.15.0')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_user_stat ADD `tracking_sale` FLOAT NULL');
        $this->updateQuery('ALTER TABLE #__acym_user_stat ADD `currency` VARCHAR(10) NULL');
        $this->updateQuery('ALTER TABLE #__acym_campaign ADD `visible` TINYINT(1) NOT NULL DEFAULT 1');
        $this->updateQuery('ALTER TABLE #__acym_user CHANGE `language` `language` VARCHAR(10) NOT NULL DEFAULT ""');
        $this->updateQuery('ALTER TABLE #__acym_mail CHANGE `language` `language` VARCHAR(10) NOT NULL DEFAULT ""');
        $this->updateQuery('ALTER TABLE `#__acym_mail` CHANGE `preheader` `preheader` TEXT NULL');
    }

    private function updateFor6160(): void
    {
        if ($this->isPreviousVersionAtLeast('6.16.0')) {
            return;
        }

        $this->updateQuery('ALTER TABLE `#__acym_mail_stat` CHANGE `bounce_unique` `bounce_unique` MEDIUMINT(8) NOT NULL DEFAULT 0');
        $this->updateQuery('ALTER TABLE `#__acym_user_stat` CHANGE `bounce` `bounce` TINYINT(4) NOT NULL DEFAULT 0');
        $this->updateQuery('ALTER TABLE #__acym_plugin ADD `type` VARCHAR(20) NOT NULL DEFAULT "ADDON"');
        $this->updateQuery('UPDATE `#__acym_plugin` SET `type` = "ADDON" WHERE `core` = 0');
        $this->updateQuery('UPDATE `#__acym_plugin` SET `type` = "CORE" WHERE `core` = 1');
        $this->updateQuery('ALTER TABLE #__acym_plugin DROP `core`');
        $this->updateQuery('ALTER TABLE `#__acym_form` ADD termspolicy_options LONGTEXT');
        $this->updateQuery('UPDATE `#__acym_form` SET `termspolicy_options` = "{\"termscond\":0,\"privacy\":0}" WHERE `termspolicy_options` is NULL');
    }

    private function updateFor6170(): void
    {
        if ($this->isPreviousVersionAtLeast('6.17.0')) {
            return;
        }

        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_segment` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `creation_date` DATETIME NOT NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `filters` LONGTEXT NULL,
                PRIMARY KEY (`id`)
            )
                ENGINE = InnoDB
                /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;'
        );
        $automationHelper = new AutomationHelper();
        $automationHelper->deleteUnusedEmails();
        $this->updateQuery('ALTER TABLE `#__acym_form` ADD cookie VARCHAR(30)');
        $this->updateQuery('UPDATE `#__acym_form` SET `cookie` = "{\"cookie_expiration\":1}" WHERE `cookie` IS NULL');
    }

    private function updateFor6180(): void
    {
        if ($this->isPreviousVersionAtLeast('6.18.0')) {
            return;
        }

        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_followup` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(255) NOT NULL,
                `display_name` VARCHAR(255) NOT NULL,
                `creation_date` DATETIME NOT NULL,
                `trigger` VARCHAR(50),
                `condition` LONGTEXT,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `send_once` TINYINT(1) NOT NULL DEFAULT 1,
                `list_id` INT NULL,
                `last_trigger` INT NULL,
                PRIMARY KEY (`id`),
                INDEX `fk_#__acym_followup_has_list`(`list_id` ASC),
                CONSTRAINT `fk_#__acym_followup_has_list`
                    FOREIGN KEY (`list_id`)
                        REFERENCES `#__acym_list`(`id`)
                        ON DELETE NO ACTION
                        ON UPDATE NO ACTION
            )
                ENGINE = InnoDB
                /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;'
        );
        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_followup_has_mail` (
                `mail_id` INT NOT NULL,
                `followup_id` INT NOT NULL,
                `delay` INT NOT NULL,
                `delay_unit` INT NOT NULL,
                PRIMARY KEY (`mail_id`, `followup_id`),
                INDEX `fk_#__acym_mail_has_followup1`(`followup_id` ASC),
                INDEX `fk_#__acym_mail_has_followup2`(`mail_id` ASC),
                CONSTRAINT `fk_#__acym_mail_has_followup1`
                    FOREIGN KEY (`mail_id`)
                        REFERENCES `#__acym_mail`(`id`)
                        ON DELETE NO ACTION
                        ON UPDATE NO ACTION,
                CONSTRAINT `fk_#__acym_mail_has_followup2`
                    FOREIGN KEY (`followup_id`)
                        REFERENCES `#__acym_followup`(`id`)
                        ON DELETE NO ACTION
                        ON UPDATE NO ACTION
            )
                ENGINE = InnoDB
                /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;'
        );

        $this->updateQuery('ALTER TABLE `#__acym_list` ADD `type` VARCHAR(20) NOT NULL DEFAULT '.acym_escapeDB(ListClass::LIST_TYPE_STANDARD));
        $this->updateQuery('UPDATE `#__acym_list` SET `type` = '.acym_escapeDB(ListClass::LIST_TYPE_FRONT).' WHERE `front_management` = 1');
        $this->updateQuery('ALTER TABLE `#__acym_list` DROP COLUMN front_management');

        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_mail_override` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `mail_id` INT NOT NULL,
                `description` VARCHAR(255) NOT NULL,
                `source` VARCHAR (20) NOT NULL,
                `active` TINYINT(1) NOT NULL DEFAULT 1,
                `base_subject` TEXT NOT NULL,
                `base_body` TEXT NOT NULL,
                PRIMARY KEY(`id`),
                CONSTRAINT `fk_#__acym_mail_override1`
                    FOREIGN KEY (`mail_id`)
                        REFERENCES `#__acym_mail`(`id`)
                        ON DELETE NO ACTION
                        ON UPDATE NO ACTION
            )
                ENGINE = InnoDB
                /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;'
        );
    }

    private function updateFor6181(): void
    {
        if ($this->isPreviousVersionAtLeast('6.18.1')) {
            return;
        }

        $mailClass = new MailClass();
        $emails = acym_loadObjectList('SELECT id, access FROM `#__acym_mail` WHERE access LIKE "%,,%" OR access LIKE "%[\"%"');

        foreach ($emails as $email) {
            if (strpos($email->access, ',,') !== false) {
                $access = explode(',', $email->access);
            } else {
                $access = json_decode(trim($email->access, ','));
            }
            $finalAccess = [];
            foreach ($access as $oneAccess) {
                if (!empty($oneAccess)) {
                    $finalAccess[] = $oneAccess;
                }
            }
            $email->access = empty($finalAccess) ? '' : $finalAccess;
            $mailClass->save($email);
        }
    }

    private function updateFor6190(): void
    {
        if ($this->isPreviousVersionAtLeast('6.19.0')) {
            return;
        }

        $this->updateQuery('ALTER TABLE `#__acym_mail_stat` ADD `unsubscribe_total` INT NOT NULL DEFAULT 0');
        $this->updateQuery('ALTER TABLE `#__acym_user_stat` ADD `unsubscribe` INT NOT NULL DEFAULT 0');
        $this->updateQuery('ALTER TABLE `#__acym_user_stat` ADD `device` VARCHAR(50) NULL');
        $this->updateQuery('ALTER TABLE `#__acym_user_stat` ADD `opened_with` VARCHAR(50) NULL');

        $this->updateQuery('ALTER TABLE `#__acym_mail` CHANGE `media_folder` `mail_settings` TEXT NULL');
        $templatesMediaFolders = acym_loadObjectList('SELECT `id`, `mail_settings` FROM `#__acym_mail` WHERE `mail_settings` IS NOT NULL');
        if (!empty($templatesMediaFolders)) {
            foreach ($templatesMediaFolders as $oneTemplate) {
                $settings = [
                    'media_folder' => $oneTemplate->mail_settings,
                ];
                $this->updateQuery('UPDATE `#__acym_mail` SET `mail_settings` = '.acym_escapeDB(json_encode($settings)).' WHERE `id` = '.intval($oneTemplate->id));
            }
        }
    }
}
