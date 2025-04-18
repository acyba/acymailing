<?php

use Joomla\CMS\Factory;

function acym_escapeDB($value)
{
    $acydb = acym_getGlobal('db');

    if (is_null($value)) {
        $value = '';
    }

    return $acydb->quote($value);
}

function acym_query($query)
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

function acym_loadObjectList($query, $key = '', $offset = null, $limit = null): array
{
    $acydb = acym_getGlobal('db');
    $acydb->setQuery($query, $offset, $limit);

    $results = $acydb->loadObjectList($key);

    return empty($results) ? [] : $results;
}

function acym_prepareQuery($query)
{
    return str_replace('#__', acym_getPrefix(), $query);
}

function acym_loadObject($query)
{
    acym_addLimit($query);

    $acydb = acym_getGlobal('db');
    $acydb->setQuery($query);

    return $acydb->loadObject();
}

function acym_loadResult($query)
{
    $acydb = acym_getGlobal('db');
    $acydb->setQuery($query);

    return $acydb->loadResult();
}

function acym_loadResultArray($query)
{
    $acydb = acym_getGlobal('db');
    $acydb->setQuery($query);

    if (ACYM_J30) {
        return $acydb->loadColumn();
    }

    return $acydb->loadResultArray();
}

function acym_getEscaped($value, $extra = false)
{
    $acydb = acym_getGlobal('db');

    if (ACYM_J30) {
        return $acydb->escape($value, $extra);
    }

    return $acydb->getEscaped($value, $extra);
}

function acym_getDBError()
{
    // Joomla decided to remove the getErrorMsg function in J4 and only use PHP exceptions
    if (ACYM_J40) return '';

    $acydb = acym_getGlobal('db');

    return $acydb->getErrorMsg();
}

function acym_insertObject($table, $element)
{
    $acydb = acym_getGlobal('db');
    $acydb->insertObject($table, $element);

    return $acydb->insertid();
}

function acym_updateObject($table, $element, $pkey)
{
    $acydb = acym_getGlobal('db');

    return $acydb->updateObject($table, $element, $pkey, true);
}

function acym_getPrefix()
{
    $acydb = acym_getGlobal('db');

    return $acydb->getPrefix();
}

function acym_getTableList()
{
    $acydb = acym_getGlobal('db');

    return $acydb->getTableList();
}

function acym_getCMSConfig($varname, $default = null)
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
