<?php

namespace AcyMailing\Controllers\Configuration;

use AcyMailing\Classes\UrlClass;
use AcyMailing\Classes\UserClass;

trait Security
{
    private array $messagesNoHtml = [];

    public function checkDBAjax(): void
    {
        $this->checkDB();

        if (empty($this->messagesNoHtml)) {
            echo '<i class="acymicon-check-circle acym__color__green"></i>';
        } else {
            $nbMessages = count($this->messagesNoHtml);
            foreach ($this->messagesNoHtml as $i => $oneMsg) {
                echo '<span style="color:'.$oneMsg['color'].'">'.$oneMsg['msg'].'</span>';

                if ($i < $nbMessages) {
                    echo '<br />';
                }
            }
        }

        exit;
    }

    /**
     * Check database integrity button in the security tab
     */
    public function checkDB(bool $fromConfiguration = true): array
    {
        // Get the structure that the AcyMailing tables should have in the database
        $correctTablesStructure = $this->getCorrectTablesStructure();
        // Get the current structure of the AcyMailing tables and tries to repair/create them if needed
        $currentTablesStructure = $this->getCurrentTablesStructure($correctTablesStructure);
        // Adds missing columns in AcyMailing tables and missing indexes / primary keys / constraints on the tables
        $this->fixCurrentStructure($correctTablesStructure, $currentTablesStructure);

        // Clean the duplicates in the acym_url table, caused by a bug before the 12/04/19
        $this->cleanDuplicatedUrls($fromConfiguration);
        // Fills the key column in the users table when missing
        $this->addMissingUserKeys();

        return $this->messagesNoHtml;
    }

    /**
     * Returns the structure that the AcyMailing tables should have in the database
     */
    private function getCorrectTablesStructure(): array
    {
        $correctTablesStructure = [
            'structure' => [],
            'createTable' => [],
            'indexes' => [],
            'constraints' => [],
        ];

        $queries = file_get_contents(ACYM_BACK.'tables.sql');
        $tables = explode('CREATE TABLE IF NOT EXISTS ', $queries);

        // For each table, get its name, its column names and its indexes / primary key
        foreach ($tables as $oneTable) {
            if (strpos($oneTable, '`#__') !== 0) {
                continue;
            }

            $tableName = substr($oneTable, 1, strpos($oneTable, '`', 1) - 1);
            $correctTablesStructure['createTable'][$tableName] = 'CREATE TABLE IF NOT EXISTS '.$oneTable;
            $correctTablesStructure['indexes'][$tableName] = [];
            $correctTablesStructure['constraints'][$tableName] = [];

            $fields = explode("\n", $oneTable);
            foreach ($fields as $key => $oneField) {
                if (strpos($oneField, '#__') === 1) {
                    continue;
                }
                $oneField = rtrim(trim($oneField), ',');

                // Find the column names and remember them
                if (substr($oneField, 0, 1) === '`') {
                    $columnName = substr($oneField, 1, strpos($oneField, '`', 1) - 1);
                    $correctTablesStructure['structure'][$tableName][$columnName] = trim($oneField, ',');
                    continue;
                }

                // Remember the primary key and indexes of the table
                if (strpos($oneField, 'PRIMARY KEY') === 0) {
                    $correctTablesStructure['indexes'][$tableName]['PRIMARY'] = $oneField;
                } elseif (strpos($oneField, 'INDEX') === 0) {
                    $firstBackquotePos = strpos($oneField, '`');
                    $indexName = substr($oneField, $firstBackquotePos + 1, strpos($oneField, '`', $firstBackquotePos + 1) - $firstBackquotePos - 1);

                    $correctTablesStructure['indexes'][$tableName][$indexName] = $oneField;
                } elseif (strpos($oneField, 'FOREIGN KEY') !== false) {
                    preg_match('/(#__fk.*)\`/Uis', $fields[$key - 1], $matchesConstraints);
                    preg_match('/(#__.*)\`\(`(.*)`\)/Uis', $fields[$key + 1], $matchesTable);
                    preg_match('/\`(.*)\`/Uis', $oneField, $matchesColumn);
                    if (!empty($matchesConstraints) && !empty($matchesTable) && !empty($matchesColumn)) {
                        $correctTablesStructure['constraints'][$tableName][$matchesConstraints[1]] = [
                            'table' => $matchesTable[1],
                            'column' => $matchesColumn[1],
                            'table_column' => $matchesTable[2],
                        ];
                    }
                }
            }
        }

        $correctTablesStructure['tableNames'] = array_keys($correctTablesStructure['structure']);

        return $correctTablesStructure;
    }

