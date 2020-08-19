<?php

class acymInstall
{
    var $cms = '{__CMS__}';
    var $level = '{__LEVEL__}';
    var $version = '{__VERSION__}';
    var $update = false;
    var $fromLevel = '';
    var $fromVersion = '';

    public function __construct()
    {
        $path = '';
        if ($this->cms == 'Joomla') {
            $path = rtrim(JPATH_ADMINISTRATOR, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_acym';
        } elseif ($this->cms == 'WordPress') {
            $path = rtrim(__DIR__, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'back';
        }
        include_once $path.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
    }

    // Function to add all pref...
    public function addPref()
    {
        $this->level = ucfirst($this->level);

        $allPref = acym_getDefaultConfigValues();

        $allPref['level'] = $this->level;
        $allPref['version'] = $this->version;
        $allPref['smtp_port'] = '';

        $allPref['from_name'] = acym_getCMSConfig('fromname');
        $allPref['from_email'] = acym_getCMSConfig('mailfrom');
        $allPref['bounce_email'] = acym_getCMSConfig('mailfrom');
        $allPref['mailer_method'] = acym_getCMSConfig('mailer');
        $allPref['sendmail_path'] = acym_getCMSConfig('sendmail');
        $smtpinfos = explode(':', acym_getCMSConfig('smtphost'));
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
        $allPref['embed_files'] = '1';
        $allPref['editor'] = 'codemirror';
        $allPref['multiple_part'] = '1';
        $allPref['smtp_host'] = $smtpinfos[0];
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
        $allPref['uploadfolder'] = ACYM_UPLOAD_FOLDER;
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

        $allPref['Starter'] = '0';
        $allPref['Essential'] = '1';
        $allPref['Enterprise'] = '2';
        $allPref['from_as_replyto'] = '1';
        $allPref['templates_installed'] = '0';

        $allPref['introjs'] = '[]';

        $allPref['bounceVersion'] = 1;

        $allPref['numberThumbnail'] = 2;
        $allPref['daily_hour'] = '12';
        $allPref['daily_minute'] = '00';

        $allPref['social_icons'] = '{"facebook": "'.ACYM_MEDIA_URL.'images/logo/facebook.png", "twitter": "'.ACYM_MEDIA_URL.'images/logo/twitter.png", "instagram": "'.ACYM_MEDIA_URL.'images/logo/instagram.png", "linkedin": "'.ACYM_MEDIA_URL.'images/logo/linkedin.png", "pinterest": "'.ACYM_MEDIA_URL.'images/logo/pinterest.png", "vimeo": "'.ACYM_MEDIA_URL.'images/logo/vimeo.png", "wordpress": "'.ACYM_MEDIA_URL.'images/logo/wordpress.png", "youtube": "'.ACYM_MEDIA_URL.'images/logo/youtube.png"}';

        $allPref['regacy'] = 1;
        $allPref['regacy_delete'] = 1;
        $allPref['regacy_forceconf'] = 0;

        $allPref['install_date'] = time();
        $allPref['remindme'] = '[]';

        $allPref['notifications'] = '{}';

        $allPref['unsubscribe_page'] = 1;

        $allPref['license_key'] = '';
        $allPref['active_cron'] = 0;
        $allPref['cron_updateme_frequency'] = 900;

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

        if (!empty($this->fromVersion) && version_compare($this->fromVersion, '6.10.3', '<')) {
            $config = acym_config();
            $config->setLicenseKeyByDomain();
        }

        return true;
    }

    //Function to update the preferences with the correct values in case of it's needed
    public function updatePref()
    {
        try {
            $results = acym_loadObjectList("SELECT `name`, `value` FROM `#__acym_configuration` WHERE `name` IN ('version','level') LIMIT 2", 'name');
        } catch (Exception $e) {
            $results = null;
        }

        if ($results === null) {
            acym_display(isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200).'...', 'error');

            return false;
        }

        if ($results['version']->value == $this->version && $results['level']->value == $this->level) {
            return true;
        }

        $this->update = true;
        $this->fromLevel = $results['level']->value;
        $this->fromVersion = $results['version']->value;

        //We update the version properly as it's a new one which is now used.
        $query = "REPLACE INTO `#__acym_configuration` (`name`,`value`) VALUES ('level',".acym_escapeDB($this->level)."),('version',".acym_escapeDB($this->version)."),('installcomplete','0')";
        acym_query($query);

        return true;
    }

    //Update the SQL from one version to the other if needed
    public function updateSQL()
    {
        if (!$this->update) return;

        $config = acym_config();

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
            $this->updateQuery('ALTER TABLE #__acym_rule MODIFY `id` INT NOT NULL AUTO_INCREMENT');
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

            $fieldClass = acym_get('class.field');
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
            $mailClass = acym_get('class.mail');
            $query = 'SELECT subject, id FROM #__acym_mail';

            $mails = acym_loadObjectList($query);
            $mails = $mailClass->encode($mails);

            foreach ($mails as $oneMail) {
                $this->updateQuery('UPDATE #__acym_mail SET `subject` = '.acym_escapeDB($oneMail->subject).' WHERE `id` = '.intval($oneMail->id));
            }
        }

        if (version_compare($this->fromVersion, '6.1.6', '<')) {
            // Handle emojis in body, name, preheader and autosave
            $mailClass = acym_get('class.mail');
            $mails = acym_loadObjectList('SELECT `id`, `name`, `body`, `autosave`, `preheader` FROM #__acym_mail');
            $mails = $mailClass->encode($mails);

            foreach ($mails as $oneMail) {
                $this->updateQuery('UPDATE #__acym_mail SET `body` = '.acym_escapeDB($oneMail->body).', `autosave` = '.acym_escapeDB($oneMail->autosave).', `name` = '.acym_escapeDB($oneMail->name).', `preheader` = '.acym_escapeDB($oneMail->preheader).' WHERE `id` = '.intval($oneMail->id));
            }
        }

        if (version_compare($this->fromVersion, '6.1.7', '<')) {
            $actionClass = acym_get('class.action');
            $actions = $actionClass->getAll();
            foreach ($actions as $action) {
                $action->actions = str_replace('{time}', '[time]', $action->actions);
                $action->filters = str_replace('{time}', '[time]', $action->filters);
                $actionClass->save($action);
            }

            $conditionClass = acym_get('class.condition');
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
                'Joomla' => [
                    'cbuilder' => 'com_comprofiler',
                    'eventbooking' => 'com_eventbooking',
                    'hikashop' => 'com_hikashop',
                    'jevents' => 'com_jevents',
                    'payplans' => 'com_payplans',
                    'seblod' => 'com_cck',
                    'virtuemart' => 'com_virtuemart',
                ],
                'WordPress' => [
                    'woocommerce' => 'woocommerce',
                ],
            ];

            $pluginsController = acym_get('controller.plugins');
            foreach ($pluginsBefore650['{__CMS__}'] as $plugin => $extension) {
                $install = false;
                if (defined('JPATH_ADMINISTRATOR') && file_exists(rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.$extension.DS)) {
                    $install = true;
                }

                if (defined('WP_PLUGIN_DIR') && file_exists(rtrim(WP_PLUGIN_DIR, DS).DS.$extension.DS)) {
                    $install = true;
                }

                if ($install) {
                    $error = $pluginsController->download(false, $plugin);
                    if (!empty($error) && true !== $error) {
                        acym_enqueueMessage($error, 'error');
                    }
                }
            }
            $this->updateQuery('ALTER TABLE #__acym_campaign CHANGE `last_trigger` `last_generated` INT DEFAULT NULL');
        }

        if (version_compare($this->fromVersion, '6.6.0', '<')) {
            $updateHelper = acym_get('helper.update');
            $mailClass = acym_get('class.mail');
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
                    '#(<span[^>]+data\-dynamic="{subtag:name[^>]+>[^<]+)(</span>)#Uis',
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
                    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'ico', 'bmp', 'svg'])) {
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
            $mailClass = acym_get('class.mail');
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
                $fieldClass = acym_get('class.field');

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
            $splashscreenHelper = acym_get('helper.splashscreen');
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

                $pluginsController = acym_get('controller.plugins');
                foreach ($wrongAddons as $oneGoneWrong) {
                    $pluginsController->downloadUpload($oneGoneWrong, false);
                }

                acym_deleteFolder(ACYM_ADDONS_FOLDER_PATH.'Volumes');
            }

            $this->updateQuery('ALTER TABLE #__acym_user ADD `language` VARCHAR(10) NOT NULL');
            $this->updateQuery('ALTER TABLE #__acym_mail ADD `language` VARCHAR(10) NOT NULL');
            $this->updateQuery('ALTER TABLE #__acym_mail ADD `parent_id` INT NULL');
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
}
