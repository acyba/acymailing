<?php

namespace AcyMailing\Controllers\Dashboard;

use AcyMailing\Helpers\MigrationHelper;
use AcyMailing\Helpers\UpdateHelper;

trait Migration
{
    public function preMigration()
    {
        $elementToMigrate = acym_getVar('string', 'element');
        $helperMigration = new MigrationHelper();

        $result = $helperMigration->preMigration($elementToMigrate);

        if (!empty($result['isOk'])) {
            echo $result['count'];
        } else {
            echo 'ERROR : ';
            if (!empty($result['errorInsert'])) {
                echo strtoupper(acym_translation('ACYM_INSERT_ERROR'));
            }
            if (!empty($result['errorClean'])) {
                echo strtoupper(acym_translation('ACYM_CLEAN_ERROR'));
            }

            if (!empty($result['errors'])) {
                echo '<br>';

                foreach ($result['errors'] as $key => $oneError) {
                    echo '<br>'.$key.' : '.$oneError;
                }
            }
        }
        exit;
    }

    public function migrate()
    {
        $elementToMigrate = acym_getVar('string', 'element');
        $helperMigration = new MigrationHelper();
        $functionName = 'do'.ucfirst($elementToMigrate).'Migration';

        $result = $helperMigration->$functionName($elementToMigrate);

        if (!empty($result['isOk'])) {
            echo json_encode($result);
        } else {
            echo 'ERROR : ';
            if (!empty($result['errorInsert'])) {
                echo strtoupper(acym_translation('ACYM_INSERT_ERROR'));
            }
            if (!empty($result['errorClean'])) {
                echo strtoupper(acym_translation('ACYM_CLEAN_ERROR'));
            }

            if (!empty($result['errors'])) {
                echo '<br>';

                foreach ($result['errors'] as $key => $oneError) {
                    echo '<br>'.$key.' : '.$oneError;
                }
            }
        }
        exit;
    }

    public function migrationDone()
    {
        $newConfig = new \stdClass();
        $newConfig->migration = '1';
        $this->config->save($newConfig);

        $updateHelper = new UpdateHelper();
        $updateHelper->installNotifications();
        $updateHelper->installTemplates();
        $updateHelper->installOverrideEmails();

        $this->listing();
    }

    private function acym_existsAcyMailing59()
    {
        $allTables = acym_getTables();

        if (in_array(acym_getPrefix().'acymailing_config', $allTables)) {
            $queryVersion = 'SELECT `value` FROM #__acymailing_config WHERE `namekey` LIKE "version"';

            $version = acym_loadResult($queryVersion);

            if (version_compare($version, '5.9.0') >= 0) {
                return true;
            }
        }

        return false;
    }

    public function migration()
    {
        if ($this->config->get('migration') == 0 && acym_existsAcyMailing59()) {
            acym_setVar('layout', 'migrate');
            parent::display();

            return true;
        }

        $newConfig = new \stdClass();
        $newConfig->migration = '1';
        $this->config->save($newConfig);

        return false;
    }
}