    /**
     * Returns the current structure of the AcyMailing tables and tries to repair/create them if needed
     */
    private function getCurrentTablesStructure(array $correctTablesStructure): array
    {
        $currentTablesStructure = [];

        foreach ($correctTablesStructure['tableNames'] as $oneTableName) {
            try {
                $columns = acym_loadObjectList('SHOW COLUMNS FROM '.$oneTableName);
            } catch (\Exception $e) {
                $columns = null;
            }

            if (!empty($columns)) {
                foreach ($columns as $oneField) {
                    $currentTablesStructure[$oneTableName][$oneField->Field] = $oneField->Field;
                }
                continue;
            }

            // We didn't get the columns, the table crashed or doesn't exist
            $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
            $this->messagesNoHtml[] = [
                'error' => false,
                'color' => 'blue',
                'msg' => acym_translationSprintf('ACYM_CHECKDB_LOAD_COLUMNS_ERROR', $oneTableName, $errorMessage),
            ];

            if (strpos($errorMessage, 'marked as crashed')) {
                try {
                    $isError = acym_query('REPAIR TABLE '.$oneTableName);
                } catch (\Exception $e) {
                    $isError = null;
                }

                if ($isError === null) {
                    $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
                    $this->messagesNoHtml[] = [
                        'error' => true,
                        'color' => 'red',
                        'msg' => acym_translationSprintf('ACYM_CHECKDB_REPAIR_TABLE_ERROR', $oneTableName, $errorMessage),
                    ];
                } else {
                    $this->messagesNoHtml[] = [
                        'error' => false,
                        'color' => 'green',
                        'msg' => acym_translationSprintf('ACYM_CHECKDB_REPAIR_TABLE_SUCCESS', $oneTableName),
                    ];
                }
                continue;
            } else {
                try {
                    // Create missing table
                    $isError = acym_query($correctTablesStructure['createTable'][$oneTableName]);
                } catch (\Exception $e) {
                    $isError = null;
                }

                if ($isError === null) {
                    $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
                    $this->messagesNoHtml[] = [
                        'error' => true,
                        'color' => 'red',
                        'msg' => acym_translationSprintf('ACYM_CHECKDB_CREATE_TABLE_ERROR', $oneTableName, $errorMessage),
                    ];
                } else {
                    $this->messagesNoHtml[] = [
                        'error' => false,
                        'color' => 'green',
                        'msg' => acym_translationSprintf('ACYM_CHECKDB_CREATE_TABLE_SUCCESS', $oneTableName),
                    ];
                }
            }
        }

        return $currentTablesStructure;
    }

    /**
     * Adds missing columns in AcyMailing tables and missing indexes / primary keys on the tables
     */
    private function fixCurrentStructure(array $correctTablesStructure, array $currentTablesStructure): void
    {
        foreach ($correctTablesStructure['tableNames'] as $oneTableName) {
            if (empty($currentTablesStructure[$oneTableName])) {
                continue;
            }

            $this->addMissingColumns($correctTablesStructure['structure'][$oneTableName], $currentTablesStructure[$oneTableName], $oneTableName);
            $this->removeExtraColumns($correctTablesStructure['structure'][$oneTableName], $currentTablesStructure[$oneTableName], $oneTableName);
            $this->fixDefaultValues($correctTablesStructure['structure'][$oneTableName], $oneTableName);
            $this->addMissingTableKeys($correctTablesStructure['indexes'][$oneTableName], $oneTableName);
            $this->addMissingTableConstraints($correctTablesStructure['constraints'][$oneTableName], $oneTableName);
        }
    }

