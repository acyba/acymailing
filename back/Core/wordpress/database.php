<?php

function acym_escapeDB(?string $value): string
{
    if (is_null($value)) {
        $value = '';
    }

    // esc_sql replaces % by something like {svzzvzevzv} but it's normal, it will be replaced back by % before the query is executed
    return "'".esc_sql($value)."'";
}

function acym_query(string $query)
{
    global $wpdb;
    $query = acym_prepareQuery($query);

    $result = $wpdb->query($query);

    return $result === false ? null : $result;
}

function acym_loadObjectList(string $query, string $key = '', ?int $offset = null, ?int $limit = null): array
{
    global $wpdb;
    $query = acym_prepareQuery($query);

    if (isset($offset)) {
        $query .= ' LIMIT '.intval($offset).','.intval($limit);
    }

    $results = $wpdb->get_results($query);
    if (empty($key)) {
        return empty($results) ? [] : $results;
    }

    $sorted = [];
    foreach ($results as $oneRes) {
        $sorted[$oneRes->$key] = $oneRes;
    }

    return $sorted;
}

function acym_prepareQuery(string $query): string
{
    global $wpdb;
    $query = str_replace('#__', $wpdb->prefix, $query);
    if (is_multisite()) {
        $query = str_replace($wpdb->prefix.'users', $wpdb->base_prefix.'users', $query);
        $query = str_replace($wpdb->prefix.'usermeta', $wpdb->base_prefix.'usermeta', $query);
    }

    return $query;
}

function acym_loadObject(string $query): ?object
{
    acym_addLimit($query);

    global $wpdb;
    $query = acym_prepareQuery($query);

    $object = $wpdb->get_row($query);

    return empty($object) ? null : $object;
}

function acym_loadResult(string $query)
{
    global $wpdb;
    $query = acym_prepareQuery($query);

    return $wpdb->get_var($query);
}

function acym_loadResultArray(string $query): array
{
    global $wpdb;
    $query = acym_prepareQuery($query);

    return $wpdb->get_col($query);
}

function acym_getEscaped(string $text, bool $extra = false)
{
    $result = esc_sql($text);
    if ($extra) {
        $result = addcslashes($result, '%_');
    }

    return $result;
}

function acym_getDBError(): string
{
    global $wpdb;

    return empty($wpdb->last_error) ? '' : $wpdb->last_error;
}

function acym_insertObject(string $table, object $element): ?int
{
    global $wpdb;
    $element = get_object_vars($element);
    $table = acym_prepareQuery($table);
    $wpdb->insert($table, $element);

    $id = $wpdb->insert_id;

    return empty($id) ? null : (int)$id;
}

function acym_updateObject(string $table, object $element, array $pkey): bool
{
    global $wpdb;
    $element = get_object_vars($element);
    $table = acym_prepareQuery($table);

    $where = [];
    foreach ($pkey as $onePkey) {
        $where[$onePkey] = $element[$onePkey];
    }

    $nbUpdated = $wpdb->update($table, $element, $where);

    return $nbUpdated !== false;
}

function acym_getPrefix(): string
{
    global $wpdb;

    return $wpdb->prefix;
}

function acym_getTableList(): array
{
    global $wpdb;

    return acym_loadResultArray(
        'SELECT table_name FROM information_schema.tables WHERE table_schema = '.acym_escapeDB($wpdb->dbname).' AND table_name LIKE '.acym_escapeDB($wpdb->prefix.'%')
    );
}

/**
 * @param mixed $default
 *
 * @return mixed
 */
function acym_getCMSConfig(string $varname, $default = null)
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
    if ($varname === 'posts_per_page') {
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
