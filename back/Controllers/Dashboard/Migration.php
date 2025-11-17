<?php

namespace AcyMailing\Controllers\Dashboard;

use AcyMailing\Helpers\MigrationHelper;
use AcyMailing\Helpers\UpdateHelper;

trait Migration
{
    public function preMigration(): void
    {
        $elementToMigrate = acym_getVar('string', 'element', '');
        $helperMigration = new MigrationHelper();

        $result = $helperMigration->preMigration($elementToMigrate);

        if (!empty($result['isOk'])) {
            echo $result['count'];
        } else {
            $this->errorHandling($result);
        }
        exit;
    }

    public function migrate(): void
    {
        $elementToMigrate = acym_getVar('string', 'element');
        $helperMigration = new MigrationHelper();
        $functionName = 'do'.ucfirst($elementToMigrate).'Migration';

        $result = $helperMigration->$functionName($elementToMigrate);

        if (!empty($result['isOk'])) {
            echo json_encode($result);
        } else {
            $this->errorHandling($result);
        }
        exit;
    }

    public function migrationDone(): void
    {
        $this->config->saveConfig(['migration' => 1]);

        $updateHelper = new UpdateHelper();
        $updateHelper->installNotifications();
        $updateHelper->installTemplates();
        $updateHelper->installOverrideEmails();

        $this->listing();
    }

    private function errorHandling(array $result): void
    {
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

    public function migration(): bool
    {
        if ($this->config->get('migration') == 0 && acym_existsAcyMailing59()) {
            acym_setVar('layout', 'migrate');
            parent::display();

            return true;
        }

        $this->config->saveConfig(['migration' => 1]);

        return false;
    }
}
