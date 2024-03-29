<?php

use AcyMailing\Classes\ActionClass;
use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\ConditionClass;
use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\FormClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\PluginClass;
use AcyMailing\Classes\RuleClass;
use AcyMailing\Classes\SegmentClass;
use AcyMailing\Classes\UrlClickClass;
use AcyMailing\Controllers\ConfigurationController;
use AcyMailing\Controllers\PluginsController;
use AcyMailing\Helpers\AutomationHelper;
use AcyMailing\Helpers\SplashscreenHelper;
use AcyMailing\Helpers\UpdateHelper;

class acymInstall
{
    public $cms = '{__CMS__}';
    public $level = '{__LEVEL__}';
    public $version = '{__VERSION__}';
    // If this is a real update between versions/editions
    public $update = false;
    // If this is the very first installation
    public $firstInstallation = true;
    public $fromLevel = '';
    public $fromVersion = '';

    public function __construct()
    {
        $path = '';
        $ds = DIRECTORY_SEPARATOR;
        if ($this->cms == 'Joomla') {
            $path = rtrim(JPATH_ADMINISTRATOR, $ds).$ds.'components'.$ds.'com_acym';
        } elseif ($this->cms == 'WordPress') {
            $path = rtrim(__DIR__, $ds).$ds.'back';
        }
        include_once $path.$ds.'helpers'.$ds.'helper.php';
    }

    public function installTables()
    {
        $queries = file_get_contents(ACYM_BACK.'tables.sql');
        $tables = explode('CREATE TABLE IF NOT EXISTS', $queries);

        foreach ($tables as $oneTable) {
            $oneTable = trim($oneTable);
            if (empty($oneTable)) {
                continue;
            }
            acym_query('CREATE TABLE IF NOT EXISTS'.$oneTable);
        }
    }