    /**
     * Add missing columns in an AcyMailing table
     */
    private function addMissingColumns(array $correctTableColumns, array $currentTableColumnNames, string $oneTableName): void
    {
        $idealColumnNames = array_keys($correctTableColumns);
        $missingColumns = array_diff($idealColumnNames, $currentTableColumnNames);

        if (empty($missingColumns)) {
            return;
        }

        foreach ($missingColumns as $oneColumn) {
            $this->messagesNoHtml[] = [
                'error' => false,
                'color' => 'blue',
                'msg' => acym_translationSprintf('ACYM_CHECKDB_MISSING_COLUMN', $oneColumn, $oneTableName),
            ];

            try {
                $isError = acym_query('ALTER TABLE '.$oneTableName.' ADD '.$correctTableColumns[$oneColumn]);
            } catch (\Exception $e) {
                $isError = null;
            }

            if ($isError === null) {
                $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
                $this->messagesNoHtml[] = [
                    'error' => true,
                    'color' => 'red',
                    'msg' => acym_translationSprintf('ACYM_CHECKDB_ADD_COLUMN_ERROR', $oneColumn, $oneTableName, $errorMessage),
                ];
            } else {
                $this->messagesNoHtml[] = [
                    'error' => false,
                    'color' => 'green',
                    'msg' => acym_translationSprintf('ACYM_CHECKDB_ADD_COLUMN_SUCCESS', $oneColumn, $oneTableName),
                ];
            }
        }
    }

    private function removeExtraColumns(array $correctTableColumns, array $currentTableColumnNames, string $oneTableName): void
    {
        $idealColumnNames = array_keys($correctTableColumns);
        $extraColumns = array_diff($currentTableColumnNames, $idealColumnNames);

        if (empty($extraColumns)) {
            return;
        }

        foreach ($extraColumns as $oneColumn) {
            $this->messagesNoHtml[] = [
                'error' => false,
                'color' => 'blue',
                'msg' => acym_translationSprintf('ACYM_CHECKDB_EXTRA_COLUMN', $oneColumn, $oneTableName),
            ];

            try {
                $isError = acym_query('ALTER TABLE '.$oneTableName.' DROP COLUMN `'.acym_secureDBColumn($oneColumn).'`');
            } catch (\Exception $e) {
                $isError = null;
            }

            if ($isError === null) {
                $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
                $this->messagesNoHtml[] = [
                    'error' => true,
                    'color' => 'red',
                    'msg' => acym_translationSprintf('ACYM_CHECKDB_EXTRA_COLUMN_ERROR', $oneColumn, $oneTableName, $errorMessage),
                ];
            } else {
                $this->messagesNoHtml[] = [
                    'error' => false,
                    'color' => 'green',
                    'msg' => acym_translationSprintf('ACYM_CHECKDB_EXTRA_COLUMN_SUCCESS', $oneColumn, $oneTableName),
                ];
            }
        }
    }

