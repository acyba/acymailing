<?php

namespace AcyMailing\Helpers\Update;

use AcyMailing\Classes\FormClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\RuleClass;

trait Patchv8
{
    private function updateFor800($config)
    {
        if ($this->isPreviousVersionAtLeast('8.0.0')) {
            return;
        }

        $secretKey = $config->get('smtp_secret');
        $config->save(['smtp_type' => empty($secretKey) ? 'password' : 'oauth']);

        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_mailbox_action` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` varchar(255) DEFAULT NULL,
                `frequency` int(10) UNSIGNED NOT NULL DEFAULT 0,
                `nextdate` int(10) UNSIGNED NOT NULL DEFAULT 0,
                `description` text DEFAULT NULL,
                `server` varchar(255) NULL,
                `port` varchar(50) NULL,
                `connection_method` ENUM(\'imap\', \'pop3\', \'pear\') NULL,
                `secure_method` ENUM(\'ssl\', \'tls\') NULL,
                `self_signed` tinyint(4) NULL,
                `username` varchar(255) NULL,
                `password` varchar(50) NULL,
                `conditions` text DEFAULT NULL,
                `actions` text DEFAULT NULL,
                `report` text DEFAULT NULL,
                `delete_wrong_emails` tinyint(4) NOT NULL DEFAULT 0,
                `senderfrom` tinyint(4) NOT NULL DEFAULT 0,
                `senderto` tinyint(4) NOT NULL DEFAULT 0,
                `active` tinyint(4) NOT NULL DEFAULT 0,
                PRIMARY KEY (`id`),
                INDEX `index_#__acym_mailbox_action1`(`name` ASC)
            )
                ENGINE = InnoDB
                /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;'
        );
    }

    private function updateFor810()
    {
        if ($this->isPreviousVersionAtLeast('8.1.0')) {
            return;
        }

        $this->updateQuery('ALTER TABLE `#__acym_form` ADD `settings` TEXT');
        $this->updateQuery('ALTER TABLE `#__acym_form` ADD `display_languages` VARCHAR(255)');

        $formClass = new FormClass();
        $forms = $formClass->getAll();

        foreach ($forms as $oneForm) {
            $oneForm->settings = [];
            foreach ($oneForm as $key => $value) {
                $optionsPos = strpos($key, '_options');
                if (empty($value) || ($key !== 'cookie' && $optionsPos === false)) {
                    continue;
                }

                $category = $key === 'cookie' ? 'cookie' : substr($key, 0, $optionsPos);
                $oneForm->settings[$category] = json_decode($value, true);
            }

            $oneForm->settings = json_encode($oneForm->settings);
            $formClass->save($oneForm);
        }

        $this->updateQuery(
            'ALTER TABLE `#__acym_form` 
            DROP `lists_options`, 
            DROP `fields_options`, 
            DROP `style_options`, 
            DROP `button_options`, 
            DROP `image_options`, 
            DROP `termspolicy_options`, 
            DROP `cookie`, 
            DROP `redirection_options`, 
            DROP `display_options`, 
            DROP `message_options`'
        );

        $this->updateQuery('ALTER TABLE `#__acym_user` CHANGE `key` `key` VARCHAR(40) NULL');
    }

    private function updateFor811()
    {
        if ($this->isPreviousVersionAtLeast('8.1.1')) {
            return;
        }

        $formClass = new FormClass();
        $forms = $formClass->getAll();

        foreach ($forms as $oneForm) {
            if (!empty($oneForm->display_languages)) {
                continue;
            }
            $oneForm->display_languages = '["all"]';
            $formClass->save($oneForm);
        }
    }

