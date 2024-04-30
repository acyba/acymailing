<?php

namespace AcyMailing\Helpers;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Libraries\acymObject;

class ImportHelper extends acymObject
{
    var $importUserInLists = [];
    var $totalInserted = 0;
    var $totalTry = 0;
    var $totalValid = 0;
    var $allSubid = [];
    //Force the user to be confirmed or not
    var $forceconfirm = false;
    var $generatename = true;
    var $overwrite = false;
    // Import users as blocked
    var $importblocked = false;
    //Number of separators to remove at the end of the file
    var $removeSep = 0;
    //Display the results via the global function or let the function handle it...
    var $dispresults = true;

    //Variables used to handle the import on filter
    public $tableName = '';
    public $dbWhere = [];
    public $fieldsMap = [];

    //Handle messages to say that X users have been subscribed to list Y
    var $subscribedUsers = [];

    public $header;
    public $separator;
    public $columns;

    public function __construct()
    {
        parent::__construct();
        //We do an import... so it might take a lot of times and it also might take some memory... so we increase those limits if we can!
        acym_increasePerf();
    }


    public function file()
    {
        //Step 1 : we copy the file in the correct directory
        $importFile = acym_getVar('array', 'import_file', [], 'files');

        if (empty($importFile['name'])) {
            acym_enqueueMessage(acym_translation('ACYM_PLEASE_BROWSE_FILE_IMPORT'), 'error');

            return false;
        }

        $extension = strtolower(acym_fileGetExt($importFile['name']));

        if (!preg_match('#^(csv)$#Ui', $extension) || preg_match('#\.(php.?|.?htm.?|pl|py|jsp|asp|sh|cgi)$#Ui', $importFile['name'])) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_ACCEPTED_TYPE', acym_escape($extension), $this->config->get('allowed_files')), 'error');

            return false;
        }

        // Checking error upload
        $fileError = $importFile['error'];
        if ($fileError > 0) {
            switch ($fileError) {
                case 1:
                case 2:
                    acym_enqueueMessage(acym_translation('ACYM_UPLOADED_FILE_EXCEED_MAX_FILESIZE_PHP'), 'error');

                    return false;
                case 3:
                    acym_enqueueMessage(acym_translation('ACYM_FILE_UPLOADED_PARTIALLY'), 'error');

                    return false;
                case 4:
                    acym_enqueueMessage(acym_translation('ACYM_NO_FILE_WAS_UPLOADED'), 'error');

                    return false;
                default:
                    acym_enqueueMessage(acym_translationSprintf('ACYM_UNKNOWN_ERROR_UPLOADING_FILE', $fileError), 'error');

                    return false;
            }
        }

        $uploadPath = $this->_createUploadFolder();

        $attachment = new \stdClass();
        $attachment->filename = uniqid('import_').'.csv';
        acym_setVar('acym_import_filename', $attachment->filename);

        //Make sure we can use this sort of extension name!
        $attachment->size = $importFile['size'];

        if (!acym_uploadFile($importFile['tmp_name'], $uploadPath.$attachment->filename)) {
            if (!move_uploaded_file($importFile['tmp_name'], $uploadPath.$attachment->filename)) {
                //If we can not do it with a simple method, let's do it with Joomla and FTP
                acym_enqueueMessage(
                    acym_translationSprintf(
                        'ACYM_FAIL_UPLOAD',
                        '<b><i>'.acym_escape($importFile['tmp_name']).'</i></b>',
                        '<b><i>'.acym_escape($uploadPath.$attachment->filename).'</i></b>'
                    ),
                    'error'
                );
            }
        }

