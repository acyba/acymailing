<?php

function acym_escapeDB($value)
{
    // esc_sql replaces % by something like {svzzvzevzv} but it's normal, it will be replaced back by % before the query is executed
    return "'".esc_sql($value)."'";
}

function acym_query($query)
{
    global $wpdb;
    $query = acym_prepareQuery($query);

    $result = $wpdb->query($query);

    return $result === false ? null : $result;
}

function acym_loadObjectList($query, $key = '', $offset = null, $limit = null)
{
    global $wpdb;
    $query = acym_prepareQuery($query);

    if (isset($offset)) {
        $query .= ' LIMIT '.intval($offset).','.intval($limit);
    }

    $results = $wpdb->get_results($query);
    if (empty($key)) {
        return $results;
    }

    $sorted = [];
    foreach ($results as $oneRes) {
        $sorted[$oneRes->$key] = $oneRes;
    }

    return $sorted;
}

function acym_prepareQuery($query)
{
    global $wpdb;
    $query = str_replace('#__', $wpdb->prefix, $query);
    if (is_multisite()) {
        $query = str_replace($wpdb->prefix.'users', $wpdb->base_prefix.'users', $query);
        $query = str_replace($wpdb->prefix.'usermeta', $wpdb->base_prefix.'usermeta', $query);
    }

    return $query;
}

function acym_loadObject($query)
{
    acym_addLimit($query);

    global $wpdb;
    $query = acym_prepareQuery($query);

    return $wpdb->get_row($query);
}

function acym_loadResult($query)
{
    global $wpdb;
    $query = acym_prepareQuery($query);

    return $wpdb->get_var($query);
}

function acym_loadResultArray($query)
{
    global $wpdb;
    $query = acym_prepareQuery($query);

    return $wpdb->get_col($query);
}

function acym_getEscaped($text, $extra = false)
{
    $result = esc_sql($text);
    if ($extra) {
        $result = addcslashes($result, '%_');
    }

    return $result;
}

function acym_getDBError()
{
    global $wpdb;

    return $wpdb->last_error;
}

function acym_insertObject($table, $element)
{
    global $wpdb;
    $element = get_object_vars($element);
    $table = acym_prepareQuery($table);
    $wpdb->insert($table, $element);

    return $wpdb->insert_id;
}

function acym_updateObject($table, $element, $pkey)
{
    global $wpdb;
    $element = get_object_vars($element);
    $table = acym_prepareQuery($table);

    if (!is_array($pkey)) {
        $pkey = [$pkey];
    }

    $where = [];
    foreach ($pkey as $onePkey) {
        $where[$onePkey] = $element[$onePkey];
    }

    $nbUpdated = $wpdb->update($table, $element, $where);

    return $nbUpdated !== false;
}

function acym_getPrefix()
{
    global $wpdb;

    return $wpdb->prefix;
}

function acym_getTableList()
{
    global $wpdb;

    return acym_loadResultArray("SELECT table_name FROM information_schema.tables WHERE table_schema = '".$wpdb->dbname."' AND table_name LIKE '".$wpdb->prefix."%'");
}

function acym_getCMSConfig($varname, $default = null)
{
    $map = [
        'offset' => 'timezone_string',
        'list_limit' => 'posts_per_page',
        'sitename' => 'blogname',
        'mailfrom' => 'new_admin_email',
        'feed_email' => 'new_admin_email',
    ];

    if (!empty($map[$varname])) {
        $varname = $map[$varname];
    }
    $value = get_option($varname, $default);

    // In WP there are multiple possible formats in the same option for the timezone
    if ($varname == 'timezone_string' && empty($value)) {
        $value = acym_getCMSConfig('gmt_offset');

        if (empty($value)) {
            $value = 'UTC';
        } elseif ($value < 0) {
            $value = 'GMT'.$value;
        } else {
            $value = 'GMT+'.$value;
        }
    }

    // In WP this could be any number, but Acy pagination only works with 5,10,15,20,25,30,50 or 100
    if ($varname == 'posts_per_page') {
        $possibilities = [5, 10, 15, 20, 25, 30, 50, 100];
        $closest = 5;
        foreach ($possibilities as $possibility) {
            if (abs($value - $closest) > abs($value - $possibility)) {
                $closest = $possibility;
            }
        }
        $value = $closest;
    }

    return $value;
}
