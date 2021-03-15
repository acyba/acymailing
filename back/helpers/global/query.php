<?php

/**
 * Get all tables from the database
 * @return mixed
 */
function acym_getTables()
{
    return acym_loadResultArray('SHOW TABLES');
}

function acym_getColumns($table, $acyTable = true, $addPrefix = true)
{
    if ($addPrefix) {
        $prefix = $acyTable ? '#__acym_' : '#__';
        $table = $prefix.$table;
    }

    return acym_loadResultArray('SHOW COLUMNS FROM '.acym_secureDBColumn($table));
}

function acym_secureDBColumn($fieldName)
{
    if (!is_string($fieldName) || preg_match('|[^a-z0-9#_.-]|i', $fieldName) !== 0) {
        die('field, table or database "'.acym_escape($fieldName).'" not secured');
    }

    return $fieldName;
}

function acym_getDatabases()
{
    try {
        $allDatabases = acym_loadResultArray('SHOW DATABASES');
    } catch (Exception $exception) {
        $allDatabases = [];
        $allDatabases[] = acym_loadResult('SELECT DATABASE();');
    }

    $databases = [];
    foreach ($allDatabases as $database) {
        $databases[$database] = $database;
    }

    return $databases;
}

function acym_addLimit(&$query, $limit = 1, $offset = null)
{
    if (strpos($query, 'LIMIT ') !== false) return;

    $query .= ' LIMIT ';
    if (!empty($offset)) $query .= intval($offset).',';
    $query .= intval($limit);
}
