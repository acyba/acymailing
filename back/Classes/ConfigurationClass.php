<?php

namespace AcyMailing\Classes;

use AcyMailing\Controllers\ConfigurationController;
use AcyMailing\Core\AcymClass;

class ConfigurationClass extends AcymClass
{
    private array $values = [];

    public function __construct()
    {
        parent::__construct();

        $this->table = 'configuration';
        $this->pkey = 'name';
    }

    public function load(): void
    {
        $this->values = acym_loadObjectList('SELECT * FROM #__acym_configuration', 'name');
    }

    public function get(string $namekey, $default = '')
    {
        if (isset($this->values[$namekey])) {
            return $this->values[$namekey]->value;
        }

        return $default;
    }

    /**
     * @param object|array $element
     *
     * @Deprecated 10.6.0 No longer used because of type mismatch.
     * @See        ConfigurationClass::saveConfig() as a replacement.
     */
    public function save($element): ?int
    {
        $this->saveConfig((array)$element);

        return null;
    }

    public function saveConfig(array $newConfig, bool $escape = true): bool
    {
        $oldFollowupPriority = $this->get('followup_max_priority', 0) == 1;

        $previousCronSecurity = $this->get('cron_security', 0);
        $previousCronSecurityKey = $this->get('cron_key');
        $params = [];
        foreach ($newConfig as $name => $value) {
            //If it's a password containing only * then we just consider the user saved again the config but there is no modification on the password
            if (!empty($value)) {
                if (strpos($name, 'password') !== false && trim($value, '*') === '') {
                    continue;
                }
                if (strpos($name, 'key') !== false && strpos($value, '**********') !== false) {
                    continue;
                }
            }

            if ($name === 'multilingual' && $value === '1') {
                $remindme = json_decode($this->get('remindme', '[]'), true);
                if (!in_array('multilingual', $remindme)) {
                    $remindme[] = 'multilingual';
                    $params[] = '("remindme",'.acym_escapeDB(json_encode($remindme)).')';
                }
            }

            if (is_array($value)) {
                $value = implode(',', $value);
            }

            //We update the current instance in the same time
            if (empty($this->values[$name])) {
                $this->values[$name] = new \stdClass();
            }
            $this->values[$name]->value = $value;

            // We do a strip tags to avoid HTML injections
            if ($escape && !is_null($value)) {
                $params[] = '('.acym_escapeDB(strip_tags($name)).','.acym_escapeDB(strip_tags($value)).')';
            } else {
                $params[] = '('.acym_escapeDB($name).','.acym_escapeDB($value).')';
            }
        }

        $activeCron = $this->get('active_cron', 0);
        $newCronSecurity = $this->get('cron_security', 0);
        $newCronSecurityKey = $this->get('cron_key');
        // Handle cron key activation or modification while automated tasks are active
        if (!empty($activeCron) && !empty($newCronSecurity) && (empty($previousCronSecurity) || $previousCronSecurityKey !== $newCronSecurityKey)) {
            $configurationController = new ConfigurationController();
            $deactivationResult = $configurationController->modifyCron('deactivateCron');
            if (!empty($deactivationResult)) {
                $configurationController->modifyCron('activateCron');
            }
        }

        if (empty($params)) {
            return true;
        }

        try {
            // We do a replace so that values are always kept up to date and added if necessary in the mean time
            $status = acym_query('REPLACE INTO #__acym_configuration (`name`, `value`) VALUES '.implode(',', $params));
        } catch (\Exception $e) {
            $status = false;
        }

        if ($status === false) {
            acym_display(isset($e) ? $e->getMessage() : substr(strip_tags(acym_getDBError()), 0, 200).'...', 'error');
        }

        $newFollowupPriority = $this->get('followup_max_priority', 0) == 1;

        $mailClass = new MailClass();
        $mailClass->updateFollowupPriority($oldFollowupPriority, $newFollowupPriority);

        return $status;
    }
}