        return true;
    }

    public function textarea()
    {
        $content = acym_getVar('string', 'acym__users__import__from_text__textarea');
        $path = $this->_createUploadFolder();
        $filename = uniqid('import_').'.csv';

        acym_writeFile($path.$filename, $content);
        acym_setVar('acym_import_filename', $filename);

        return true;
    }

    public function cms()
    {
        //Update the users which already have a userid
        $query = 'UPDATE IGNORE '.$this->cmsUserVars->table.' as b, #__acym_user as a SET a.email = b.'.$this->cmsUserVars->email.', a.name = b.'.$this->cmsUserVars->name.', a.active = 1 - b.'.$this->cmsUserVars->blocked.' WHERE a.cms_id = b.'.$this->cmsUserVars->id.' AND a.cms_id IS NOT NULL';
        $nbUpdated = acym_query($query);

        //Step 1 : update the existing ones.
        //Update the users to give them a userid
        $query = 'UPDATE IGNORE '.$this->cmsUserVars->table.' as b, #__acym_user as a SET a.cms_id = b.'.$this->cmsUserVars->id.' WHERE a.email = b.'.$this->cmsUserVars->email;
        $affected = acym_query($query);
        $nbUpdated += intval($affected);

        acym_enqueueMessage(acym_translationSprintf('ACYM_IMPORT_UPDATE', $nbUpdated), 'success');

        //Step 2 : delete the ones which don't exist any more
        $query = 'SELECT a.id FROM #__acym_user as a LEFT JOIN '.$this->cmsUserVars->table.' as b on a.cms_id = b.'.$this->cmsUserVars->id.' WHERE b.'.$this->cmsUserVars->id.' IS NULL AND a.cms_id > 0';
        $deletedSubid = acym_loadResultArray($query);

        $query = 'SELECT a.id FROM #__acym_user as a LEFT JOIN '.$this->cmsUserVars->table.' as b on a.email = b.'.$this->cmsUserVars->email.' WHERE b.'.$this->cmsUserVars->id.' IS NULL AND a.cms_id > 0';
        $deletedSubid = array_merge(acym_loadResultArray($query), $deletedSubid);

        if (!empty($deletedSubid)) {
            $userClass = new UserClass();
            $deletedUsers = $userClass->delete($deletedSubid);
            acym_enqueueMessage(acym_translationSprintf('ACYM_IMPORT_DELETE', $deletedUsers), 'success');
        }

        //Step 3 : insert the new ones
        $time = time();
        $formattedTime = acym_date($time, 'Y-m-d H:i:s');
        $sourceImport = 'Import on '.$formattedTime;
        $query = 'INSERT IGNORE INTO #__acym_user (`name`,`email`,`creation_date`,`active`,`cms_id`, `source`) SELECT `user`.`'.$this->cmsUserVars->name.'`,`user`.`'.$this->cmsUserVars->email.'`,`user`.`'.$this->cmsUserVars->registered.'`,1 - `user`.'.$this->cmsUserVars->blocked.',`user`.`'.$this->cmsUserVars->id.'`,\''.$sourceImport.'\' FROM '.$this->cmsUserVars->table.' AS `user` ';
        $groups = acym_getVar('array', 'groups', []);
        $this->config->save(['import_groups' => implode(',', $groups)]);
        if (!empty($groups)) {
            if (ACYM_CMS === 'joomla') {
                acym_arrayToInteger($groups);
                $query .= ' JOIN #__user_usergroup_map AS `map` ON map.user_id = `user`.`'.$this->cmsUserVars->id.'` WHERE `map`.`group_id` IN ('.implode(', ', $groups).')';
            } else {
                $query .= ' JOIN #__usermeta AS `meta` ON meta.user_id = `user`.`'.$this->cmsUserVars->id.'` AND `meta`.`meta_key` = "#__capabilities"';
                foreach ($groups as $i => $oneGroup) {
                    $groups[$i] = acym_escapeDB('%'.strlen($oneGroup).':"'.$oneGroup.'"%');
                }
                $query .= ' WHERE `meta`.`meta_value` LIKE '.implode(' OR `meta`.`meta_value` LIKE ', $groups);
            }
        }

        $insertedUsers = acym_query($query);

        acym_query('UPDATE #__acym_configuration SET `value` = '.intval($time).' WHERE `name` = \'last_import\'');

        acym_trigger('onAcymAfterCMSUserImport', [$sourceImport]);

        acym_enqueueMessage(acym_translationSprintf('ACYM_IMPORT_NEW_SUBS', $insertedUsers), 'info');

        //Step 4 : subscribe all the registered users to one or several lists
        $lists = $this->getImportedLists();
        $listsSubscribe = [];
        if (!empty($lists)) {
            foreach ($lists as $listid => $val) {
                if (!empty($val)) {
                    $listsSubscribe[] = intval($listid);
                }
            }
        }

        if (empty($listsSubscribe)) {
            return true;
        }

        $query = 'INSERT IGNORE INTO #__acym_user_has_list (`user_id`,`list_id`,`status`,`subscription_date`) ';
        $query .= 'SELECT user.`id`, list.`id`, 1, '.acym_escapeDB(date('Y-m-d H:i:s', time() - date('Z'))).' 
                    FROM #__acym_list AS list, #__acym_user AS user ';
        $conditions = [];
        $conditions[] = 'list.`id` IN ('.implode(',', $listsSubscribe).')';
        $conditions[] = 'user.`cms_id` > 0';

        if (!empty($groups)) {
            if (ACYM_CMS === 'joomla') {
                $query .= ' JOIN #__user_usergroup_map AS `map` ON map.user_id = `user`.`cms_id`';
                $conditions[] = '`map`.`group_id` IN ('.implode(', ', $groups).')';
            } else {
                $query .= ' JOIN #__usermeta AS `meta` ON meta.user_id = `user`.`cms_id` AND `meta`.`meta_key` = "#__capabilities"';
                $conditions[] = '`meta`.`meta_value` LIKE ('.implode(' OR `meta`.`meta_value` LIKE ', $groups).')';
            }
        }

        $query .= ' WHERE '.implode(' AND ', $conditions);
        $nbsubscribed = acym_query($query);
        acym_enqueueMessage(acym_translationSprintf('ACYM_IMPORT_SUBSCRIPTION', $nbsubscribed));

        return true;
    }

    public function database($onlyImport = false)
    {
        $this->forceconfirm = acym_getVar('int', 'import_confirmed_database');


        $table = empty($this->tableName) ? trim(acym_getVar('string', 'tablename')) : $this->tableName;
        $time = time();
        $formattedTime = acym_date($time, 'Y-m-d H:i:s');

        if (empty($table)) {
            acym_enqueueMessage(acym_translation('ACYM_SPECIFYTABLE'), 'warning');

            return false;
        }

        //We got a table... let's load the fields from this table
        $fields = acym_getColumns($table, false, false);
        if (empty($fields)) {
            //there is no field... so we consider it was the wrong table
            acym_enqueueMessage(acym_translation('ACYM_SPECIFYTABLE'), 'warning');

            return false;
        }

        //Ok now we have some fields and now we have a table selected.
        $equivalentFields = empty($this->fieldsMap) ? acym_getVar('array', 'fields', []) : $this->fieldsMap;

        if (empty($equivalentFields['email'])) {
            acym_enqueueMessage(acym_translation('ACYM_SPECIFYFIELDEMAIL'), 'warning');

            return false;
        }

        $select = [];
        //We check the data and create the query
        foreach ($equivalentFields as $acyField => $tableField) {
            $tableField = trim($tableField);
            if (empty($tableField)) {
                continue;
            }
            if (!in_array($tableField, $fields)) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_SPECIFYFIELD', $tableField, implode(' <br> ', $fields)), 'warning');

                return false;
            }
            $select['`'.acym_secureDBColumn($acyField).'`'] = acym_secureDBColumn($tableField);
        }

        if (empty($select['`creation_date`'])) {
            $select['`creation_date`'] = acym_escapeDB(acym_date('now', 'Y-m-d H:i:s'));
        }

        if ($this->forceconfirm && empty($select['`confirmed`'])) {
            $select['`confirmed`'] = 1;
        }

        $sourceTxt = 'Import on '.$formattedTime;
        $select['`source`'] = acym_escapeDB($sourceTxt);

        $query = 'INSERT IGNORE INTO #__acym_user ('.implode(' , ', array_keys($select)).') SELECT '.implode(' , ', $select);
        $query .= ' FROM '.acym_secureDBColumn($table).' WHERE '.acym_secureDBColumn($select['`email`']).' LIKE "%@%"';
        if (!empty($this->dbWhere)) {
            $query .= ' AND ( '.implode(' ) AND (', $this->dbWhere).' )';
        }

        $affectedRows = acym_query($query);

        acym_query('UPDATE #__acym_configuration SET `value` = '.intval($time).' WHERE `name` = "last_import"');

        acym_trigger('onAcymAfterDatabaseUserImport', [$sourceTxt]);

        if ($onlyImport) return true;

        acym_enqueueMessage(acym_translationSprintf('ACYM_IMPORT_NEW_SUBS', $affectedRows), 'info');


        //Step 4 : subscribe all the registered users to one or several lists
        $lists = $this->getImportedLists();
        $listsSubscribe = [];
        if (!empty($lists)) {
            foreach ($lists as $listid => $val) {
                if (!empty($val)) {
                    $listsSubscribe[] = intval($listid);
                }
            }
        }

        if (empty($listsSubscribe)) {
            return true;
        }

        $query = 'INSERT IGNORE INTO #__acym_user_has_list (`user_id`,`list_id`,`status`,`subscription_date`) ';
        $query .= 'SELECT user.`id`, list.`id`, 1, '.acym_escapeDB(date('Y-m-d H:i:s', time() - date('Z'))).' 
                    FROM #__acym_list AS list, #__acym_user AS user 
                    WHERE list.`id` IN ('.implode(',', $listsSubscribe).') 
                        AND user.`email` IN (SELECT '.acym_secureDBColumn($select['`email`']).' FROM '.acym_secureDBColumn($table).')';

        $nbsubscribed = acym_query($query);
        acym_enqueueMessage(acym_translationSprintf('ACYM_IMPORT_SUBSCRIPTION', $nbsubscribed));

        return true;
    }

    public function mailpoet()
    {
        // Step 1 : import subscriber from mailpoet
        $time = time();
        $formattedTime = acym_date($time, 'Y-m-d H:i:s');
        $sourceImport = 'import_on_'.$formattedTime.'_mailpoet';

        $query = 'INSERT IGNORE INTO #__acym_user (`name`,`email`,`creation_date`,`active`, `confirmed`,`cms_id`, `source`) 
                  SELECT CONCAT(`subscriber`.`first_name`, `subscriber`.`last_name`),
                         `subscriber`.`email`,
                         `subscriber`.`created_at`, 
                         IF(`subscriber`.`status` = "inactive", 0, 1),
                         IF(`subscriber`.`status` = "subscribed", 1, 0),
                         `subscriber`.`wp_user_id`,
                         \''.$sourceImport.'\' 
                  FROM #__mailpoet_subscribers AS `subscriber` ';
        $listsMailpoet = acym_getVar('array', 'mailpoet_lists', []);
        if (!empty($listsMailpoet)) {
            acym_arrayToInteger($listsMailpoet);
            $query .= ' JOIN #__mailpoet_subscriber_segment AS sub_segment ON `subscriber`.`id` = `sub_segment`.`subscriber_id` AND `sub_segment`.`segment_id` IN ('.implode(
                    ',',
                    $listsMailpoet
                ).')';
        }
        $query .= ' WHERE `subscriber`.`deleted_at` IS NULL ON DUPLICATE KEY UPDATE `source`='.acym_escapeDB($sourceImport);

        $insertedUsers = acym_query($query);

        acym_query('UPDATE #__acym_configuration SET `value` = '.intval($time).' WHERE `name` = \'last_import\'');

        acym_enqueueMessage(acym_translationSprintf('ACYM_IMPORT_NEW_SUBS', $insertedUsers), 'info');

        // Step 2 : subscribe all the registered users to one or several lists
        $lists = $this->getImportedLists();
        $listsSubscribe = [];
        if (!empty($lists)) {
            foreach ($lists as $listid => $val) {
                if (!empty($val)) {
                    $listsSubscribe[] = intval($listid);
                }
            }
        }

        if (empty($listsSubscribe)) {
            return true;
        }

        $query = 'INSERT IGNORE INTO #__acym_user_has_list (`user_id`,`list_id`,`status`,`subscription_date`) ';
        $query .= 'SELECT user.`id`, list.`id`, 1, '.acym_escapeDB(date('Y-m-d H:i:s', time() - date('Z'))).'
                    FROM #__acym_list AS list, #__acym_user AS user ';
        $conditions = [];
        $conditions[] = 'list.`id` IN ('.implode(',', $listsSubscribe).')';
        $conditions[] = 'user.`source` = '.acym_escapeDB($sourceImport);

        if (!empty($listsMailpoet)) {
            $query .= ' JOIN #__mailpoet_subscribers AS subscriber ON subscriber.email = user.email';
            $query .= ' JOIN #__mailpoet_subscriber_segment AS subscriber_segment ON subscriber.id = subscriber_segment.subscriber_id AND subscriber_segment.segment_id IN ('.implode(
                    ',',
                    $listsMailpoet
                ).')';
        }

        $query .= ' WHERE '.implode(' AND ', $conditions);
        $nbsubscribed = acym_query($query);
        acym_enqueueMessage(acym_translationSprintf('ACYM_IMPORT_SUBSCRIPTION', $nbsubscribed));

        return true;
    }

    private function _createUploadFolder()
    {
        $folderPath = acym_cleanPath(ACYM_ROOT.trim(html_entity_decode(ACYM_MEDIA_FOLDER.'import'))).DS;
        if ('wordpress' === ACYM_CMS) {
            $folderPath = acym_cleanPath(WP_PLUGIN_DIR.DS.ACYM_COMPONENT.DS.'media'.DS.'import').DS;
        }
        if (!is_dir($folderPath)) {
            acym_createDir($folderPath, true, true);
        }

        if (!is_writable($folderPath)) {
            @chmod($folderPath, '0755');
            if (!is_writable($folderPath)) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_WRITABLE_FOLDER', $folderPath), 'warning');
            }
        }

        return $folderPath;
    }

    public function finalizeImport()
    {
        $filename = strtolower(acym_getVar('cmd', 'acym_import_filename'));
        $extension = '.'.acym_fileGetExt($filename);
        $this->forceconfirm = acym_getVar('int', 'import_confirmed_generic', 1);
        $this->generatename = acym_getVar('int', 'import_generate_generic', 1);
        $this->overwrite = acym_getVar('int', 'import_overwrite_generic', 1);

        // Remember user's choices
        $newConfig = new \stdClass();
        $newConfig->import_confirmed = $this->forceconfirm;
        $newConfig->import_generate = $this->generatename;
        $newConfig->import_overwrite = $this->overwrite;
        $this->config->save($newConfig);

        $filename = str_replace(['.', ' '], '_', substr($filename, 0, strpos($filename, $extension))).$extension;
        $uploadPath = ACYM_MEDIA.'import'.DS.$filename;

        if (!file_exists($uploadPath) || !is_file($uploadPath)) {
            acym_enqueueMessage(acym_translation('ACYM_UPLOADED_FILE_NOT_FOUND').' '.$uploadPath, 'error');

            return false;
        }

        $importColumns = acym_getVar('string', 'import_columns');
        if (empty($importColumns)) {
            acym_enqueueMessage(acym_translation('ACYM_COLUMNS_NOT_FOUND'), 'error');

            return false;
        }

        $contentFile = file_get_contents($uploadPath);

        //We convert into the correct charset
        if (acym_getVar('cmd', 'acyencoding', '') != '') {
            $encodingHelper = new EncodingHelper();
            $contentFile = $encodingHelper->change($contentFile, acym_getVar('cmd', 'acyencoding'), 'UTF-8');
        }

        $cutContent = str_replace(["\r\n", "\r"], "\n", $contentFile);
        $allLines = explode("\n", $cutContent);

        $listSeparators = ["\t", ';', ','];
        $separator = ',';
        foreach ($listSeparators as $sep) {
            if (strpos($allLines[0], $sep) !== false) {
                $separator = $sep;
                break;
            }
        }

        if (!empty($listsId)) {
            $allLines[0] .= $sep;
        }

        $importColumns = str_replace(',', $separator, $importColumns);

        if (strpos($allLines[0], '@')) {
            $contentFile = $importColumns."\n".$contentFile;
        } else {
            $allLines[0] = $importColumns;
            $contentFile = implode("\n", $allLines);
        }

        $this->_handleContent($contentFile);

        //We can now delete the file
        unlink($uploadPath);
        $this->_cleanImportFolder();
    }

    public function _handleContent(&$contentFile)
    {
        $success = true;
        $timestamp = time();

        //We convert the file into something valid so that mac or not will be Ok!
        $contentFile = str_replace(["\r\n", "\r"], "\n", $contentFile);
        $importLines = explode("\n", $contentFile);

        //A little trick to take the second line if the first line is empty...
        $i = 0;
        $this->header = '';
        $this->allSubid = [];
        while (empty($this->header) && $i < 10) {
            $this->header = trim($importLines[$i]);
            $i++;
        }

        if (strpos($this->header, '@') && !strpos($this->header, ',') && !strpos($this->header, ';') && !strpos($this->header, "\t")) {
            //We have an @ in the first line so there is definitely an issue there and the first line is not the right one!
            //And we don't have separators so we know the format
            $this->header = 'email';
            $i--;
        }

        //Step 3 : we make sure the header of the file is correct (so it does correspond to columns from the database)
        if (!$this->_autoDetectHeader()) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_IMPORT_HEADER', acym_escape($this->header)), 'error');
            acym_enqueueMessage(acym_translation('ACYM_IMPORT_EMAIL'), 'error');

            return false;
        }

        $numberColumns = count($this->columns);

        $encodingHelper = new EncodingHelper();

        $importUsers = [];

        $errorLines = [];

        $errorMessageInvalidEmails = "";

        $userClass = new UserClass();

        $countUsersBeforeImport = $userClass->getCountTotalUsers();

        $listClass = new ListClass();
        $allLists = $listClass->getAll('name');

        //Step 4 : we start importing the file as everything is OK
        while (isset($importLines[$i])) {
            // If there are quotes in the line, check if it is a broken line or not
            if (strpos($importLines[$i], '"') !== false) {
                $data = [];
                $j = $i + 1;
                // The position is the separator's one, -1 for the beginning as the line does not start by a separator
                $position = -1;

                // Concatenate 30 lines max
                while ($j < ($i + 30)) {

                    // Test if the value is encapsulated by quotes
                    $quoteOpened = substr($importLines[$i], $position + 1, 1) == '"';

                    // If encapsulated, search the end of the value
                    if ($quoteOpened) {
                        $nextQuotePosition = strpos($importLines[$i], '"', $position + 2);
                        // If quotes in the value encapsulated by quotes... find the real closing quote...
                        while ($nextQuotePosition !== false && $nextQuotePosition + 1 != strlen($importLines[$i]) && substr(
                                $importLines[$i],
                                $nextQuotePosition + 1,
                                1
                            ) != $this->separator) {
                            $nextQuotePosition = strpos($importLines[$i], '"', $nextQuotePosition + 1);
                        }
                        // If we didn't find the whole value, then concatenate the current line with the next one
                        if ($nextQuotePosition === false) {
                            // If end of import file, error...
                            if (!isset($importLines[$j])) {
                                break;
                            }

                            $importLines[$i] .= "\n".$importLines[$j];
                            $importLines[$i] = rtrim($importLines[$i], $this->separator);
                            unset($importLines[$j]);
                            $j++;
                            continue;
                        } else {
                            // Found the entire value, add it to data and move the position to the next separator

                            // If the quote is at the end of the line and the line is completed...
                            if (strlen($importLines[$i]) - 1 == $nextQuotePosition) {
                                $data[] = substr($importLines[$i], $position + 1);
                                break;
                            }
                            // ,"toto",value...
                            // 3456789
                            // Start in 3+1 so 4: the first quote
                            // Length = 9+1-(3+1) = 6, the length of "toto".
                            $data[] = substr($importLines[$i], $position + 1, $nextQuotePosition + 1 - ($position + 1));
                            $position = $nextQuotePosition + 1;
                        }
                    } else {
                        // If not encapsulated by quotes, search the next separator
                        $nextSeparatorPosition = strpos($importLines[$i], $this->separator, $position + 1);
                        // If not found, the line is completed
                        if ($nextSeparatorPosition === false) {
                            $data[] = substr($importLines[$i], $position + 1);
                            break;
                        } else { // If found the next separator, add the value in $data and change the position
                            $data[] = substr($importLines[$i], $position + 1, $nextSeparatorPosition - ($position + 1));
                            $position = $nextSeparatorPosition;
                        }
                    }
                }

                // We unset some lines, don't forget to change the remaining lines keys
                $importLines = array_merge($importLines);
            } else {
                //We remove the separators from the end of the string as we don't need them... they will be added as empty string anyway during the import process.
                $data = explode($this->separator, rtrim(trim($importLines[$i]), $this->separator));
            }

            //We clean it... maybe there are other arguments at the end we should remove
            if (!empty($this->removeSep)) {
                for ($b = $numberColumns + $this->removeSep - 1 ; $b >= $numberColumns ; $b--) {
                    if (isset($data[$b]) && (strlen($data[$b]) == 0 || $data[$b] == ' ')) {
                        unset($data[$b]);
                    }
                }
            }

            $i++;
            //We don't handle empty lines...
            if (empty($importLines[$i - 1])) {
                continue;
            }

            $this->totalTry++;
            //Lets try to fix it first...
            if (count($data) > $numberColumns) {
                $copy = $data;
                foreach ($copy as $oneelem => $oneval) {
                    if (!empty($oneval[0]) && $oneval[0] == '"' && $oneval[strlen($oneval) - 1] != '"' && isset($copy[$oneelem + 1]) && $copy[$oneelem + 1][strlen(
                            $copy[$oneelem + 1]
                        ) - 1] == '"') {
                        //We concat both with the separator
                        $data[$oneelem] = $copy[$oneelem].$this->separator.$copy[$oneelem + 1];
                        unset($data[$oneelem + 1]);
                    }
                }
                $data = array_values($data);
            }

            // Not enough columns found...
            if (count($data) < $numberColumns) {
                // If not enough info compared to the header... lets add them as empty! We don't care if the user does not specify everything...
                for ($a = count($data) ; $a < $numberColumns ; $a++) {
                    $data[$a] = '';
                }
            }

            if (count($data) != $numberColumns) {
                $success = false;
                static $errorcount = 0;
                if (empty($errorcount)) {
                    acym_enqueueMessage(acym_translationSprintf('ACYM_IMPORT_ARGUMENTS', $numberColumns), 'warning');
                }
                $errorcount++;

                //If it's for the first line, we return so we don't check the rest and let the user fix it
                if ($this->totalTry == 1) {
                    return false;
                }
                if (empty($errorLines)) {
                    $errorLines[] = 'error,'.$importLines[0];
                }
                $errorLines[] = acym_translation('ACYM_IMPORT_ERROR_WRONG_NUMBER_ARGUMENTS').','.$importLines[$i - 1];
                continue;
            }

            $newUser = new \stdClass();
            $newUser->customfields = [];

            // Handle email column first to be able to use it with listids and listname
            $emailKey = array_search('email', $this->columns);
            $newUser->email = trim(strip_tags($data[$emailKey]), '\'" ');
            // Remove all whitespace type
            $newUser->email = preg_replace("/\s+/u", '', $newUser->email);
            if (!empty($newUser->email)) {
                $newUser->email = acym_punycode($newUser->email);
            }
            $newUser->email = trim(str_replace([' ', "\t"], '', $encodingHelper->change($newUser->email, 'UTF-8', 'ISO-8859-1')));


            if (!acym_isValidEmail($newUser->email)) {
                $success = false;
                static $errorcountfail = 0;
                //We limit to 10 errors otherwise it may break the Joomla messaging system in terms of memory usage
                if ($errorcountfail == 0) {
                    acym_enqueueMessage(acym_translation('ACYM_ADDRESSES_INVALID'), 'warning');
                }
                $errorcountfail++;
                if (empty($errorLines)) {
                    $errorLines[] = 'error,'.$importLines[0];
                }
                $errorLines[] = acym_translation('ACYM_INVALID_EMAIL_ADDRESS').','.$importLines[$i - 1];
                continue;
            }

            foreach ($data as $num => $value) {
                // Already handled the email address
                if ($num == $emailKey) continue;

                $field = $this->columns[$num];

                // Ignored
                if ($field == 1) continue;

                if ($field == 'listids') {
                    //Wee explode the listids separated by "-" and forach of them we add the user in the importUserInLists
                    $liststosub = explode('-', trim($value, '\'" 	'));
                    foreach ($liststosub as $onelistid) {
                        $this->importUserInLists[intval(trim($onelistid))][] = acym_escapeDB($newUser->email);
                    }
                    continue;
                }

                if ($field == 'listname') {
                    $liststosub = explode('-', trim($value, '\'" 	'));
                    foreach ($liststosub as $onelistName) {
                        if (empty($onelistName)) {
                            continue;
                        }
                        $onelistName = trim($onelistName);
                        if (empty($allLists[$onelistName])) {
                            $newList = new \stdClass();
                            $newList->name = $onelistName;
                            $newList->active = 1;
                            $colors = ['#3366ff', '#7240A4', '#7A157D', '#157D69', '#ECE649'];
                            $newList->color = $colors[rand(0, count($colors) - 1)];
                            $listid = $listClass->save($newList);
                            $newList->id = $listid;
                            $allLists[$onelistName] = $newList;
                        }
                        $this->importUserInLists[intval($allLists[$onelistName]->id)][] = acym_escapeDB($newUser->email);
                    }
                    continue;
                }

                // If we assigned the data to an Acy custom field
                if (strpos($field, 'cf_') === 0) {
                    $newUser->customfields[substr($field, 3)] = trim(strip_tags($value), '\'" 	');
                    continue;
                }

                if ($value == 'null') {
                    $newUser->$field = '';
                } else {
                    //We remove anything which should not be there (quotes or spaces or return char or html tags)
                    $newUser->$field = trim(strip_tags($value), '\'" 	');
                }
            }


            //Everything is Ok... we can add the line
            $importUsers[] = $newUser;
            $this->totalValid++;

            //Every 50 users, we handle it
            if ($this->totalValid % 50 == 0) {
                $this->_insertUsers($importUsers, $timestamp);
                $importUsers = [];
            }
        }

        if (!empty($errorLines)) {
            $filename = strtolower(acym_getVar('cmd', 'acym_import_filename', ''));
            if (!empty($filename)) {
                $extension = '.'.acym_fileGetExt($filename);
                $filename = str_replace(['.', ' '], '_', substr($filename, 0, strpos($filename, $extension))).$extension;
                $errorFile = implode("\n", $errorLines);
                acym_writeFile(ACYM_MEDIA.'import'.DS.'error_'.$filename, $errorFile);
                acym_enqueueMessage(
                    '<a target="_blank" href="'.acym_prepareAjaxURL((acym_isAdmin() ? '' : 'front').'users&task=downloadImport').'&filename=error_'.preg_replace(
                        '#\.[^.]*$#',
                        '',
                        $filename
                    ).'&'.acym_noTemplate().'" >'.acym_translation('ACYM_DOWNLOAD_IMPORT_ERRORS').'</a>',
                    'notice'
                );
            }
        }

        $this->_insertUsers($importUsers, $timestamp);

        acym_query('UPDATE #__acym_configuration SET `value` = '.intval($timestamp).' WHERE `name` = \'last_import\'');

        // We could have imported empty values
        acym_query('DELETE FROM #__acym_user_has_field WHERE `value` = ""');

        $countUsersAfterImport = $userClass->getCountTotalUsers();
        $this->totalInserted = $countUsersAfterImport - $countUsersBeforeImport;

        if ($this->dispresults) {
            //All users have been added properly into the database... we will now subscribe the users
            acym_enqueueMessage(
                acym_translationSprintf(
                    'ACYM_IMPORT_REPORTING',
                    $this->totalTry,
                    $this->totalInserted,
                    $this->totalTry - $this->totalValid,
                    $this->totalValid - $this->totalInserted
                ),
                'info'
            );
        }

        $this->_subscribeUsers();

        return $success;
    }

    public function _insertUsers($users, $timestamp)
    {
        if (empty($users)) return true;

        $importedCols = array_keys(get_object_vars($users[0]));
        unset($importedCols[array_search('customfields', $importedCols)]);
        if ($this->forceconfirm) $importedCols[] = 'confirmed';

        foreach ($users as $a => $oneUser) {
            $this->_checkData($users[$a], $timestamp);
        }

        $columns = reset($users);
        $colNames = array_keys(get_object_vars($columns));
        unset($colNames[array_search('customfields', $colNames)]);

        if (!in_array('key', $colNames)) $colNames[] = 'key';

        foreach ($colNames as $oneColumn) {
            acym_secureDBColumn($oneColumn);
        }

        $queryInsertUsers = 'INSERT'.($this->overwrite ? '' : ' IGNORE').' INTO #__acym_user (`'.implode('`,`', $colNames).'`) VALUES (';
        $values = [];
        $customFieldsvalues = [];
        $allemails = [];
        foreach ($users as $oneUser) {
            $value = [];
            acym_trigger('onAcymBeforeUserImport', [&$oneUser]);

            foreach ($oneUser as $map => $oneValue) {
                if ($map === 'customfields') continue;

                $oneValue = htmlspecialchars_decode($oneValue, ENT_QUOTES);

                if ($map === 'active' && $this->importblocked) {
                    $value[] = 0;
                } else {
                    if ($map !== 'id') {
                        $oneValue = acym_escapeDB($oneValue);
                        if ($map === 'email') $allemails[] = $oneValue;
                    } else {
                        $oneValue = intval($oneValue);
                    }

                    $value[] = $oneValue;
                }
            }

            if (!isset($oneUser->key)) $value[] = acym_escapeDB(acym_generateKey(14));
            $values[] = implode(',', $value);

            // Prepare import custom fields
            if (!empty($oneUser->customfields)) $customFieldsvalues[$oneUser->email] = $oneUser->customfields;
        }
        acym_trigger('onAcymUserImport', [&$users]);

        $queryInsertUsers .= implode('),(', $values).')';

        if ($this->overwrite) {
            $queryInsertUsers .= ' ON DUPLICATE KEY UPDATE ';
            foreach ($importedCols as &$oneColumn) {
                acym_secureDBColumn($oneColumn);
                if ($oneColumn == 'key') {
                    $oneColumn = '`'.$oneColumn.'` = `'.$oneColumn.'`';
                } else {
                    $oneColumn = '`'.$oneColumn.'` = VALUES(`'.$oneColumn.'`)';
                }
            }
            $queryInsertUsers .= implode(',', $importedCols);
        }

        acym_query($queryInsertUsers);

        $importedUsers = acym_loadObjectList('SELECT id, email FROM #__acym_user WHERE email IN ('.implode(',', $allemails).')', 'id');

        if (!empty($customFieldsvalues)) {
            $insertValues = [];
            foreach ($importedUsers as $one) {
                if (empty($customFieldsvalues[$one->email])) continue;

                foreach ($customFieldsvalues[$one->email] as $fieldId => $value) {
                    $value = htmlspecialchars_decode($value, ENT_QUOTES);
                    $insertValues[] = '('.intval($one->id).','.intval($fieldId).','.acym_escapeDB($value).')';
                }
            }

            if (!empty($insertValues)) {
                $queryInsertCustomFields = 'INSERT'.($this->overwrite ? '' : ' IGNORE').' INTO #__acym_user_has_field (`user_id`, `field_id`, `value`) VALUES '.implode(
                        ',',
                        $insertValues
                    );
                if ($this->overwrite) {
                    $queryInsertCustomFields .= ' ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)';
                }
                acym_query($queryInsertCustomFields);
            }
        }


        $this->allSubid = array_merge($this->allSubid, array_keys($importedUsers));

        return true;
    }

    public function _checkData(&$user, $timestamp)
    {
        //Created field
        if (empty($user->creation_date)) {
            $user->creation_date = time();
        }

        if (is_numeric($user->creation_date)) {
            $user->creation_date = date('Y-m-d H:i:s', $user->creation_date);
        }

        if (!isset($user->active) || strlen($user->active) == 0) {
            $user->active = 1;
        }

        if ((!isset($user->confirmed) || strlen($user->confirmed) == 0) && $this->forceconfirm) {
            $user->confirmed = 1;
        }

        if (empty($user->source)) {
            $user->source = 'Import on '.acym_date($timestamp, 'Y-m-d H:i:s');
        }

        //name field
        if (empty($user->name) && $this->generatename) {
            $user->name = ucwords(trim(str_replace(['.', '_', '-', 1, 2, 3, 4, 5, 6, 7, 8, 9, 0], ' ', substr($user->email, 0, strpos($user->email, '@')))));
        }
    }

    public function _autoDetectHeader()
    {
        //By default it's the comma
        $this->separator = ',';

        //Remove BOM characters if there is one
        $this->header = str_replace("\xEF\xBB\xBF", "", $this->header);

        //First we automatically detect the separator
        $listSeparators = ["\t", ';', ','];
        foreach ($listSeparators as $sep) {
            if (strpos($this->header, $sep) !== false) {
                $this->separator = $sep;
                break;
            }
        }


        //Then we will check the columns...
        $this->columns = explode($this->separator, $this->header);

        //Clean the headers
        for ($i = count($this->columns) - 1 ; $i >= 0 ; $i--) {
            if (strlen($this->columns[$i]) == 0) {
                unset($this->columns[$i]);
                $this->removeSep++;
            }
        }

        $columns = acym_getColumns('user');
        foreach ($columns as $i => $oneColumn) {
            $columns[$i] = strtolower($oneColumn);
        }

        foreach ($this->columns as $i => $oneColumn) {
            $this->columns[$i] = strtolower(trim($oneColumn, '\'" '));
            if (in_array($this->columns[$i], ['listids', 'listname'])) continue;
            if (strpos($this->columns[$i], 'cf_') === 0) continue;

            if (!in_array($this->columns[$i], $columns) && $this->columns[$i] != 1) {
                acym_enqueueMessage(
                    acym_translationSprintf(
                        'ACYM_IMPORT_ERROR_FIELD',
                        '<b>'.acym_escape($this->columns[$i]).'</b>',
                        '<b>'.implode('</b> | <b>', array_diff($columns, ['id', 'cms_id'])).'</b>'
                    ),
                    'error'
                );

                return false;
            }
        }

        //We check that the email field is there...
        if (!in_array('email', $this->columns)) {
            return false;
        }

        return true;
    }

    public function _cleanImportFolder()
    {

        $files = acym_getFiles(ACYM_MEDIA.'import', '.', false, true, []);
        foreach ($files as $oneFile) {
            if (acym_fileGetExt($oneFile) != 'csv') {
                continue;
            }
            if (filectime($oneFile) < time() - 86400) {
                unlink($oneFile);
            }
        }
    }

    public function getImportedLists()
    {
        $listClass = new ListClass();
        $listsId = json_decode(acym_getVar('string', 'acym__entity_select__selected'));
        $newListName = acym_getVar('string', 'new_list');

        if (empty($listsId) && empty($newListName)) {
            return false;
        }

        $lists = [];

        if (!empty($newListName)) {
            $newList = new \stdClass();
            $newList->name = $newListName;
            $newList->active = 1;
            $colors = '#'.substr(str_shuffle('ABCDEF0123456789'), 0, 6);
            $newList->color = $colors;
            $listid = $listClass->save($newList);
            $lists[$listid] = 1;
        }

        if (!empty($listsId)) {
            foreach ($listsId as $id) {
                $lists[$id] = 1;
            }
        }

        if (!empty($lists)) {
            return $lists;
        } else {
            return false;
        }
    }

    public function _subscribeUsers()
    {
        //All users are known as : $this->allSubid
        if (empty($this->allSubid)) return true;

        $subdate = date('Y-m-d H:i:s', time() - date('Z'));

        $listClass = new ListClass();
        $lists = $this->getImportedLists();

        if (!acym_isAdmin() && 'joomla' === ACYM_CMS) {
            $listClass = new ListClass();
            $listManagementId = $listClass->getfrontManagementList();
            if (empty($listManagementId)) {
                acym_redirect(acym_rootURI(), 'ACYM_UNABLE_TO_CREATE_MANAGEMENT_LIST', 'error');
            }

            if (empty($lists)) {
                $lists = [$listManagementId => 1];
            } else {
                $lists[$listManagementId] = 1;
            }
        }

        if (!empty($this->importUserInLists)) {
            //We import the users based on the subscription of each of them, specified in the listids or listname columns...
            foreach ($this->importUserInLists as $listid => $arrayEmails) {
                if (empty($listid)) continue;

                $listid = (int)$listid;
                $query = 'INSERT IGNORE INTO #__acym_user_has_list (`list_id`,`user_id`,`subscription_date`,`status`) ';
                $query .= 'SELECT '.intval($listid).',`id`,'.acym_escapeDB($subdate).',1 FROM #__acym_user WHERE `email` IN (';
                $query .= implode(',', $arrayEmails).')';
                $nbsubscribed = acym_query($query);
                $nbsubscribed = intval($nbsubscribed);

                if (isset($this->subscribedUsers[$listid])) {
                    $this->subscribedUsers[$listid]->nbusers += $nbsubscribed;
                } else {
                    $myList = $listClass->getOneById($listid);
                    $this->subscribedUsers[$listid] = $myList;
                    $this->subscribedUsers[$listid]->nbusers = $nbsubscribed;
                }
            }
        }

        if (!empty($lists)) {

            //We do one query per list to avoid problem with huge queries and so in the mean time we can display info for each list
            foreach ($lists as $listid => $val) {
                if (empty($val)) {
                    continue;
                }

                if ($val == -1) {
                    $dateColumn = 'unsubscribe_date';
                    $status = -1;
                } else {
                    $dateColumn = 'subscription_date';
                    $status = 1;
                }

                $nbsubscribed = 0;
                $listid = intval($listid);
                $query = 'INSERT IGNORE INTO #__acym_user_has_list (`list_id`,`user_id`,`'.$dateColumn.'`,`status`) VALUES ';
                $b = 0;
                $currentSubids = [];
                foreach ($this->allSubid as $subid) {
                    $subid = intval($subid);
                    $currentSubids[] = $subid;
                    $b++;

                    if ($b > 200) {
                        $query = rtrim($query, ',');
                        if ($val == -1) {
                            $query .= ' ON DUPLICATE KEY UPDATE status = -1';
                            $nbsubscribed = -acym_loadResult(
                                'SELECT COUNT(*) FROM #__acym_listsub WHERE `list_id` = '.intval($listid).' AND status != -1 AND `user_id` IN ('.implode(',', $currentSubids).')'
                            );
                        }
                        $affected = acym_query($query);
                        $nbsubscribed += intval($affected);
                        $b = 0;
                        $currentSubids = [];
                        $query = 'INSERT IGNORE INTO #__acym_user_has_list (`list_id`,`user_id`,`'.$dateColumn.'`,`status`) VALUES ';
                    }

                    $query .= '('.intval($listid).','.intval($subid).','.acym_escapeDB($subdate).','.$status.'),';
                }
                $query = rtrim($query, ',');
                if ($val == -1) {
                    $query .= ' ON DUPLICATE KEY UPDATE status = -1';
                    // It could be empty if we imported exactly 200, 400, 600... users
                    if (!empty($currentSubids)) {
                        $nbsubscribed = -acym_loadResult(
                            'SELECT COUNT(*) FROM #__acym_listsub WHERE `list_id` = '.intval($listid).' AND status != -1 AND `user_id` IN ('.implode(',', $currentSubids).')'
                        );
                    }
                }

                $affected = acym_query($query);
                $nbsubscribed += intval($affected);

                if (isset($this->subscribedUsers[$listid])) {
                    $this->subscribedUsers[$listid]->nbusers += $nbsubscribed;
                } else {
                    $myList = $listClass->getOneById($listid);
                    $myList->status = $val;
                    $this->subscribedUsers[$listid] = $myList;
                    $this->subscribedUsers[$listid]->nbusers = $nbsubscribed;
                }
            }
        }

        return true;
    }

    public function additionalDataUsersImport($isGeneric)
    {
        $buttonAddListId = $isGeneric ? 'acym__users__generic__import__create-list__button' : 'acym__users__import__create-list__button';
        $buttonImportClass = $isGeneric ? 'acym__users__import__generic__import__button' : 'acym__users__import__button';
        $buttonSkipId = $isGeneric ? 'acym__users__generic__import__skip__button' : 'acym__users__import__skip__button';
        $buttonImportDataTask = $isGeneric ? 'listing' : '';

        return '<div class="cell align-right grid-x margin-bottom-2">
                    <div id="acym__users__import__create-list" class="grid-x" style="display: none;">
                        <label for="acym__users__import__create-list__field" class="margin-right-1 acym_vcenter">'.acym_translation('ACYM_LIST_NAME').' : </label>
                        <div>
                            <input id="acym__users__import__create-list__field" type="text">
                        </div>
                    </div>
                    <button type="button" class="button button-secondary margin-left-1 acym_vcenter margin-right-2" id="'.$buttonAddListId.'">'.acym_translation(
                'ACYM_CREATE_NEW_LIST'
            ).'</button>
                    <i style="display: none;" class="acym_vcenter acymicon-circle-o-notch acymicon-spin" id="acym__users__import__create-list__loading-logo"></i>
                </div>
                <div class="cell align-right grid-x">
                    <button type="button" class="button-secondary button cell shrink margin-right-1" id="'.$buttonSkipId.'">'.acym_translation('ACYM_SKIP').'</button>
                    <button 
                        type="button" 
                        class="button-primary button acy_button_submit cell shrink margin-right-2 '.$buttonImportClass.'" 
                        id="acym__entity_select__button__submit" data-task="'.$buttonImportDataTask.'">'.acym_translation('ACYM_SUBSCRIBE_USERS_TO_THESE_LISTS').'</button>
                </div>';
    }
}
