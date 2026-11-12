<?php

namespace AcyMailing\Classes;

use AcyMailing\Core\AcymClass;

class StatsCacheClass extends AcymClass
{
    const LIFETIME = 3600;

    public function __construct()
    {
        parent::__construct();

        $this->table = 'stats_cache';
        $this->pkey = 'name';
    }

    public function getOrCompute(string $name, callable $compute)
    {
        $cached = acym_loadObject('SELECT `value`, `expiration_date` FROM #__acym_stats_cache WHERE `name` = '.acym_escapeDB($name));
        if (!empty($cached) && $cached->expiration_date > time()) {
            return unserialize($cached->value, ['allowed_classes' => [\stdClass::class]]);
        }

        $value = $compute();

        try {
            acym_query(
                'INSERT INTO #__acym_stats_cache (`name`, `value`, `expiration_date`)
                VALUES ('.acym_escapeDB($name).', '.acym_escapeDB(serialize($value)).', '.intval(time() + self::LIFETIME).')
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `expiration_date` = VALUES(`expiration_date`)'
            );
        } catch (\Exception $e) {
        }

        return $value;
    }
}