    private function updateFor850()
    {
        if ($this->isPreviousVersionAtLeast('8.5.0')) {
            return;
        }

        $aclPages = [
            'forms',
            'users',
            'fields',
            'lists',
            'segments',
            'campaigns',
            'mails',
            'override',
            'automation',
            'queue',
            'plugins',
            'bounces',
            'stats',
            'configuration',
        ];

        $config = acym_config();
        $groups = array_keys(acym_getGroups());

        foreach ($aclPages as $page) {
            $aclConfig = $config->get('acl_'.$page, 'all');

            if ($aclConfig === 'all') {
                continue;
            }

            $groupsAuthorized = [];

            foreach ($groups as $group) {
                if ($config->get('acl_'.$page.'_'.$group, 1) == 1) {
                    $groupsAuthorized[] = $group;
                }
            }

            $config->save(['acl_'.$page => implode(',', $groupsAuthorized)]);
        }

        $queries = file_get_contents(ACYM_BACK.'tables.sql');
        $tables = explode('CREATE TABLE IF NOT EXISTS ', $queries);
        $tableNames = [];
        $indexes = [];
        $constraints = [];

        foreach ($tables as $oneTable) {
            if (strpos($oneTable, '`#__') !== 0) {
                continue;
            }
            $tableName = substr($oneTable, 1, strpos($oneTable, '`', 1) - 1);
            $tableNames[] = $tableName;

            $fields = explode("\n", $oneTable);
            foreach ($fields as $key => $oneField) {
                $oneField = rtrim(trim($oneField), ',');
                if (strpos($oneField, 'INDEX') === 0) {
                    $firstBackquotePos = strpos($oneField, '`');
                    $indexName = substr($oneField, $firstBackquotePos + 1, strpos($oneField, '`', $firstBackquotePos + 1) - $firstBackquotePos - 1);

                    $indexes[$tableName][$indexName] = $oneField;
                } elseif (strpos($oneField, 'FOREIGN KEY') !== false) {
                    preg_match('/(#__fk.*)\`/Uis', $fields[$key - 1], $matchesConstraints);
                    preg_match('/(#__.*)\`\(`(.*)`\)/Uis', $fields[$key + 1], $matchesTable);
                    preg_match('/\`(.*)\`/Uis', $oneField, $matchesColumn);
                    if (!empty($matchesConstraints) && !empty($matchesTable) && !empty($matchesColumn)) {
                        if (empty($constraints[$tableName])) $constraints[$tableName] = [];
                        $constraints[$tableName][$matchesConstraints[1]] = [
                            'table' => $matchesTable[1],
                            'column' => $matchesColumn[1],
                            'table_column' => $matchesTable[2],
                        ];
                    }
                }
            }
        }

        foreach ($tableNames as $tableName) {
            if (!empty($indexes[$tableName])) {
                foreach ($indexes[$tableName] as $newName => $query) {
                    $oldName = str_replace('#__index_', 'index_#__', $newName);
                    preg_match('#\(.*\)#U', $query, $matches);
                    try {
                        acym_query('ALTER TABLE `'.$tableName.'` DROP INDEX `'.$oldName.'`, ADD INDEX `'.$newName.'`'.$matches[0]);
                    } catch (\Exception $exception) {
                        if (function_exists('acym_logError')) {
                            acym_logError('Error while renaming index '.$oldName.', with the error '.$exception->getMessage());
                        }
                    }
                }
            }

            if (!empty($constraints[$tableName])) {
                acym_query('SET FOREIGN_KEY_CHECKS=0;');
                foreach ($constraints[$tableName] as $newName => $constraintInfo) {
                    $oldName = str_replace('#__fk_', 'fk_#__', $newName);
                    $query = 'ALTER TABLE '.$tableName.' DROP FOREIGN KEY `'.$oldName.'`,ADD CONSTRAINT `'.$newName.'` FOREIGN KEY (`'.$constraintInfo['column'].'`) REFERENCES `'.$constraintInfo['table'].'` (`'.$constraintInfo['table_column'].'`) ON DELETE NO ACTION ON UPDATE NO ACTION;';
                    try {
                        acym_query($query);
                    } catch (\Exception $exception) {
                        if (function_exists('acym_logError')) {
                            acym_logError('Error while renaming foreign key '.$oldName.', with the error '.$exception->getMessage());
                        }
                    }
                }
                acym_query('SET FOREIGN_KEY_CHECKS=1;');
            }
        }
    }