    private function fixDefaultValues(array $correctTableColumns, string $oneTableName): void
    {
        $oneTableName = str_replace('#__', acym_getPrefix(), $oneTableName);
        try {
            $currentTableColumns = acym_loadObjectList(
                'SELECT COLUMN_NAME, COLUMN_DEFAULT, IS_NULLABLE, COLUMN_TYPE 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = '.acym_escapeDB($oneTableName),
                'COLUMN_NAME'
            );

            if (empty($currentTableColumns)) {
                //TODO
                return;
            }
        } catch (\Exception $e) {
            $this->messagesNoHtml[] = [
                'error' => true,
                'color' => 'orange',
                'msg' => acym_translationSprintf('ACYM_CHECKDB_ERROR_GET_COLUMNS', $oneTableName, $e->getMessage()),
            ];

            return;
        }

        foreach ($correctTableColumns as $oneColumn => $oneColumnDefinition) {
            $defaultValue = '';
            if (preg_match('#DEFAULT ([^ ]+)$#Ui', $oneColumnDefinition, $matches)) {
                $defaultValue = $matches[1];
            }

            if (strlen($defaultValue) === 0) {
                continue;
            }
            // Normalisation of current and expected default values
            $currentDefault = $currentTableColumns[$oneColumn]->COLUMN_DEFAULT;
            if (!empty($currentDefault)) {
                $currentDefault = trim($currentDefault, "'\"");
            }
            $expectedDefault = trim($defaultValue, "'\"");

            if (strtoupper($expectedDefault) === 'NULL') {
                $expectedDefault = null;
            }

            $isNullable = strtoupper($currentTableColumns[$oneColumn]->IS_NULLABLE) === 'YES';

            if ($isNullable && $currentDefault === '') {
                $currentDefault = null;
            }

            if (!$isNullable && $currentDefault === null) {
                $currentDefault = '';
            }

            if ($currentDefault === $expectedDefault) {
                continue;
            }

            // if current value is surrounded by double quotes, replace them by quotes before comparing
            if (!empty($currentTableColumns[$oneColumn]->COLUMN_DEFAULT) && substr($currentTableColumns[$oneColumn]->COLUMN_DEFAULT, 0, 1) === '"') {
                $currentTableColumns[$oneColumn]->COLUMN_DEFAULT = '\''.substr($currentTableColumns[$oneColumn]->COLUMN_DEFAULT, 1, -1).'\'';
            }

            $isColumnDefaultEmpty = empty($currentTableColumns[$oneColumn]->COLUMN_DEFAULT) && $currentTableColumns[$oneColumn]->COLUMN_DEFAULT !== '0';

            if (!$isColumnDefaultEmpty && $currentTableColumns[$oneColumn]->COLUMN_DEFAULT === $defaultValue) {
                continue;
            }

            $this->messagesNoHtml[] = [
                'error' => false,
                'color' => 'blue',
                'msg' => acym_translationSprintf('ACYM_CHECKDB_WRONG_DEFAULT_VALUE', $oneColumn, $oneTableName),
            ];

            try {
                $isError = acym_query('ALTER TABLE '.$oneTableName.' CHANGE `'.acym_secureDBColumn($oneColumn).'` '.$oneColumnDefinition);
            } catch (\Exception $e) {
                $isError = null;
            }

            if ($isError === null) {
                $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
                $this->messagesNoHtml[] = [
                    'error' => true,
                    'color' => 'red',
                    'msg' => acym_translationSprintf('ACYM_CHECKDB_WRONG_DEFAULT_VALUE_ERROR', $oneColumn, $oneTableName, $errorMessage),
                ];
            } else {
                $this->messagesNoHtml[] = [
                    'error' => false,
                    'color' => 'green',
                    'msg' => acym_translationSprintf('ACYM_CHECKDB_WRONG_DEFAULT_VALUE_SUCCESS', $oneColumn, $oneTableName),
                ];
            }
        }
    }

    /**
     * Adds the missing indexes / primary keys on an AcyMailing table
     */
    private function addMissingTableKeys(array $correctTableIndexes, string $oneTableName): void
    {
        // Add missing index and primary keys
        $results = acym_loadObjectList('SHOW INDEX FROM '.$oneTableName, 'Key_name');
        if (empty($results)) {
            $results = [];
        }

        foreach ($correctTableIndexes as $name => $query) {
            $name = acym_prepareQuery($name);
            if (in_array($name, array_keys($results))) {
                continue;
            }

            // The index / primary key is missing, add it

            $keyName = $name === 'PRIMARY' ? 'primary key' : 'index '.$name;

            $this->messagesNoHtml[] = [
                'error' => false,
                'color' => 'blue',
                'msg' => acym_translationSprintf('ACYM_CHECKDB_MISSING_INDEX', $keyName, $oneTableName),
            ];

            try {
                $isError = acym_query('ALTER TABLE '.$oneTableName.' ADD '.$query);
            } catch (\Exception $e) {
                $isError = null;
            }

            if ($isError === null) {
                $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
                $this->messagesNoHtml[] = [
                    'error' => true,
                    'color' => 'red',
                    'msg' => acym_translationSprintf('ACYM_CHECKDB_ADD_INDEX_ERROR', $keyName, $oneTableName, $errorMessage),
                ];
            } else {
                $this->messagesNoHtml[] = [
                    'error' => false,
                    'color' => 'green',
                    'msg' => acym_translationSprintf('ACYM_CHECKDB_ADD_INDEX_SUCCESS', $keyName, $oneTableName),
                ];
            }
        }
    }