    // Function to add all pref...
    public function addPref()
    {
        $this->level = ucfirst($this->level);

        $allPref = acym_getDefaultConfigValues();

        $allPref['level'] = $this->level;
        $allPref['version'] = $this->version;

        $allPref['from_name'] = acym_getCMSConfig('fromname');
        $allPref['from_email'] = acym_getCMSConfig('mailfrom');
        $allPref['bounce_email'] = acym_getCMSConfig('mailfrom');
        $cmsMailer = acym_getCMSConfig('mailer', 'phpmail');
        $allPref['mailer_method'] = $cmsMailer === 'mail' ? 'phpmail' : $cmsMailer;
        $allPref['sendmail_path'] = acym_getCMSConfig('sendmail');
        $allPref['smtp_port'] = acym_getCMSConfig('smtpport');
        $allPref['smtp_secured'] = acym_getCMSConfig('smtpsecure');
        $allPref['smtp_auth'] = acym_getCMSConfig('smtpauth');
        $allPref['smtp_username'] = acym_getCMSConfig('smtpuser');
        $allPref['smtp_password'] = acym_getCMSConfig('smtppass');

        $allPref['replyto_name'] = $allPref['from_name'];
        $allPref['replyto_email'] = $allPref['from_email'];
        $allPref['cron_sendto'] = $allPref['from_email'];

        $allPref['add_names'] = '1';
        $allPref['encoding_format'] = '8bit';
        $allPref['charset'] = 'UTF-8';
        $allPref['word_wrapping'] = '150';
        $allPref['hostname'] = '';
        $allPref['embed_images'] = '0';
        $allPref['embed_files'] = '0';
        $allPref['editor'] = 'codemirror';
        $allPref['multiple_part'] = '1';

        $smtpinfos = explode(':', acym_getCMSConfig('smtphost', ''));
        $allPref['smtp_host'] = $smtpinfos[0];
        $allPref['smtp_type'] = 'oauth';
        if (isset($smtpinfos[1])) {
            $allPref['smtp_port'] = $smtpinfos[1];
        }
        if (!in_array($allPref['smtp_secured'], ['tls', 'ssl'])) {
            $allPref['smtp_secured'] = '';
        }

        $allPref['queue_nbmail'] = '40';
        $allPref['queue_nbmail_auto'] = '70';
        $allPref['queue_type'] = 'auto';
        $allPref['queue_try'] = '3';
        $allPref['queue_pause'] = '120';
        $allPref['allow_visitor'] = '1';
        $allPref['require_confirmation'] = '1';
        $allPref['priority_newsletter'] = '3';
        $allPref['allowed_files'] = 'zip,doc,docx,pdf,xls,txt,gzip,rar,jpg,jpeg,gif,xlsx,pps,csv,bmp,ico,odg,odp,ods,odt,png,ppt,swf,xcf,mp3,wma';
        $allPref['confirm_redirect'] = '';
        $allPref['subscription_message'] = '1';
        $allPref['notification_unsuball'] = '';
        $allPref['cron_next'] = '1251990901';
        $allPref['confirmation_message'] = '1';
        $allPref['welcome_message'] = '1';
        $allPref['unsub_message'] = '1';
        $allPref['cron_last'] = '0';
        $allPref['cron_fromip'] = '';
        $allPref['cron_report'] = '';
        $allPref['cron_frequency'] = '900';
        $allPref['cron_sendreport'] = '2';

        $allPref['cron_fullreport'] = '1';
        $allPref['cron_savereport'] = '2';
        $allPref['uploadfolder'] = str_replace('\\', '/', ACYM_UPLOAD_FOLDER);
        $allPref['notification_created'] = '';
        $allPref['notification_accept'] = '';
        $allPref['notification_refuse'] = '';
        $allPref['forward'] = '0';

        $allPref['priority_followup'] = '2';
        $allPref['unsub_redirect'] = '';
        $allPref['use_sef'] = '0';
        $allPref['css_frontend'] = '';
        $allPref['css_backend'] = '';

        $allPref['last_import'] = '';

        $allPref['unsub_reasons'] = serialize(['UNSUB_SURVEY_FREQUENT', 'UNSUB_SURVEY_RELEVANT']);
        $allPref['security_key'] = acym_generateKey(30);
        $allPref['export_excelsecurity'] = 1;
        $allPref['gdpr_export'] = 0;
        $allPref['gdpr_delete'] = 0;
        $allPref['anonymous_tracking'] = '0';
        $allPref['anonymizeold'] = '0';
        $allPref['trackingsystem'] = 'acymailing';
        $allPref['trackingsystemexternalwebsite'] = 1;
        $allPref['generate_name'] = 1;
        $allPref['allow_modif'] = 'data';

        $allPref['walk_through'] = '1';
        $allPref['migration'] = '0';

        $allPref['installcomplete'] = '0';

        $allPref['Starter'] = ACYM_STARTER;
        $allPref['Essential'] = ACYM_ESSENTIAL;
        $allPref['Enterprise'] = ACYM_ENTERPRISE;
        $allPref['from_as_replyto'] = '1';
        $allPref['templates_installed'] = '0';

        $allPref['bounceVersion'] = 2;

        $allPref['numberThumbnail'] = 2;
        $allPref['daily_hour'] = '12';
        $allPref['daily_minute'] = '00';

        $allPref['social_icons'] = json_encode([
                'facebook' => ACYM_MEDIA_URL.'images/logo/facebook.png',
                'twitter' => ACYM_MEDIA_URL.'images/logo/twitter.png',
                'x' => ACYM_MEDIA_URL.'images/logo/x.png',
                'instagram' => ACYM_MEDIA_URL.'images/logo/instagram.png',
                'linkedin' => ACYM_MEDIA_URL.'images/logo/linkedin.png',
                'pinterest' => ACYM_MEDIA_URL.'images/logo/pinterest.png',
                'vimeo' => ACYM_MEDIA_URL.'images/logo/vimeo.png',
                'wordpress' => ACYM_MEDIA_URL.'images/logo/wordpress.png',
                'youtube' => ACYM_MEDIA_URL.'images/logo/youtube.png',
                'telegram' => ACYM_MEDIA_URL.'images/logo/telegram.png',
            ]
        );

        $allPref['regacy'] = 1;
        $allPref['regacy_delete'] = 1;
        $allPref['regacy_forceconf'] = 0;

        $allPref['install_date'] = time();
        $allPref['remindme'] = '[]';

        $allPref['notifications'] = '{}';

        $allPref['unsubscribe_page'] = 1;

        $allPref['license_key'] = '';
        $allPref['active_cron'] = 0;
        $allPref['multilingual'] = 0;

        $allPref['delete_stats_enabled'] = 1;
        $allPref['delete_stats'] = 86400 * 360;
        $allPref['delete_archive_history_after'] = 86400 * 90;
        $allPref['previous_version'] = '{__VERSION__}';

        $allPref['display_built_by'] = acym_level(ACYM_ESSENTIAL) ? 0 : 1;
        $allPref['php_overrides'] = 0;

        $query = "INSERT IGNORE INTO `#__acym_configuration` (`name`,`value`) VALUES ";
        foreach ($allPref as $namekey => $value) {
            $query .= '('.acym_escapeDB($namekey).','.acym_escapeDB($value).'),';
        }
        $query = rtrim($query, ',');

        try {
            $res = acym_query($query);
        } catch (Exception $e) {
            $res = null;
        }
        if ($res === null) {
            acym_display(isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200).'...', 'error');

            return false;
        }