    private function updateFor860()
    {
        if ($this->isPreviousVersionAtLeast('8.6.0')) {
            return;
        }

        $ruleClass = new RuleClass();

        $rule = new \stdClass();
        $rule->name = 'ACYM_SUPPRESSION_LIST';
        $rule->ordering = 2;
        $rule->regex = 'suppression list';
        $rule->executed_on = '["body"]';
        $rule->action_message = '["delete_message"]';
        $rule->action_user = '["unsubscribe_user","block_user","empty_queue_user"]';
        $rule->active = 1;
        $rule->increment_stats = 1;
        $rule->execute_action_after = 0;
        $ruleClass->save($rule);

        $rule = new \stdClass();
        $rule->name = 'ACYM_REJECTED';
        $rule->ordering = 9;
        $rule->regex = 'rejected *your *message|email *provider *rejected *it';
        $rule->executed_on = '["body"]';
        $rule->action_message = '["delete_message"]';
        $rule->action_user = '[]';
        $rule->active = 1;
        $rule->increment_stats = 1;
        $rule->execute_action_after = 0;
        $ruleClass->save($rule);

        $this->updateQuery('ALTER TABLE #__acym_user ADD COLUMN `last_sent_date` DATETIME NULL');
        $this->updateQuery('ALTER TABLE #__acym_user ADD COLUMN `last_open_date` DATETIME NULL');
        $this->updateQuery('ALTER TABLE #__acym_user ADD COLUMN `last_click_date` DATETIME NULL');

        $this->updateQuery(
            'CREATE TABLE IF NOT EXISTS `#__acym_mail_archive` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `mail_id` INT NOT NULL,
                `date` INT(10) UNSIGNED NOT NULL,
                `body` LONGTEXT NOT NULL,
                `subject` VARCHAR(255) NULL,
                `settings` TEXT NULL,
                `stylesheet` TEXT NULL,
                `attachments` TEXT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `#__fk_acym_mail_archive1`
                    FOREIGN KEY (`mail_id`)
                        REFERENCES `#__acym_mail`(`id`)
                        ON DELETE NO ACTION
                        ON UPDATE NO ACTION
            )
                ENGINE = InnoDB
                /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci*/;'
        );
    }

    private function updateFor862()
    {
        if ($this->isPreviousVersionAtLeast('8.6.2')) {
            return;
        }

        $this->updateQuery('DELETE FROM #__acym_campaign WHERE mail_id IS NULL');

        $mailsToClean = acym_loadResultArray(
            'SELECT mail.id  
            FROM `#__acym_mail` AS mail 
            LEFT JOIN #__acym_campaign AS campaign 
                ON mail.id = campaign.mail_id
            WHERE campaign.mail_id IS NULL
                AND mail.parent_id IS NULL
                AND mail.type = '.acym_escapeDB(MailClass::TYPE_STANDARD)
        );

        if (!empty($mailsToClean)) {
            $this->updateQuery('DELETE FROM #__acym_mail_archive WHERE mail_id IN ('.implode(',', $mailsToClean).')');
            $mailClass = new MailClass();
            $mailClass->delete($mailsToClean);
        }
    }

    private function updateFor870()
    {
        if ($this->isPreviousVersionAtLeast('8.7.0')) {
            return;
        }

        $this->updateQuery('ALTER TABLE #__acym_plugin DROP `features`');
    }

    private function updateFor872($config)
    {
        if ($this->isPreviousVersionAtLeast('8.7.2')) {
            return;
        }

        $socialIcons = json_decode($config->get('social_icons', '{}'), true);
        if (empty($socialIcons['X'])) {
            $socialIcons['X'] = ACYM_IMAGES.'logo/x.png';

            $newConfig = new \stdClass();
            $newConfig->social_icons = json_encode($socialIcons);
            $config->save($newConfig);
        }
    }

    private function updateFor873($config)
    {
        if ($this->isPreviousVersionAtLeast('8.7.3')) {
            return;
        }

        $socialIcons = json_decode($config->get('social_icons', '{}'), true);

        if (!empty($socialIcons['X'])) {
            unset($socialIcons['X']);

            $newConfig = new \stdClass();
            $newConfig->social_icons = json_encode($socialIcons);
            $config->save($newConfig);
        }

        if (empty($socialIcons['x'])) {
            $socialIcons['x'] = ACYM_IMAGES.'logo/x.png';

            $newConfig = new \stdClass();
            $newConfig->social_icons = json_encode($socialIcons);
            $config->save($newConfig);
        }
    }

    private function updateFor874($config)
    {
        if ($this->isPreviousVersionAtLeast('8.7.4') || ACYM_CMS !== 'joomla') {
            return;
        }

        $config->save(['malicious_scan' => 1]);
    }

    private function updateFor881($config)
    {
        if ($this->isPreviousVersionAtLeast('8.7.0')) {
            return;
        }

        // Replace backslashes by slashes in the upload folder option
        $uploadFolder = $config->get('uploadfolder');
        if (!empty($uploadFolder) && strpos($uploadFolder, '\\') !== false) {
            $uploadFolder = str_replace('\\', '/', $uploadFolder);
            $config->save(['uploadfolder' => $uploadFolder]);
        }
    }
}