    /**
     * Adds or fixes the table's foreign keys
     */
    private function addMissingTableConstraints(array $correctTableConstraints, string $oneTableName): void
    {
        if (empty($correctTableConstraints)) {
            return;
        }

        $tableNameQuery = str_replace('#__', acym_getPrefix(), $oneTableName);
        $databaseName = acym_loadResult('SELECT DATABASE();');
        $foreignKeys = acym_loadObjectList(
            'SELECT i.CONSTRAINT_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME, k.COLUMN_NAME
            FROM information_schema.TABLE_CONSTRAINTS AS i 
            JOIN information_schema.KEY_COLUMN_USAGE AS k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME 
            WHERE i.TABLE_NAME = '.acym_escapeDB($tableNameQuery).' AND i.CONSTRAINT_TYPE = "FOREIGN KEY" AND i.TABLE_SCHEMA = '.acym_escapeDB($databaseName),
            'CONSTRAINT_NAME'
        );

        acym_query('SET foreign_key_checks = 0');

        foreach ($correctTableConstraints as $constraintName => $constraintInfo) {
            $constraintTableNamePrefix = str_replace('#__', acym_getPrefix(), $constraintInfo['table']);
            $constraintName = str_replace('#__', acym_getPrefix(), $constraintName);

            if (!empty($foreignKeys[$constraintName]) && $foreignKeys[$constraintName]->REFERENCED_TABLE_NAME === $constraintTableNamePrefix && $foreignKeys[$constraintName]->REFERENCED_COLUMN_NAME === $constraintInfo['table_column'] && $foreignKeys[$constraintName]->COLUMN_NAME === $constraintInfo['column']) {
                continue;
            }

            $this->messagesNoHtml[] = [
                'error' => false,
                'color' => 'blue',
                'msg' => acym_translationSprintf('ACYM_CHECKDB_WRONG_FOREIGN_KEY', $constraintName, $oneTableName),
            ];

            // The foreign key exists, but it is incorrect. We remove it then add the correct one
            if (!empty($foreignKeys[$constraintName])) {
                try {
                    $isError = acym_query('ALTER TABLE `'.$oneTableName.'` DROP FOREIGN KEY `'.$constraintName.'`');
                } catch (\Exception $e) {
                    $isError = null;
                }

                if ($isError === null) {
                    $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
                    $this->messagesNoHtml[] = [
                        'error' => true,
                        'color' => 'red',
                        'msg' => acym_translationSprintf('ACYM_CHECKDB_ADD_FOREIGN_KEY_ERROR', $constraintName, $oneTableName, $errorMessage),
                    ];
                    continue;
                }
            }

            // Add the missing foreign key
            try {
                $isError = acym_query(
                    'ALTER TABLE `'.$oneTableName.'` ADD CONSTRAINT `'.$constraintName.'` FOREIGN KEY (`'.$constraintInfo['column'].'`) REFERENCES `'.$constraintInfo['table'].'` (`'.$constraintInfo['table_column'].'`) ON DELETE NO ACTION ON UPDATE NO ACTION;'
                );
            } catch (\Exception $e) {
                $isError = null;
            }

            if ($isError === null) {
                $errorMessage = (isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200));
                $this->messagesNoHtml[] = [
                    'error' => true,
                    'color' => 'red',
                    'msg' => acym_translationSprintf('ACYM_CHECKDB_ADD_FOREIGN_KEY_ERROR', $constraintName, $oneTableName, $errorMessage),
                ];
            } else {
                $this->messagesNoHtml[] = [
                    'error' => false,
                    'color' => 'green',
                    'msg' => acym_translationSprintf('ACYM_CHECKDB_ADD_FOREIGN_KEY_SUCCESS', $constraintName, $oneTableName),
                ];
            }
        }

