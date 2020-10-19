<?php

function acym_escapeDB($value)
{
    $acydb = acym_getGlobal('db');

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

function acym_loadObjectList($query, $key = '', $offset = null, $limit = null)
{
    $acydb = acym_getGlobal('db');

    $acydb->setQuery($query, $offset, $limit);

    return $acydb->loadObjectList($key);
}

function acym_prepareQuery($query)
{
    $query = str_replace('#__', acym_getPrefix(), $query);

    return $query;
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
    if (is_string($query)) {
        $acydb = acym_getGlobal('db');
        $acydb->setQuery($query);
    } else {
        $acydb = $query;
    }

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
    if (ACYM_J30) {
        $acyapp = acym_getGlobal('app');

        return $acyapp->getCfg($varname, $default);
    }

    $conf = JFactory::getConfig();
    $val = $conf->getValue('config.'.$varname);

    return empty($val) ? $default : $val;
}
