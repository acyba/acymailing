<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\UrlClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\EncodingHelper;
use AcyMailing\Helpers\ExportHelper;
use AcyMailing\Helpers\HeaderHelper;
use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Helpers\TabHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Libraries\acymController;
use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Types\AclType;
use AcyMailing\Types\DelayType;
use AcyMailing\Types\FailactionType;

class ConfigurationController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_CONFIGURATION')] = acym_completeLink('configuration');
    }

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
                $remindme = json_decode($this->config->get('remindme', []), true);
                if (in_array('sendoverload', $remindme)) {
                    $data['displayWarningOverload'] = true;
                }
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
        $data['acl_advanced'] = [
            'forms' => 'ACYM_SUBSCRIPTION_FORMS',
            'users' => 'ACYM_SUBSCRIBERS',
            'fields' => 'ACYM_CUSTOM_FIELDS',
            'lists' => 'ACYM_LISTS',
            'segments' => 'ACYM_SEGMENTS',
            'campaigns' => 'ACYM_EMAILS',
            'mails' => 'ACYM_TEMPLATES',
            'override' => 'ACYM_EMAILS_OVERRIDE',
            'automation' => 'ACYM_AUTOAMTION',
            'queue' => 'ACYM_QUEUE',
            'plugins' => 'ACYM_ADD_ONS',
            'bounces' => 'ACYM_MAILBOX_ACTIONS',
            'stats' => 'ACYM_STATISTICS',
            'configuration' => 'ACYM_CONFIGURATION',
        ];
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

    /**
     * Check database integrity
     */
    public function checkDB($returnMode = '', $fromConfiguration = true)
    {
        $messagesNoHtml = [];

        //Parse SQL
        $queries = file_get_contents(ACYM_BACK.'tables.sql');
        $tables = explode('CREATE TABLE IF NOT EXISTS ', $queries);
        $structure = [];
        $createTable = [];
        $indexes = [];
        $constraints = [];

        // For each table, get its name, its column names and its indexes / pkey
        foreach ($tables as $oneTable) {
            if (strpos($oneTable, '`#__') !== 0) {
                continue;
            }

            //find tableName
            $tableName = substr($oneTable, 1, strpos($oneTable, '`', 1) - 1);

            $fields = explode("\n", $oneTable);
            foreach ($fields as $key => $oneField) {
                if (strpos($oneField, '#__') === 1) {
                    continue;
                }
                $oneField = rtrim(trim($oneField), ',');

                // Find the column names and remember them
                if (substr($oneField, 0, 1) == '`') {
                    $columnName = substr($oneField, 1, strpos($oneField, '`', 1) - 1);
                    $structure[$tableName][$columnName] = trim($oneField, ',');
                    continue;
                }

                // Remember the primary key and indexes of the table
                if (strpos($oneField, 'PRIMARY KEY') === 0) {
                    $indexes[$tableName]['PRIMARY'] = $oneField;
                } elseif (strpos($oneField, 'INDEX') === 0) {
                    $firstBackquotePos = strpos($oneField, '`');
                    $indexName = substr($oneField, $firstBackquotePos + 1, strpos($oneField, '`', $firstBackquotePos + 1) - $firstBackquotePos - 1);

                    $indexes[$tableName][$indexName] = $oneField;
                } elseif (strpos($oneField, 'FOREIGN KEY') !== false) {
                    preg_match('/(fk.*)\`/Uis', $fields[$key - 1], $matchesConstraints);
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
            $createTable[$tableName] = 'CREATE TABLE IF NOT EXISTS '.$oneTable;
        }


        $columnNames = [];
        $tableNames = array_keys($structure);

        // Good, we have the structure acym SHOULD have, now we get the CURRENT structure so we can compare and add what's missing
        foreach ($tableNames as $oneTableName) {
            try {
                $columns = acym_loadObjectList('SHOW COLUMNS FROM '.$oneTableName);
            } catch (\Exception $e) {
                $columns = null;
            }

            if (!empty($columns)) {
                foreach ($columns as $oneField) {
                    $columnNames[$oneTableName][$oneField->Field] = $oneField->Field;
                }
                continue;
            }

            // We didn't get the columns, the table crashed or doesn't exist

            $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
            $messagesNoHtml[] = ['error' => false, 'color' => 'blue', 'msg' => acym_translationSprintf('ACYM_CHECKDB_LOAD_COLUMNS_ERROR', $oneTableName, $errorMessage)];

            if (strpos($errorMessage, 'marked as crashed')) {
                //The table is apparently crashed, let's repair it!
                $repairQuery = 'REPAIR TABLE '.$oneTableName;

                try {
                    $isError = acym_query($repairQuery);
                } catch (\Exception $e) {
                    $isError = null;
                }

                if ($isError === null) {
                    $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
                    $messagesNoHtml[] = ['error' => true, 'color' => 'red', 'msg' => acym_translationSprintf('ACYM_CHECKDB_REPAIR_TABLE_ERROR', $oneTableName, $errorMessage)];
                } else {
                    $messagesNoHtml[] = ['error' => false, 'color' => 'green', 'msg' => acym_translationSprintf('ACYM_CHECKDB_REPAIR_TABLE_SUCCESS', $oneTableName)];
                }
                continue;
            }

            //Table does not exist? lets create it...
            try {
                $isError = acym_query($createTable[$oneTableName]);
            } catch (\Exception $e) {
                $isError = null;
            }

            if ($isError === null) {
                $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
                $messagesNoHtml[] = ['error' => true, 'color' => 'red', 'msg' => acym_translationSprintf('ACYM_CHECKDB_CREATE_TABLE_ERROR', $oneTableName, $errorMessage)];
            } else {
                $messagesNoHtml[] = ['error' => false, 'color' => 'green', 'msg' => acym_translationSprintf('ACYM_CHECKDB_CREATE_TABLE_SUCCESS', $oneTableName)];
            }
        }

        //Add missing columns in tables
        foreach ($tableNames as $oneTableName) {
            if (empty($columnNames[$oneTableName])) continue;

            $idealColumnNames = array_keys($structure[$oneTableName]);
            $missingColumns = array_diff($idealColumnNames, $columnNames[$oneTableName]);

            if (!empty($missingColumns)) {
                // Some columns are missing, add them
                foreach ($missingColumns as $oneColumn) {
                    $messagesNoHtml[] = ['error' => false, 'color' => 'blue', 'msg' => acym_translationSprintf('ACYM_CHECKDB_MISSING_COLUMN', $oneColumn, $oneTableName)];
                    try {
                        $isError = acym_query('ALTER TABLE '.$oneTableName.' ADD '.$structure[$oneTableName][$oneColumn]);
                    } catch (\Exception $e) {
                        $isError = null;
                    }
                    if ($isError === null) {
                        $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
                        $messagesNoHtml[] = [
                            'error' => true,
                            'color' => 'red',
                            'msg' => acym_translationSprintf('ACYM_CHECKDB_ADD_COLUMN_ERROR', $oneColumn, $oneTableName, $errorMessage),
                        ];
                    } else {
                        $messagesNoHtml[] = ['error' => false, 'color' => 'green', 'msg' => acym_translationSprintf('ACYM_CHECKDB_ADD_COLUMN_SUCCESS', $oneColumn, $oneTableName)];
                    }
                }
            }


            // Add missing index and primary keys
            $results = acym_loadObjectList('SHOW INDEX FROM '.$oneTableName, 'Key_name');
            if (empty($results)) {
                $results = [];
            }

            foreach ($indexes[$oneTableName] as $name => $query) {
                $name = acym_prepareQuery($name);
                if (in_array($name, array_keys($results))) continue;

                // The index / primary key is missing, add it

                $keyName = $name == 'PRIMARY' ? 'primary key' : 'index '.$name;

                $messagesNoHtml[] = ['error' => false, 'color' => 'blue', 'msg' => acym_translationSprintf('ACYM_CHECKDB_MISSING_INDEX', $keyName, $oneTableName)];
                try {
                    $isError = acym_query('ALTER TABLE '.$oneTableName.' ADD '.$query);
                } catch (\Exception $e) {
                    $isError = null;
                }

                if ($isError === null) {
                    $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
                    $messagesNoHtml[] = [
                        'error' => true,
                        'color' => 'red',
                        'msg' => acym_translationSprintf('ACYM_CHECKDB_ADD_INDEX_ERROR', $keyName, $oneTableName, $errorMessage),
                    ];
                } else {
                    $messagesNoHtml[] = ['error' => false, 'color' => 'green', 'msg' => acym_translationSprintf('ACYM_CHECKDB_ADD_INDEX_SUCCESS', $keyName, $oneTableName)];
                }
            }

            if (empty($constraints[$oneTableName])) continue;
            $tableNameQuery = str_replace('#__', acym_getPrefix(), $oneTableName);
            $databaseName = acym_loadResult('SELECT DATABASE();');
            $foreignKeys = acym_loadObjectList(
                'SELECT i.TABLE_NAME, i.CONSTRAINT_TYPE, i.CONSTRAINT_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME, k.COLUMN_NAME
                                                FROM information_schema.TABLE_CONSTRAINTS AS i 
                                                LEFT JOIN information_schema.KEY_COLUMN_USAGE AS k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME 
                                                WHERE i.TABLE_NAME = '.acym_escapeDB($tableNameQuery).' AND i.CONSTRAINT_TYPE = "FOREIGN KEY" AND i.TABLE_SCHEMA = '.acym_escapeDB(
                    $databaseName
                ),
                'CONSTRAINT_NAME'
            );

            acym_query('SET foreign_key_checks = 0');

            foreach ($constraints[$oneTableName] as $constraintName => $constraintInfo) {
                $constraintTableNamePrefix = str_replace('#__', acym_getPrefix(), $constraintInfo['table']);
                $constraintName = str_replace('#__', acym_getPrefix(), $constraintName);
                if (empty($foreignKeys[$constraintName]) || (!empty($foreignKeys[$constraintName]) && ($foreignKeys[$constraintName]->REFERENCED_TABLE_NAME != $constraintTableNamePrefix || $foreignKeys[$constraintName]->REFERENCED_COLUMN_NAME != $constraintInfo['table_column'] || $foreignKeys[$constraintName]->COLUMN_NAME != $constraintInfo['column']))) {
                    $messagesNoHtml[] = ['error' => false, 'color' => 'blue', 'msg' => acym_translationSprintf('ACYM_CHECKDB_WRONG_FOREIGN_KEY', $constraintName, $oneTableName)];

                    if (!empty($foreignKeys[$constraintName])) {
                        try {
                            $isError = acym_query('ALTER TABLE `'.$oneTableName.'` DROP FOREIGN KEY `'.$constraintName.'`');
                        } catch (\Exception $e) {
                            $isError = null;
                        }
                        if ($isError === null) {
                            $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
                            $messagesNoHtml[] = [
                                'error' => true,
                                'color' => 'red',
                                'msg' => acym_translationSprintf('ACYM_CHECKDB_ADD_FOREIGN_KEY_ERROR', $constraintName, $oneTableName, $errorMessage),
                            ];
                            continue;
                        }
                    }

                    try {
                        $isError = acym_query(
                            'ALTER TABLE `'.$oneTableName.'` ADD CONSTRAINT `'.$constraintName.'` FOREIGN KEY (`'.$constraintInfo['column'].'`) REFERENCES `'.$constraintInfo['table'].'` (`'.$constraintInfo['table_column'].'`) ON DELETE NO ACTION ON UPDATE NO ACTION;'
                        );
                    } catch (\Exception $e) {
                        $isError = null;
                    }

                    if ($isError === null) {
                        $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
                        $messagesNoHtml[] = [
                            'error' => true,
                            'color' => 'red',
                            'msg' => acym_translationSprintf('ACYM_CHECKDB_ADD_FOREIGN_KEY_ERROR', $constraintName, $oneTableName, $errorMessage),
                        ];
                    } else {
                        $messagesNoHtml[] = [
                            'error' => false,
                            'color' => 'green',
                            'msg' => acym_translationSprintf('ACYM_CHECKDB_ADD_FOREIGN_KEY_SUCCESS', $constraintName, $oneTableName),
                        ];
                    }
                }
            }
            acym_query('SET foreign_key_checks = 1');
        }

        // Clean the duplicates in the acym_url table, caused by a bug before the 12/04/19
        if ($fromConfiguration) {
            $urlClass = new UrlClass();
            $duplicatedUrls = $urlClass->getDuplicatedUrls();

            if (!empty($duplicatedUrls)) {
                $time = time();
                $interrupted = false;
                $messagesNoHtml[] = ['error' => false, 'color' => 'blue', 'msg' => acym_translation('ACYM_CHECKDB_DUPLICATED_URLS')];

                // Make sure we don't reach the max execution time
                $maxexecutiontime = intval($this->config->get('max_execution_time'));
                if (empty($maxexecutiontime) || $maxexecutiontime - 20 < 20) {
                    $maxexecutiontime = 20;
                } else {
                    $maxexecutiontime -= 20;
                }

                acym_increasePerf();
                while (!empty($duplicatedUrls)) {
                    $urlClass->delete($duplicatedUrls);

                    if (time() - $time > $maxexecutiontime) {
                        $interrupted = true;
                        break;
                    }

                    $duplicatedUrls = $urlClass->getDuplicatedUrls();
                }
                if (empty($interrupted)) {
                    $messagesNoHtml[] = ['error' => false, 'color' => 'green', 'msg' => acym_translation('ACYM_CHECKDB_DUPLICATED_URLS_SUCCESS')];
                } else {
                    $messagesNoHtml[] = ['error' => false, 'color' => 'blue', 'msg' => acym_translation('ACYM_CHECKDB_DUPLICATED_URLS_REMAINING')];
                }
            }
        }

        // Add a key for users that don't have one
        $userClass = new UserClass();
        $addedKeys = $userClass->addMissingKeys();
        if (!empty($addedKeys)) {
            $messagesNoHtml[] = ['error' => false, 'color' => 'green', 'msg' => acym_translationSprintf('ACYM_CHECKDB_ADDED_KEYS', $addedKeys)];
        }

        if ($returnMode == 'report') {
            return $messagesNoHtml;
        }

        if (empty($messagesNoHtml)) {
            echo '<i class="acymicon-check-circle acym__color__green"></i>';
        } else {
            $nbMessages = count($messagesNoHtml);
            foreach ($messagesNoHtml as $i => $oneMsg) {
                echo '<span style="color:'.$oneMsg['color'].'">'.$oneMsg['msg'].'</span>';
                if ($i < $nbMessages) echo '<br />';
            }
        }

        exit;
    }

    /**
     * Save configuration
     * @return bool
     */
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
                if (strpos($index, 'acl') !== false) unset($formData[$index]);
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
            $formData[$oneField] = !empty($formData[$oneField]) ? $formData[$oneField] : [];
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

    public function ports()
    {
        if (!function_exists('fsockopen')) {
            echo '<span style="color:red">'.acym_translation('ACYM_FSOCKOPEN').'</span>';
            exit;
        }

        $tests = [
            25 => 'smtp.sendgrid.com',
            2525 => 'smtp.sendgrid.com',
            587 => 'smtp.sendgrid.com',
            465 => 'ssl://smtp.gmail.com',
        ];
        $total = 0;
        foreach ($tests as $port => $server) {
            $fp = @fsockopen($server, $port, $errno, $errstr, 5);
            if ($fp) {
                echo '<span style="color:#3dea91">'.acym_translationSprintf('ACYM_SMTP_AVAILABLE_PORT', $port).'</span><br />';
                fclose($fp);
                $total++;
            } else {
                echo '<span style="color:#ff5259">'.acym_translationSprintf('ACYM_SMTP_NOT_AVAILABLE_PORT', $port, $errno.' - '.utf8_encode($errstr)).'</span><br />';
            }
        }

        exit;
    }

    public function detecttimeout()
    {
        acym_query('REPLACE INTO `#__acym_configuration` (`name`,`value`) VALUES ("max_execution_time","5"), ("last_maxexec_check","'.time().'")');
        //We try to extend it...
        @ini_set('max_execution_time', 600);
        @ignore_user_abort(true);
        $i = 0;
        //Max 8 minutes anyway...
        while ($i < 480) {
            sleep(8);
            $i += 10;
            acym_query('UPDATE `#__acym_configuration` SET `value` = "'.intval($i).'" WHERE `name` = "max_execution_time"');
            acym_query('UPDATE `#__acym_configuration` SET `value` = "'.intval(time()).'" WHERE `name` = "last_maxexec_check"');
            //We do that every 10 seconds...
            sleep(2);
        }
        exit;
    }

    public function deletereport()
    {
        $path = trim(html_entity_decode($this->config->get('cron_savepath')));
        if (!preg_match('#^[a-z0-9/_\-{}]*\.log$#i', $path)) {
            acym_enqueueMessage(acym_translation('ACYM_WRONG_LOG_NAME'), 'error');

            return;
        }

        $path = str_replace(['{year}', '{month}'], [date('Y'), date('m')], $this->config->get('cron_savepath'));
        $reportPath = acym_cleanPath(ACYM_ROOT.$path);

        if (is_file($reportPath)) {
            $result = acym_deleteFile($reportPath);
            if ($result) {
                acym_enqueueMessage(acym_translation('ACYM_SUCC_DELETE_LOG'), 'success');
            } else {
                acym_enqueueMessage(acym_translation('ACYM_ERROR_DELETE_LOG'), 'error');
            }
        } else {
            acym_enqueueMessage(acym_translation('ACYM_EXIST_LOG'), 'info');
        }

        return $this->listing();
    }

    public function seereport()
    {
        acym_noCache();

        $path = trim(html_entity_decode($this->config->get('cron_savepath')));
        if (!preg_match('#^[a-z0-9/_\-{}]*\.log$#i', $path)) {
            acym_display(acym_translation('ACYM_WRONG_LOG_NAME'), 'error');
        }

        $path = str_replace(['{year}', '{month}'], [date('Y'), date('m')], $path);
        $reportPath = acym_cleanPath(ACYM_ROOT.$path);

        if (file_exists($reportPath) && !is_dir($reportPath)) {
            try {
                $lines = 5000;
                $f = fopen($reportPath, "rb");
                fseek($f, -1, SEEK_END);
                if (fread($f, 1) != "\n") {
                    $lines -= 1;
                }

                $report = '';
                while (ftell($f) > 0 && $lines >= 0) {
                    $seek = min(ftell($f), 4096); // Figure out how far back we should jump
                    fseek($f, -$seek, SEEK_CUR);
                    $report = ($chunk = fread($f, $seek)).$report; // Get the line
                    fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
                    $lines -= substr_count($chunk, "\n"); // Move to previous line
                }

                while ($lines++ < 0) {
                    $report = substr($report, strpos($report, "\n") + 1);
                }
                fclose($f);
            } catch (\Exception $e) {
                $report = '';
            }
        }

        if (empty($report)) {
            $report = acym_translation('ACYM_EMPTY_LOG');
        }

        echo nl2br($report);
        exit;
    }

    public function redomigration()
    {
        $newConfig = new \stdClass();
        $newConfig->migration = 0;
        $this->config->save($newConfig);

        acym_redirect(acym_completeLink('dashboard', false, true));
    }

    public function removeNotification()
    {
        $whichNotification = acym_getVar('string', 'id');

        if ($whichNotification != 0 && empty($whichNotification)) {
            acym_sendAjaxResponse(acym_translation('ACYM_NOTIFICATION_NOT_FOUND'), [], false);
        }

        if ('all' === $whichNotification) {
            $this->config->save(['notifications' => '[]']);
            $notifications = [];
        } else {
            $notifications = json_decode($this->config->get('notifications', '[]'), true);
            unset($notifications[$whichNotification]);
            $this->config->save(['notifications' => json_encode($notifications)]);
        }
        $helperHeader = new HeaderHelper();

        acym_sendAjaxResponse('', ['html' => $helperHeader->getNotificationCenterInner($notifications)]);
    }

    public function markNotificationRead()
    {
        $which = acym_getVar('string', 'id');

        $notifications = json_decode($this->config->get('notifications', '[]'), true);
        if (empty($notifications)) {
            acym_sendAjaxResponse('', []);
        }

        if (empty($which)) {
            foreach ($notifications as $key => $notification) {
                $notifications[$key]['read'] = true;
            }
        } else {
            foreach ($notifications as $key => $notification) {
                if ($notification['id'] != $which) continue;
                $notifications[$key]['read'] = true;
            }
        }


        $this->config->save(['notifications' => json_encode($notifications)]);

        acym_sendAjaxResponse('', []);
    }

    public function addNotification()
    {
        $message = acym_getVar('string', 'message');
        $level = acym_getVar('string', 'level');

        if (empty($message) || empty($level)) {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR'), [], false);
        }

        $helperHeader = new HeaderHelper();

        $newNotification = new \stdClass();
        $newNotification->message = $message;
        $newNotification->level = $level;
        $newNotification->read = false;
        $newNotification->date = time();

        $helperHeader->addNotification($newNotification);

        acym_sendAjaxResponse('', ['notificationCenter' => $helperHeader->getNotificationCenter()]);
    }

    public function getAjax()
    {
        acym_checkToken();

        $field = acym_getVar('string', 'field', '');
        $res = $this->config->get($field, '');

        if (empty($res)) {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_LOAD_INFORMATION'), [], false);
        } else {
            acym_sendAjaxResponse('', ['value' => $res]);
        }
    }

    public function unlinkLicense()
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return true;
        }
        //__END__demo_
        $config = acym_getVar('array', 'config', []);
        $licenseKey = empty($config['license_key']) ? $this->config->get('license_key') : $config['license_key'];

        $resultUnlinkLicenseOnUpdateMe = $this->unlinkLicenseOnUpdateMe($licenseKey);

        if ($resultUnlinkLicenseOnUpdateMe['success'] === true) {
            $this->config->save(['license_key' => '']);
        }

        if (!empty($resultUnlinkLicenseOnUpdateMe['message'])) {
            $this->displayMessage($resultUnlinkLicenseOnUpdateMe['message']);
        }

        //Display the configuration
        $this->listing();

        return true;
    }

    public function attachLicense()
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return true;
        }
        //__END__demo_
        $config = acym_getVar('array', 'config', []);
        $licenseKey = $config['license_key'];

        if (empty($licenseKey)) {
            $this->displayMessage(acym_translation('ACYM_PLEASE_SET_A_LICENSE_KEY'));
            $this->listing();

            return true;
        }

        //We save the license key
        $this->config->save(['license_key' => $licenseKey]);

        //We call updateme to attach the website to the license
        $resultAttachLicenseOnUpdateMe = $this->attachLicenseOnUpdateMe();

        if ($resultAttachLicenseOnUpdateMe['success'] === false) {
            $this->config->save(['license_key' => '']);
        }

        if (!empty($resultAttachLicenseOnUpdateMe['message'])) {
            $this->displayMessage($resultAttachLicenseOnUpdateMe['message']);
        }

        $this->listing();

        return true;
    }

    public function attachLicenseOnUpdateMe($licenseKey = null)
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return true;
        }
        //__END__demo_

        //We get the license key saved
        if (is_null($licenseKey)) {
            $licenseKey = $this->config->get('license_key', '');
        }

        $return = [
            'message' => '',
            'success' => false,
        ];

        if (empty($licenseKey)) {
            $return['message'] = 'LICENSE_NOT_FOUND';

            return $return;
        }

        $url = ACYM_UPDATEMEURL.'license&task=attachWebsiteKey';

        $fields = [
            'domain' => ACYM_LIVE,
            'license_key' => $licenseKey,
        ];

        $resultAttach = acym_makeCurlCall($url, $fields);

        acym_checkVersion();

        //If it's not the result well formatted => don't save the license key and out
        if (empty($resultAttach) || !empty($resultAttach['error'])) {
            $return['message'] = empty($resultAttach['error']) ? '' : $resultAttach['error'];

            return $return;
        }

        $return['message'] = $resultAttach['message'];
        //If there is an error when the website has been attached => don't save the license key in the configuration
        if ($resultAttach['type'] == 'error') {

            return $return;
        }

        $return['success'] = true;

        acym_trigger('onAcymAttachLicense', [&$licenseKey]);

        return $return;
    }

    private function unlinkLicenseOnUpdateMe($licenseKey = null)
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return true;
        }
        //__END__demo_
        //We get the license key saved
        if (is_null($licenseKey)) {
            $licenseKey = $this->config->get('license_key', '');
        }

        $level = $this->config->get('level', '');

        $return = [
            'message' => '',
            'success' => false,
        ];

        if (empty($licenseKey)) {
            $return['message'] = 'LICENSE_NOT_FOUND';

            return $return;
        }

        //First let's deactivate the cron
        $this->deactivateCron(false, $licenseKey);

        $url = ACYM_UPDATEMEURL.'license&task=unlinkWebsiteFromLicense';

        $fields = [
            'domain' => ACYM_LIVE,
            'license_key' => $licenseKey,
            'level' => $level,
            'component' => ACYM_COMPONENT_NAME_API,
        ];

        //Call updateme to unlink the license from this website
        $resultUnlink = acym_makeCurlCall($url, $fields);

        acym_checkVersion();

        //If it's not the result well formated => out
        if (empty($resultUnlink) || !empty($resultUnlink['error'])) {
            $return['message'] = empty($resultUnlink['error']) ? '' : $resultUnlink['error'];

            return $return;
        }

        if ($resultUnlink['type'] === 'error') {
            //If we can't retrieve the license, we set that the unlink is ok.
            //Example: if you don't have the license on acymailing.com, you need to unlink the license
            if ($resultUnlink['message'] == 'LICENSE_NOT_FOUND' || $resultUnlink['message'] == 'LICENSES_DONT_MATCH') {
                $return['message'] = 'UNLINK_SUCCESSFUL';
                $return['success'] = true;

                return $return;
            }
        }

        if ($resultUnlink['type'] === 'info') {
            $return['success'] = true;
        }

        $return['message'] = $resultUnlink['message'];

        acym_trigger('onAcymDetachLicense');

        return $return;
    }

    public function activateCron($licenseKey = null)
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return true;
        }
        //__END__demo_
        $result = $this->modifyCron('activateCron', $licenseKey);
        //If everything went ok we save config with a active_cron to true
        if ($result !== false && $this->displayMessage($result['message'])) $this->config->save(['active_cron' => 1]);
        $this->listing();

        return true;
    }

    //The listing parameter allows us to know if we need to display the listing or not
    public function deactivateCron($listing = true, $licenseKey = null)
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return true;
        }
        //__END__demo_
        $result = $this->modifyCron('deactivateCron', $licenseKey);
        //If everything went ok we save config with a active_cron to false
        if ($result !== false && $this->displayMessage($result['message'])) $this->config->save(['active_cron' => 0]);
        if ($listing) $this->listing();

        return true;
    }

    //The listing parameter allows us to know if we need to display the listing or not
    public function modifyCron($functionToCall, $licenseKey = null)
    {
        if (is_null($licenseKey)) {
            $config = acym_getVar('array', 'config', []);
            $licenseKey = empty($config['license_key']) ? '' : $config['license_key'];
        }

        //If the license is not set => out
        if (empty($licenseKey)) {
            $this->displayMessage('LICENSE_NOT_FOUND');

            return false;
        }

        $url = ACYM_UPDATEMEURL.'launcher&task='.$functionToCall;

        $fields = [
            'domain' => ACYM_LIVE,
            'license_key' => $licenseKey,
            'cms' => ACYM_CMS,
            'frequency' => 900,
            'level' => $this->config->get('level', ''),
            'url_version' => 'secured',
        ];

        //We call updateme to activate/deactivate the cron
        $result = acym_makeCurlCall($url, $fields);


        //If it's not the result well formated => out
        if (empty($result) || !empty($result['error'])) {
            $this->displayMessage(empty($result['error']) ? '' : $result['error']);

            return false;
        }

        //If there is an error during the process on updateme => out
        if ($result['type'] == 'error') {
            $this->displayMessage($result['message']);

            return false;
        }

        return $result;
    }

    public function displayMessage($message, $ajax = false)
    {
        $correspondences = [
            'WEBSITE_NOT_FOUND' => ['message' => 'ACYM_WEBSITE_NOT_FOUND', 'type' => 'error'],
            'LICENSE_NOT_FOUND' => ['message' => 'ACYM_LICENSE_NOT_FOUND', 'type' => 'error'],
            'WELL_ATTACH' => ['message' => 'ACYM_LICENSE_WELL_ATTACH', 'type' => 'info'],
            'ISSUE_WHILE_ATTACH' => ['message' => 'ACYM_ISSUE_WHILE_ATTACHING_LICENSE', 'type' => 'error'],
            'ALREADY_ATTACH' => ['message' => 'ACYM_LICENSE_ALREADY_ATTACH', 'type' => 'info'],
            'LICENSES_DONT_MATCH' => ['message' => 'ACYM_CANT_UNLINK_WEBSITE_LICENSE_DONT_MATCH', 'type' => 'error'],
            'MAX_SITES_ATTACH' => ['message' => 'ACYM_YOU_REACH_THE_MAX_SITE_ATTACH', 'type' => 'error'],
            'SITE_NOT_FOUND' => ['message' => 'ACYM_ISSUE_WHILE_ATTACHING_LICENSE', 'type' => 'error'],
            'UNLINK_SUCCESSFUL' => ['message' => 'ACYM_LICENSE_UNLINK_SUCCESSFUL', 'type' => 'info'],
            'UNLINK_FAILED' => ['message' => 'ACYM_ERROR_WHILE_UNLINK_LICENSE', 'type' => 'error'],
            'CRON_WELL_ACTIVATED' => ['message' => 'ACYM_AUTOMATIC_SEND_PROCESS_WELL_ACTIVATED', 'type' => 'info'],
            'CRON_WELL_DEACTIVATED' => ['message' => 'ACYM_AUTOMATIC_SEND_PROCESS_WELL_DEACTIVATED', 'type' => 'info'],
            'CRON_NOT_SAVED' => ['message' => 'ACYM_AUTOMATIC_SEND_PROCESS_NOT_ENABLED', 'type' => 'error'],
        ];

        if (!$ajax) {
            if (empty($message) || empty($correspondences[$message])) {
                acym_enqueueMessage(acym_translation('ACYM_ERROR_ON_CALL_ACYBA_WEBSITE'), 'error');

                if (!empty($message)) acym_enqueueMessage(acym_translationSprintf('ACYM_CURL_ERROR_MESSAGE', $message), 'error');

                return false;
            }

            acym_enqueueMessage(acym_translation($correspondences[$message]['message']), $correspondences[$message]['type']);

            return $correspondences[$message]['type'] == 'info';
        } else {
            if (empty($message) || empty($correspondences[$message])) {
                $response = ['message' => acym_translation('ACYM_ERROR_ON_CALL_ACYBA_WEBSITE'), 'type' => 'error'];

                if (!empty($message)) $response['message'] = acym_translationSprintf('ACYM_CURL_ERROR_MESSAGE', $message);

                return $response;
            }

            $response = $correspondences[$message];
            $response['message'] = acym_translation($response['message']);

            return $response;
        }
    }

    public function multilingual()
    {
        $remindme = json_decode($this->config->get('remindme', '[]'), true);
        $remindme[] = 'multilingual';
        $this->config->save(['remindme' => json_encode($remindme)]);

        $this->listing();
    }

    public function call($task, $allowedTasks = [])
    {
        $allowedTasks[] = 'markNotificationRead';
        $allowedTasks[] = 'removeNotification';
        $allowedTasks[] = 'getAjax';
        $allowedTasks[] = 'addNotification';

        parent::call($task, $allowedTasks);
    }

    public function testCredentialsSendingMethod()
    {
        $sendingMethod = acym_getVar('string', 'sendingMethod', '');
        $config = acym_getVar('array', 'config', []);

        if (empty($sendingMethod) || empty($config)) acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_FIND_SENDING_METHOD'), [], false);
        acym_trigger('onAcymTestCredentialSendingMethod', [$sendingMethod, $config]);
    }

    public function copySettingsSendingMethod()
    {
        $plugin = acym_getVar('string', 'plugin', '');
        $method = acym_getVar('string', 'method', '');

        if (empty($plugin) || empty($method)) acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_RETRIEVE_DATA'), [], false);

        $data = [];

        if ($method == 'from_options') {
            $wpMailSmtpSetting = get_option('wp_mail_smtp', '');
            if (!empty($wpMailSmtpSetting) && !empty($wpMailSmtpSetting['mail'])) {
                $mailSettings = $wpMailSmtpSetting['mail'];

                if (!empty($mailSettings['from_email']) && !empty($mailSettings['from_name'])) {
                    $data['from_email'] = $mailSettings['from_email'];
                    $data['from_name'] = $mailSettings['from_name'];
                }
            }
        } else {
            acym_trigger('onAcymGetSettingsSendingMethodFromPlugin', [&$data, $plugin, $method]);
        }

        if (empty($data)) acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_RETRIEVE_DATA'), [], false);

        acym_sendAjaxResponse('', $data);
    }

    public function synchronizeExistingUsers()
    {
        $sendingMethod = acym_getVar('string', 'sendingMethod', '');

        if (empty($sendingMethod)) acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_FIND_SENDING_METHOD'), [], false);
        acym_trigger('onAcymSynchronizeExistingUsers', [$sendingMethod]);
    }

    function seeLogs()
    {
        $filename = acym_getVar('string', 'filename');

        if (empty($filename) || !acym_fileNameValid($filename)) {
            acym_enqueueMessage(acym_translation('ACYM_FILENAME_EMPTY_OR_NOT_VALID'), 'error');
            $this->listing();

            return;
        }

        $reportPath = acym_getLogPath($filename);

        if (!file_exists($reportPath)) {
            acym_enqueueMessage(acym_translation('ACYM_EXIST_LOG'), 'error');
            $this->listing();

            return;
        }

        if (ACYM_CMS === 'wordpress') @ob_get_clean();

        $final = acym_fileGetContent($reportPath);
        echo nl2br($final);

        exit;
    }


    public function downloadExportChangesFile()
    {
        $current = acym_getVar('boolean', 'export_changes_file_current', true);
        $dateTime = $current ? 'now' : '1 month ago';

        $exportHelper = new ExportHelper();

        $filenameToSearch = $exportHelper->getExportChangesFileName(acym_date($dateTime, 'Y'), acym_date($dateTime, 'm'), false);

        $exportFolder = acym_getLogPath('');
        $files = scandir($exportFolder);
        if (empty($files)) {
            acym_enqueueMessage(acym_translation('ACYM_NO_FILE_TO_EXPORT'), 'info');
            $this->listing();

            return;
        }

        $filename = acym_getLogPath($filenameToSearch);

        $zipFiles = [];

        foreach ($files as $file) {
            if (strpos($file, $filenameToSearch) === false) continue;
            $zipFiles[] = [
                'name' => $file,
                'data' => acym_fileGetContent(acym_getLogPath($file)),
            ];
        }

        if (empty($zipFiles)) {
            acym_enqueueMessage(acym_translation('ACYM_NO_FILE_TO_EXPORT'), 'info');
            $this->listing();

            return;
        }

        acym_createArchive($filename, $zipFiles);

        if (ACYM_CMS === 'wordpress') @ob_get_clean();
        $exportHelper->setDownloadHeaders($filenameToSearch, 'zip');
        readfile($filename.'.zip');
        acym_deleteFile($filename.'.zip');

        exit;
    }

    public function loginForAuth2()
    {
        $auth2Smtp = [
            'smtp.gmail.com' => [
                'baseUrl' => 'https://accounts.google.com/o/oauth2/v2/auth?access_type=offline&prompt=consent&',
                'scope' => 'https%3A%2F%2Fmail.google.com%2F',
            ],
            'smtp-mail.outlook.com' => [
                'baseUrl' => 'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize?response_mode=query&',
                'scope' => 'openid%20offline_access%20https%3A%2F%2Fgraph.microsoft.com%2Fmail.read%20https%3A%2F%2Foutlook.office.com%2FSMTP.Send',
            ],
            'smtp.office365.com' => [
                'baseUrl' => 'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize?response_mode=query&',
                'scope' => 'openid%20offline_access%20https%3A%2F%2Fgraph.microsoft.com%2Fmail.read%20https%3A%2F%2Foutlook.office.com%2FSMTP.Send',
            ],
        ];

        $this->store();

        $smtpHost = strtolower($this->config->get('smtp_host'));
        $clientId = $this->config->get('smtp_clientId');
        $clientSecret = $this->config->get('smtp_secret');
        $redirect_url = $this->config->get('smtp_redirectUrl');

        if (empty($clientId) || empty($clientSecret) || empty($smtpHost) || !array_key_exists($smtpHost, $auth2Smtp)) {
            $this->listing();

            return;
        }

        if (in_array($smtpHost, ['smtp.office365.com', 'smtp-mail.outlook.com'])) {
            $tenant = $this->config->get('smtp_tenant');
            if (empty($tenant)) {
                acym_enqueueMessage(acym_translation('ACYM_TENANT_FIELD_IS_MISSING'), 'error');
                $this->listing();

                return;
            }
            $auth2Smtp[$smtpHost]['baseUrl'] = sprintf($auth2Smtp[$smtpHost]['baseUrl'], $tenant);
        }

        $redirectLink = $auth2Smtp[$smtpHost]['baseUrl'];
        $redirectLink .= 'client_id='.urlencode($clientId);
        $redirectLink .= '&response_type=code';
        $redirectLink .= '&redirect_uri='.urlencode($redirect_url);
        $redirectLink .= '&scope='.$auth2Smtp[$smtpHost]['scope'];
        $redirectLink .= '&state=acymailing';

        acym_redirect($redirectLink);
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