        acym_query('SET foreign_key_checks = 1');
    }

    /**
     * Clean the duplicates in the acym_url table, caused by a bug before the 12/04/19
     */
    private function cleanDuplicatedUrls(bool $fromConfiguration): void
    {
        if (!$fromConfiguration) {
            return;
        }

        $urlClass = new UrlClass();
        $duplicatedUrls = $urlClass->getDuplicatedUrls();

        if (empty($duplicatedUrls)) {
            return;
        }

        $maxExecutionTime = intval(ini_get('max_execution_time'));
        $time = time();
        $interrupted = false;
        $this->messagesNoHtml[] = [
            'error' => false,
            'color' => 'blue',
            'msg' => acym_translation('ACYM_CHECKDB_DUPLICATED_URLS'),
        ];

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
            $this->messagesNoHtml[] = [
                'error' => false,
                'color' => 'green',
                'msg' => acym_translation('ACYM_CHECKDB_DUPLICATED_URLS_SUCCESS'),
            ];
        } else {
            $this->messagesNoHtml[] = [
                'error' => false,
                'color' => 'blue',
                'msg' => acym_translation('ACYM_CHECKDB_DUPLICATED_URLS_REMAINING'),
            ];
        }
    }

    /**
     * Fills the key column in the users table when missing
     */
    private function addMissingUserKeys(): void
    {
        $userClass = new UserClass();
        $nbAddedKeys = $userClass->addMissingKeys();

        if (!empty($nbAddedKeys)) {
            $this->messagesNoHtml[] = [
                'error' => false,
                'color' => 'green',
                'msg' => acym_translationSprintf('ACYM_CHECKDB_ADDED_KEYS', $nbAddedKeys),
            ];
        }
    }

    public function redomigration(): void
    {
        $this->config->saveConfig(['migration' => 0]);
        acym_redirect(acym_completeLink('dashboard', false, true));
    }

    public function scanSiteFiles(): void
    {
        $maliciousFiles = [];
        $siteFiles = acym_getFiles(ACYM_ROOT, '.', true, true);
        foreach ($siteFiles as $oneFilePath) {
            $lastSlashPos = strrpos($oneFilePath, '/');
            if (
                !empty($lastSlashPos)
                && strpos($oneFilePath, ACYM_UPLOAD_FOLDER_THUMBNAIL) !== false
                && preg_match('/.*thumbnail.*php.*$/', substr($oneFilePath, $lastSlashPos + 1))
            ) {
                $maliciousFiles[] = $oneFilePath;
            } elseif (filesize($oneFilePath) < 10000) {
                $fileContent = file_get_contents($oneFilePath);
                if (preg_match('/^<\?php echo "jm"\."te"\."st"; \?>$/U', $fileContent)) {
                    $maliciousFiles[] = $oneFilePath;
                } elseif (preg_match('/^<\?php\n\$[a-z]+\s*=\s*\$_COOKIE\s*;/Ui', $fileContent)) {
                    $maliciousFiles[] = $oneFilePath;
                }
            }
        }

        ob_start();
        if (!empty($maliciousFiles)) {
            $message = acym_translation('ACYM_MALICIOUS_FILES');
            $message .= '<ul><li>'.implode('</li><li>', $maliciousFiles).'</li></ul>';
            acym_display($message, 'error', false);
        } else {
            acym_display(acym_translation('ACYM_NO_MALICIOUS_FILES'), 'success', false);
        }
        $message = ob_get_clean();
        acym_sendAjaxResponse($message);
    }
}