        return true;
    }

    //Function to update the preferences with the correct values in case of it's needed
    public function updatePref()
    {
        try {
            $results = acym_loadObjectList('SELECT `name`, `value` FROM `#__acym_configuration` WHERE `name` IN ("version", "level") LIMIT 2', 'name');
        } catch (Exception $e) {
            $results = null;
        }

        if ($results === null) {
            acym_display(isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200).'...', 'error');

            return false;
        }

        if (!empty($results['version'])) {
            $this->firstInstallation = false;
        }

        if ($results['version']->value == $this->version && $results['level']->value == $this->level) {
            return true;
        }

        $this->update = true;
        $this->fromLevel = $results['level']->value;
        $this->fromVersion = $results['version']->value;

        //We update the version properly as it's a new one which is now used.
        $query = 'REPLACE INTO `#__acym_configuration` (`name`,`value`) VALUES ("level",'.acym_escapeDB($this->level).')';
        $query .= ',("version",'.acym_escapeDB($this->version).')';
        $query .= ',("installcomplete","0")';
        $query .= ',("previous_version",'.acym_escapeDB($this->fromVersion).')';
        acym_query($query);

        return true;
    }

    public function deleteNewSplashScreenInstall()
    {
        // Compatibility with old versions for Joomla
        $splashscreenJson = ACYM_BACK.'partial'.DS.'update'.DS.'changelogs_splashscreen.json';

        if (file_exists($splashscreenJson)) {
            @unlink($splashscreenJson);
        }
    }

    //Update the SQL from one version to the other if needed
    public function updateSQL()
    {
        if (!$this->update) return;

        $config = acym_config();
        $mailClass = new MailClass();

        if (version_compare($this->fromVersion, '6.0.3', '<')) {
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
            if (ACYM_CMS == 'wordpress') {
                $this->updateQuery('UPDATE #__acym_configuration SET `value` = '.acym_escapeDB(ACYM_UPLOAD_FOLDER).' WHERE `name` = "uploadfolder"');
                $this->updateQuery('UPDATE #__acym_configuration SET `value` = '.acym_escapeDB(ACYM_LOGS_FOLDER.'report_{year}_{month}.log').' WHERE `name` = "cron_savepath"');
            }
        }

        if (version_compare($this->fromVersion, '6.1.0', '<')) {
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
                            /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;'
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
                            /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;'
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
                            /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;'
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
                            /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;'
            );

            $this->updateQuery('ALTER TABLE #__acym_user ADD COLUMN `automation` VARCHAR(50) NOT NULL');

            $query = 'SELECT field.type AS type, userfield.user_id AS user_id, userfield.field_id AS field_id, userfield.value AS user_value 
                    FROM #__acym_user_has_field AS userfield 
                    JOIN #__acym_field AS field ON userfield.field_id = field.id 
                    WHERE field.type IN ("checkbox", "multiple_dropdown")';
            $fieldValues = acym_loadObjectList($query);
            foreach ($fieldValues as $fieldValue) {
                $value = [];
                if ('checkbox' == $fieldValue->type) {
                    $value = json_decode($fieldValue->user_value, true);
                    $value = array_keys($value);
                } elseif ('multiple_dropdown' == $fieldValue->type) {
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

        if (version_compare($this->fromVersion, '6.1.2', '<')) {
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

        if (version_compare($this->fromVersion, '6.1.3', '<')) {
            $this->updateQuery('UPDATE #__acym_user_has_list SET `status` = 1 WHERE `status` = 2');
        }

        if (version_compare($this->fromVersion, '6.1.4', '<')) {
            $this->updateQuery('ALTER TABLE #__acym_mail ADD `headers` TEXT NULL');
            $this->updateQuery('ALTER TABLE #__acym_mail ADD `autosave` LONGTEXT NULL');

            $columns = acym_getColumns('condition');
            if (!in_array('conditions', $columns)) {
                $this->updateQuery('ALTER TABLE #__acym_condition CHANGE `condition` `conditions` LONGTEXT NULL');
            }

            $this->updateQuery('ALTER TABLE #__acym_automation ADD `admin` TINYINT(3) NULL');
        }

        if (version_compare($this->fromVersion, '6.1.5', '<')) {
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

                $newConfig = new stdClass();
                $newConfig->social_icons = json_encode($socialIcons);
                $config->save($newConfig);
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
            $query = 'SELECT subject, id FROM #__acym_mail';

            $mails = acym_loadObjectList($query);
            $mails = $mailClass->encode($mails);

            foreach ($mails as $oneMail) {
                $this->updateQuery('UPDATE #__acym_mail SET `subject` = '.acym_escapeDB($oneMail->subject).' WHERE `id` = '.intval($oneMail->id));
            }
        }

        if (version_compare($this->fromVersion, '6.1.6', '<')) {
            // Handle emojis in body, name, preheader and autosave
            $mails = acym_loadObjectList('SELECT `id`, `name`, `body`, `autosave`, `preheader` FROM #__acym_mail');
            $mails = $mailClass->encode($mails);

            foreach ($mails as $oneMail) {
                $this->updateQuery(
                    'UPDATE #__acym_mail SET `body` = '.acym_escapeDB($oneMail->body).', `autosave` = '.acym_escapeDB($oneMail->autosave).', `name` = '.acym_escapeDB(
                        $oneMail->name
                    ).', `preheader` = '.acym_escapeDB($oneMail->preheader).' WHERE `id` = '.intval($oneMail->id)
                );
            }
        }

        if (version_compare($this->fromVersion, '6.1.7', '<')) {
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

        if (version_compare($this->fromVersion, '6.2.2', '<')) {
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

        if (version_compare($this->fromVersion, '6.4.0', '<')) {
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

        if (version_compare($this->fromVersion, '6.5.0', '<')) {
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
                        /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;'
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

        if (version_compare($this->fromVersion, '6.6.0', '<')) {
            $updateHelper = new UpdateHelper();
            $firstEmail = $mailClass->getOneByName(acym_translation($updateHelper::FIRST_EMAIL_NAME_KEY));
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

        if (version_compare($this->fromVersion, '6.7.0', '<')) {
            $this->updateQuery('DELETE FROM #__acym_configuration WHERE `name` = "small_display"');
            $this->updateQuery('UPDATE #__acym_configuration SET `value` = REPLACE(REPLACE(`value`, ",", "comma"), ";", "semicol") WHERE `name` = "export_separator"');
            $this->updateQuery('ALTER TABLE #__acym_field DROP COLUMN frontend_form');
            $this->updateQuery('ALTER TABLE #__acym_field CHANGE `frontend_profile` `frontend_edition` TINYINT(3) NULL');
            $this->updateQuery('ALTER TABLE #__acym_field CHANGE `backend_profile` `backend_edition` TINYINT(3) NULL');
            $this->updateQuery('ALTER TABLE #__acym_list ADD COLUMN `front_management` INT NULL');
        }

        if (version_compare($this->fromVersion, '6.9.2', '<')) {
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

        if (version_compare($this->fromVersion, '6.10.0', '<')) {
            $fieldColumns = acym_getColumns('field');
            if (in_array('backend_filter', $fieldColumns)) $this->updateQuery('ALTER TABLE #__acym_field DROP COLUMN backend_filter');
            if (in_array('frontend_filter', $fieldColumns)) $this->updateQuery('ALTER TABLE #__acym_field DROP COLUMN frontend_filter');
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

        if (version_compare($this->fromVersion, '6.10.2', '<')) {
            $this->updateQuery('ALTER TABLE #__acym_mail ADD COLUMN `links_language` VARCHAR(10) NOT NULL DEFAULT ""');
        }

        if (version_compare($this->fromVersion, '6.10.4', '<')) {
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

        if (version_compare($this->fromVersion, '6.11.0', '<')) {
            $splashscreenHelper = new SplashscreenHelper();
            $splashscreenHelper->setDisplaySplashscreenForViewName('bounces', 1);

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

        if (version_compare($this->fromVersion, '6.12.0', '<')) {
            $this->updateQuery('ALTER TABLE #__acym_user ADD `tracking` TINYINT(1) NOT NULL DEFAULT 1');
            $this->updateQuery('ALTER TABLE #__acym_list ADD `tracking` TINYINT(1) NOT NULL DEFAULT 1');
            $this->updateQuery('ALTER TABLE #__acym_mail ADD `tracking` TINYINT(1) NOT NULL DEFAULT 1');
            $this->updateQuery('ALTER TABLE #__acym_plugin ADD `settings` LONGTEXT NULL');
        }

        if (version_compare($this->fromVersion, '6.13.0', '<')) {
            $this->updateQuery('ALTER TABLE #__acym_plugin ADD `core` TINYINT(1) NOT NULL DEFAULT 0');
            $this->updateQuery('ALTER TABLE #__acym_plugin CHANGE `latest_version` `latest_version` VARCHAR(10) NOT NULL');
        }

        if (version_compare($this->fromVersion, '6.14.0', '<')) {
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
                        	/*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;'
            );

            // In a release, all the content insertion addons had the structure Volumes/workspace/acymailing/addons/folder_name/plugin.php instead of just folder_name/plugin.php
            if (file_exists(ACYM_ADDONS_FOLDER_PATH.'Volumes')) {
                $wrongAddons = acym_getFolders(ACYM_ADDONS_FOLDER_PATH.'Volumes'.DS.'workspace'.DS.'acymailing'.DS.'addons'.DS);

                $pluginClass = new PluginClass();
                foreach ($wrongAddons as $oneGoneWrong) {
                    $pluginClass->downloadAddon($oneGoneWrong, false);
                }

                acym_deleteFolder(ACYM_ADDONS_FOLDER_PATH.'Volumes');
            }

            $this->updateQuery('ALTER TABLE #__acym_user ADD `language` VARCHAR(10) NOT NULL');
            $this->updateQuery('ALTER TABLE #__acym_mail ADD `language` VARCHAR(10) NOT NULL');
            $this->updateQuery('ALTER TABLE #__acym_mail ADD `parent_id` INT NULL');
        }

        if (version_compare($this->fromVersion, '6.15.0', '<')) {
            $this->updateQuery('ALTER TABLE #__acym_user_stat ADD `tracking_sale` FLOAT NULL');
            $this->updateQuery('ALTER TABLE #__acym_user_stat ADD `currency` VARCHAR(10) NULL');
            $this->updateQuery('ALTER TABLE #__acym_campaign ADD `visible` TINYINT(1) NOT NULL DEFAULT 1');
            $this->updateQuery('ALTER TABLE #__acym_user CHANGE `language` `language` VARCHAR(10) NOT NULL DEFAULT ""');
            $this->updateQuery('ALTER TABLE #__acym_mail CHANGE `language` `language` VARCHAR(10) NOT NULL DEFAULT ""');
            $this->updateQuery('ALTER TABLE `#__acym_mail` CHANGE `preheader` `preheader` TEXT NULL');
        }

        if (version_compare($this->fromVersion, '6.16.0', '<')) {
            $this->updateQuery('ALTER TABLE `#__acym_mail_stat` CHANGE `bounce_unique` `bounce_unique` MEDIUMINT(8) NOT NULL DEFAULT 0');
            $this->updateQuery('ALTER TABLE `#__acym_user_stat` CHANGE `bounce` `bounce` TINYINT(4) NOT NULL DEFAULT 0');
            $this->updateQuery('ALTER TABLE #__acym_plugin ADD `type` VARCHAR(20) NOT NULL DEFAULT "ADDON"');
            $this->updateQuery('UPDATE `#__acym_plugin` SET `type` = "ADDON" WHERE `core` = 0');
            $this->updateQuery('UPDATE `#__acym_plugin` SET `type` = "CORE" WHERE `core` = 1');
            $this->updateQuery('ALTER TABLE #__acym_plugin DROP `core`');
            $this->updateQuery('ALTER TABLE `#__acym_form` ADD termspolicy_options LONGTEXT');
            $this->updateQuery('UPDATE `#__acym_form` SET `termspolicy_options` = "{\"termscond\":0,\"privacy\":0}" WHERE `termspolicy_options` is NULL');
        }
        if (version_compare($this->fromVersion, '6.17.0', '<')) {
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
                            /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;
            '
            );
            $automationHelper = new AutomationHelper();
            $automationHelper->deleteUnusedEmails();
            $this->updateQuery('ALTER TABLE `#__acym_form` ADD cookie VARCHAR(30)');
            $this->updateQuery('UPDATE `#__acym_form` SET `cookie` = "{\"cookie_expiration\":1}" WHERE `cookie` IS NULL');
        }

        if (version_compare($this->fromVersion, '6.18.0', '<')) {
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
                        /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;'
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
                        /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;'
            );

            $listClass = new ListClass();

            $this->updateQuery('ALTER TABLE `#__acym_list` ADD `type` VARCHAR(20) NOT NULL DEFAULT '.acym_escapeDB($listClass::LIST_TYPE_STANDARD));
            $this->updateQuery('UPDATE `#__acym_list` SET `type` = '.acym_escapeDB($listClass::LIST_TYPE_FRONT).' WHERE `front_management` = 1');
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
                        /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;'
            );
        }

        if (version_compare($this->fromVersion, '6.18.1', '<')) {
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

        if (version_compare($this->fromVersion, '6.19.0', '<')) {
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

        if (version_compare($this->fromVersion, '7.0.0', '<')) {
            $socialIcons = json_decode($config->get('social_icons', '{}'), true);
            $socialMedias = acym_getSocialMedias();
            foreach ($socialIcons as $oneSocial => $imagePath) {
                if (!in_array($oneSocial, $socialMedias)) {
                    unset($socialIcons[$oneSocial]);
                }
            }

            $newConfig = new stdClass();
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
                    $configurationController->modifyCron('activateCron', $licenseKey, 900);
                }
            }

            $segmentClass = new SegmentClass();
            $segmentClass->updateSegments();
        }

        if (version_compare($this->fromVersion, '7.1.0', '<')) {
            // Welcome and unsubscribe emails have template = 1 for some reason
            $this->updateQuery(
                'UPDATE #__acym_mail 
                SET `type` = '.acym_escapeDB($mailClass::TYPE_TEMPLATE).' 
                WHERE `template` = 1 
                    AND `type` = '.acym_escapeDB($mailClass::TYPE_STANDARD)
            );

            $this->updateQuery('ALTER TABLE #__acym_mail DROP `template`');

            $fieldClass = new FieldClass();
            $fieldClass->insertLanguageField();
        }

        if (version_compare($this->fromVersion, '7.2.0', '<')) {
            $config->save(['built_by_update' => 1]);
            $config->save(['display_built_by' => 0]);
            $this->updateQuery('ALTER TABLE #__acym_user CHANGE `language` `language` VARCHAR(20) NOT NULL DEFAULT ""');
            $this->updateQuery('ALTER TABLE #__acym_mail CHANGE `language` `language` VARCHAR(20) NOT NULL DEFAULT ""');
            $this->updateQuery('ALTER TABLE #__acym_mail CHANGE `links_language` `links_language` VARCHAR(20) NOT NULL DEFAULT ""');

            $this->updateQuery('ALTER TABLE #__acym_list ADD `translation` LONGTEXT NULL');
            $this->updateQuery('ALTER TABLE #__acym_field ADD `translation` LONGTEXT NULL');
            $this->updateQuery('ALTER TABLE #__acym_mail ADD `translation` TEXT NULL');
        }

        if (version_compare($this->fromVersion, '7.2.1', '<')) {
            $this->updateQuery('ALTER TABLE #__acym_mail CHANGE `translation` `translation` TEXT NULL');
        }

        if (version_compare($this->fromVersion, '7.4.0', '<')) {
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

            $rule = new stdClass();
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

        if (version_compare($this->fromVersion, '7.5.0', '<')) {
            $fieldClass = new FieldClass();
            $languageFieldId = $config->get($fieldClass::LANGUAGE_FIELD_ID_KEY, 0);
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

        if (version_compare($this->fromVersion, '7.5.5', '<')) {
            $automationClass = new AutomationClass();
            $adminAutomations = $automationClass->getAutomationsAdmin();

            if (!empty($adminAutomations)) {
                foreach ($adminAutomations as $oneAutomation) {
                    $actions = $automationClass->getActionsByAutomationId($oneAutomation->id);
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
        }

        if (version_compare($this->fromVersion, '7.5.9', '<')) {
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

        if (version_compare($this->fromVersion, '7.5.10', '<')) {
            if (ACYM_CMS == 'joomla') {
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
                    if (file_exists(ACYM_BACK.'dynamics'.DS.$dynamicFolder)) acym_deleteFolder(ACYM_BACK.'dynamics'.DS.$dynamicFolder);
                }
            }
        }

        if (version_compare($this->fromVersion, '7.6.0', '<')) {
            $this->updateQuery('ALTER TABLE #__acym_user CHANGE `key` `key` VARCHAR(30) NULL');
            $this->updateQuery('ALTER TABLE #__acym_mail DROP `library`');
            $this->updateQuery('ALTER TABLE #__acym_user_has_list ADD `unsubscribe_reason` TEXT NULL');
        }

        if (version_compare($this->fromVersion, '7.6.1', '<')) {
            $this->updateQuery('ALTER TABLE #__acym_user_has_list DROP COLUMN `unsubscribe_reason`');
            $this->updateQuery('ALTER TABLE #__acym_history ADD `unsubscribe_reason` TEXT NULL');
        }

        if (version_compare($this->fromVersion, '7.6.1', '<')) {
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

        if (version_compare($this->fromVersion, '7.6.2', '<')) {
            $this->updateQuery('ALTER TABLE `#__acym_form` ADD `message_options` TEXT');
            $this->updateQuery('ALTER TABLE `#__acym_history` CHANGE `ip` `ip` VARCHAR(50)');
            $this->updateQuery('ALTER TABLE `#__acym_user` CHANGE `confirmation_ip` `confirmation_ip` VARCHAR(50)');
        }

        if (version_compare($this->fromVersion, '7.7.6', '<')) {
            $this->updateQuery('ALTER TABLE `#__acym_list` ADD `display_name` VARCHAR(255) NULL');
        }

        if (version_compare($this->fromVersion, '7.8.1', '<')) {
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

        if (version_compare($this->fromVersion, '7.9.2', '<')) {
            $zones = acym_loadObjectList('SELECT `id`, `content` FROM #__acym_custom_zone');
            if (!empty($zones)) {
                foreach ($zones as $oneZone) {
                    $oneZone->content = base64_encode(acym_utf8Decode($oneZone->content));
                    $this->updateQuery('UPDATE `#__acym_custom_zone` SET `content` = '.acym_escapeDB($oneZone->content).' WHERE `id` = '.intval($oneZone->id));
                }
            }
        }

        if (version_compare($this->fromVersion, '7.9.3', '<')) {
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

        if (version_compare($this->fromVersion, '7.9.4', '<')) {
            $this->updateQuery('ALTER TABLE `#__acym_custom_zone` ADD `image` VARCHAR(255) NULL');
        }

        if (version_compare($this->fromVersion, '7.9.6', '<')) {
            if (file_exists(ACYM_ADDONS_FOLDER_PATH.'couryeah')) {
                unlink(ACYM_ADDONS_FOLDER_PATH.'couryeah');
                $this->updateQuery('UPDATE #__acym_configuration SET `name` = "acymailer_domains" WHERE `name` = "couryeah_domains"');
            }
        }

        if (version_compare($this->fromVersion, '8.0.0', '<')) {
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
                        	/*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;'
            );
        }

        if (version_compare($this->fromVersion, '8.1.0', '<')) {
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

        if (version_compare($this->fromVersion, '8.1.1', '<')) {
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

        if (version_compare($this->fromVersion, '8.5.0', '<')) {
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

        if (version_compare($this->fromVersion, '8.6.0', '<')) {
            $ruleClass = new RuleClass();

            $rule = new stdClass();
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

            $rule = new stdClass();
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
                    /*!40100 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci*/;'
            );
        }

        if (version_compare($this->fromVersion, '8.6.2', '<')) {
            $this->updateQuery('DELETE FROM #__acym_campaign WHERE mail_id IS NULL');

            $mailsToClean = acym_loadResultArray(
                'SELECT mail.id  
                FROM `#__acym_mail` AS mail 
                LEFT JOIN #__acym_campaign AS campaign 
                    ON mail.id = campaign.mail_id
                WHERE campaign.mail_id IS NULL
                    AND mail.parent_id IS NULL
                    AND mail.type = '.acym_escapeDB($mailClass::TYPE_STANDARD)
            );

            if (!empty($mailsToClean)) {
                $this->updateQuery('DELETE FROM #__acym_mail_archive WHERE mail_id IN ('.implode(',', $mailsToClean).')');
                $mailClass->delete($mailsToClean);
            }
        }

        if (version_compare($this->fromVersion, '8.7.0', '<')) {
            $this->updateQuery('ALTER TABLE #__acym_plugin DROP `features`');
        }

        if (version_compare($this->fromVersion, '8.7.2', '<')) {
            $socialIcons = json_decode($config->get('social_icons', '{}'), true);
            if (empty($socialIcons['X'])) {
                $socialIcons['X'] = ACYM_MEDIA_URL.'images/logo/x.png';

                $newConfig = new \stdClass();
                $newConfig->social_icons = json_encode($socialIcons);
                $config->save($newConfig);
            }
        }

        if (version_compare($this->fromVersion, '8.7.3', '<')) {
            $socialIcons = json_decode($config->get('social_icons', '{}'), true);

            if (!empty($socialIcons['X'])) {
                unset($socialIcons['X']);

                $newConfig = new stdClass();
                $newConfig->social_icons = json_encode($socialIcons);
                $config->save($newConfig);
            }

            if (empty($socialIcons['x'])) {
                $socialIcons['x'] = ACYM_MEDIA_URL.'images/logo/x.png';

                $newConfig = new \stdClass();
                $newConfig->social_icons = json_encode($socialIcons);
                $config->save($newConfig);
            }
        }

        //__START__joomla_
        if (version_compare($this->fromVersion, '8.7.4', '<')) {
            $config->save(['malicious_scan' => 1]);
        }
        //__END__joomla_

        if (version_compare($this->fromVersion, '8.8.1', '<')) {
            // Replace backslashes by slashes in the upload folder option
            $uploadFolder = $config->get('uploadfolder');
            if (!empty($uploadFolder) && strpos($uploadFolder, '\\') !== false) {
                $uploadFolder = str_replace('\\', '/', $uploadFolder);
                $config->save(['uploadfolder' => $uploadFolder]);
            }
        }

        if (version_compare($this->fromVersion, '9.2.0', '<')) {
            $socialIcons = json_decode($config->get('social_icons', '{}'), true);
            if (empty($socialIcons['telegram'])) {
                $socialIcons['telegram'] = ACYM_MEDIA_URL.'images/logo/telegram.png';

                $newConfig = new \stdClass();
                $newConfig->social_icons = json_encode($socialIcons);
                $config->save($newConfig);
            }

            $this->updateQuery('ALTER TABLE #__acym_mail_stat ADD COLUMN `click_unique` INT NOT NULL DEFAULT 0');
            $this->updateQuery('ALTER TABLE #__acym_mail_stat ADD COLUMN `click_total` INT NOT NULL DEFAULT 0');

            $urlClickClass = new UrlClickClass();
            $mailClicks = $urlClickClass->getTotalClicksPerMail();
            if (!empty($mailClicks)) {
                foreach ($mailClicks as $mailId => $stats) {
                    $this->updateQuery(
                        'UPDATE #__acym_mail_stat 
                        SET click_unique = '.intval($stats->unique_clicks).', click_total = '.intval($stats->total_clicks).' 
                        WHERE mail_id = '.intval($mailId)
                    );
                }
            }
        }

        if (version_compare($this->fromVersion, '9.3.0', '<')) {
            $this->updateQuery('ALTER TABLE #__acym_rule ADD COLUMN `description` VARCHAR(250) NULL AFTER `name`');
        }

        if (version_compare($this->fromVersion, '9.3.1', '<')) {
            $maxOrdering = acym_loadResult('SELECT MAX(ordering) FROM #__acym_rule');
            $this->updateQuery('UPDATE #__acym_rule SET `ordering` = '.intval($maxOrdering + 1).' WHERE `id` = 17');
        }

        if (version_compare($this->fromVersion, '9.4.0', '<')) {
            $config->save([
                'from_email' => acym_strtolower($config->get('from_email')),
                'replyto_email' => acym_strtolower($config->get('replyto_email')),
                'bounce_email' => acym_strtolower($config->get('bounce_email')),
            ]);

            // Make sure that all domains in AcyMailer configuration are in lower cases
            $acymailerParams = $config->get('acymailer_domains', '[]');
            $acymailerParams = @json_decode($acymailerParams, true);
            if (!empty($acymailerParams)) {
                foreach ($acymailerParams as $domain => $domainParams) {
                    $acymailerParams[$domain]['domain'] = acym_strtolower($domainParams['domain']);
                    if (acym_strtolower($domain) === $domain) {
                        continue;
                    }

                    $acymailerParams[acym_strtolower($domain)] = $acymailerParams[$domain];
                    unset($acymailerParams[$domain]);
                }
                $config->save(['acymailer_domains' => json_encode($acymailerParams)]);
            }

            $this->updateQuery('ALTER TABLE `#__acym_field` DROP COLUMN `access`');
        }
    }

    public function updateQuery($query)
    {
        try {
            $res = acym_query($query);
        } catch (Exception $e) {
            $res = null;
        }
        if ($res === null) {
            acym_enqueueMessage(isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200).'...', 'error');
        }
    }

    public function replaceHikaReminder($matches)
    {
        $val = (int)$matches[1] * 86400;

        return '"hikareminder":{"days":"'.$val.'"';
    }

    public function checkDB()
    {
        //TODO fix this, the trait Listing isn't found in the configuration controller.
        // This is due to the way Joomla updates extensions, we need to do the same thing as we did for WordPress
        return;
        $configController = new ConfigurationController();
        $messages = $configController->checkDB('report', false);

        if (empty($messages)) return;

        $isError = false;
        $textMsgs = [];
        foreach ($messages as $oneMsg) {
            if ($oneMsg['error'] == true) $isError = true;
            $textMsgs[] = $oneMsg['msg'];
        }
        if ($isError && !empty($textMsgs)) acym_enqueueMessage(implode('<br />', $textMsgs), 'warning');
    }
}
