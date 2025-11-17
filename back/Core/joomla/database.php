<?php

use Joomla\CMS\Factory;

function acym_escapeDB(?string $value): string
{
    $acydb = acym_getGlobal('db');

    if (is_null($value)) {
        $value = '';
    }

    return $acydb->quote($value);
}

function acym_query(string $query)
{
    $acydb = acym_getGlobal('db');
    $acydb->setQuery($query);

    $method = ACYM_J40 ? 'execute' : 'query';

    $result = $acydb->$method();
    if (!$result) {
        return false;
    }

    return $acydb->getAffectedRows();
}

function acym_loadObjectList(string $query, string $key = '', ?int $offset = null, ?int $limit = null): array
{
    $acydb = acym_getGlobal('db');
    $acydb->setQuery($query, $offset, $limit);

    $results = $acydb->loadObjectList($key);

    return empty($results) ? [] : $results;
}

function acym_prepareQuery(string $query): string
{
    return str_replace('#__', acym_getPrefix(), $query);
}

function acym_loadObject(string $query): ?object
{
    acym_addLimit($query);

    $acydb = acym_getGlobal('db');
    $acydb->setQuery($query);

    $object = $acydb->loadObject();

    return empty($object) ? null : $object;
}

function acym_loadResult(string $query)
{
    $acydb = acym_getGlobal('db');
    $acydb->setQuery($query);

    return $acydb->loadResult();
}

function acym_loadResultArray(string $query): array
{
    $acydb = acym_getGlobal('db');
    $acydb->setQuery($query);

    if (ACYM_J30) {
        return $acydb->loadColumn();
    }

    return $acydb->loadResultArray();
}

function acym_getEscaped(string $text, bool $extra = false)
{
    $acydb = acym_getGlobal('db');

    if (ACYM_J30) {
        return $acydb->escape($text, $extra);
    }

    return $acydb->getEscaped($text, $extra);
}

function acym_getDBError()
{
    // Joomla decided to remove the getErrorMsg function in J4 and only use PHP exceptions
    if (ACYM_J40) {
        return '';
    }

    $acydb = acym_getGlobal('db');
    $lastError = $acydb->getErrorMsg();

    return empty($lastError) ? '' : $lastError;
}

function acym_insertObject(string $table, object $element): ?int
{
    $acydb = acym_getGlobal('db');
    $acydb->insertObject($table, $element);

    $id = $acydb->insertid();

    return empty($id) ? null : (int)$id;
}

function acym_updateObject(string $table, object $element, array $pkey): bool
{
    $acydb = acym_getGlobal('db');
    $updated = $acydb->updateObject($table, $element, $pkey, true);

    return !empty($updated);
}

function acym_getPrefix(): string
{
    $acydb = acym_getGlobal('db');

    return $acydb->getPrefix();
}

function acym_getTableList(): array
{
    $acydb = acym_getGlobal('db');

    return $acydb->getTableList();
}

/**
 * @param mixed $default
 *
 * @return mixed
 */
function acym_getCMSConfig(string $varname, $default = null)
{
    if (ACYM_J30 && !ACYM_J40) {
        $acyapp = acym_getGlobal('app');

        return $acyapp->getCfg($varname, $default);
    } elseif (ACYM_J40) {
        $acyapp = acym_getGlobal('app');

        return $acyapp->get($varname, $default);
    }

    $conf = Factory::getConfig();
    $val = $conf->getValue('config.'.$varname);

    return empty($val) ? $default : $val;
}
