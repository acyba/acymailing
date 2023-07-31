<?php

namespace AcyMailing\Controllers\Configuration;

use AcyMailing\Classes\UrlClass;
use AcyMailing\Classes\UserClass;

trait Security
{
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

        $maxExecutionTime = intval(ini_get('max_execution_time'));

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
                if (empty($maxExecutionTime) || $maxExecutionTime - 20 < 20) {
                    $maxExecutionTime = 20;
                } else {
                    $maxExecutionTime -= 20;
                }

                acym_increasePerf();
                while (!empty($duplicatedUrls)) {
                    $urlClass->delete($duplicatedUrls);

                    if (time() - $time > $maxExecutionTime) {
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

    public function redomigration()
    {
        $newConfig = new \stdClass();
        $newConfig->migration = 0;
        $this->config->save($newConfig);

        acym_redirect(acym_completeLink('dashboard', false, true));
    }
}
