<?php

function acym_getTables(bool $reload = false): array
{
    static $tables = null;
    if (empty($tables) || $reload) {
        $tables = acym_loadResultArray('SHOW TABLES');
    }

    return $tables;
}

function acym_getColumns(string $table, bool $acyTable = true, bool $addPrefix = true): array
{
    if ($addPrefix) {
        $prefix = $acyTable ? '#__acym_' : '#__';
        $table = $prefix.$table;
    }

    static $columns = [];
    if (empty($columns[$table])) {
        $columns[$table] = acym_loadResultArray('SHOW COLUMNS FROM '.acym_secureDBColumn($table));
    }

    return $columns[$table] ?? [];
}

function acym_secureDBColumn(string $fieldName): string
{
    if (preg_match('|[^a-z0-9#_.-]|i', $fieldName) !== 0) {
        die('field, table or database "'.acym_escape($fieldName).'" not secured');
    }

    return $fieldName;
}

function acym_getDatabases(): array
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

function acym_addLimit(string &$query, int $limit = 1, ?int $offset = null): void
{
    if (strpos($query, 'LIMIT ') !== false) {
        return;
    }

    $query .= ' LIMIT ';
    if (!empty($offset)) {
        $query .= intval($offset).',';
    }
    $query .= intval($limit);
}
