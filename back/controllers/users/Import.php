<?php

namespace AcyMailing\Controllers\Users;

use AcyMailing\Classes\MailpoetClass;
use AcyMailing\Helpers\EntitySelectHelper;
use AcyMailing\Helpers\ExportHelper;
use AcyMailing\Helpers\ImportHelper;
use AcyMailing\Helpers\TabHelper;

trait Import
{
    public function import()
    {
        acym_setVar('layout', 'import');

        $tab = new TabHelper();

        $nbUsersAcymailing = $this->currentClass->getCountTotalUsers();
        $nbUsersCMS = acym_loadResult('SELECT count('.$this->cmsUserVars->id.') FROM '.$this->cmsUserVars->table);

        // Get tables from database
        $tables = acym_getTables();
        $arrayTables = [];
        foreach ($tables as $key => $tableName) {
            $arrayTables[$tableName] = $tableName;
        }

        $data = [
            'tab' => $tab,
            'nbUsersAcymailing' => $nbUsersAcymailing,
            'nbUsersCMS' => $nbUsersCMS,
            'tables' => $arrayTables,
            'entitySelect' => new EntitySelectHelper(),
            'importHelper' => new ImportHelper(),
        ];

        //__START__wordpress_
        if (ACYM_CMS === 'wordpress' && acym_isExtensionActive('mailpoet/mailpoet.php')) {
            $this->prepareMailPoetList($data);
        }
        //__END__wordpress_

        $this->breadcrumb[acym_translation('ACYM_IMPORT')] = acym_completeLink('users&task=import');
        $data['menuClass'] = $this->menuClass;

        parent::display($data);
    }

    //__START__wordpress_
    private function prepareMailPoetList(&$data)
    {
        $mailpoetClass = new MailpoetClass();
        $data['mailpoet_list'] = $mailpoetClass->getAllLists();
    }

    //__END__wordpress_

    public function ajaxEncoding()
    {
        acym_setVar('layout', 'ajaxencoding');
        parent::display();
        exit;
    }

    public function doImport()
    {
        acym_checkToken();

        $function = acym_getVar('cmd', 'import_from');
        $importHelper = new ImportHelper();

        if (empty($function) || !$importHelper->$function()) {
            $this->import();

            return;
        }

        if ($function == 'textarea' || $function == 'file') {
            $importFile = ACYM_MEDIA.'import'.DS.acym_getVar('cmd', 'filename');
            if (file_exists($importFile)) {
                $importContent = file_get_contents($importFile);
            }
            if (empty($importContent)) {
                acym_enqueueMessage(acym_translation('ACYM_EMPTY_TEXTAREA'), 'error');
                $this->import();
            } else {
                acym_setVar('layout', 'genericimport');
                $this->breadcrumb[acym_translation('ACYM_IMPORT')] = acym_completeLink('users&task=import');
                parent::display();

                return;
            }
        } else {
            $this->listing();
        }
    }

    public function finalizeImport()
    {
        $importHelper = new ImportHelper();
        $importHelper->finalizeImport();

        $this->listing();
    }

    public function downloadImport()
    {
        $filename = acym_getVar('cmd', 'filename');
        if (!file_exists(ACYM_MEDIA.'import'.DS.$filename.'.csv')) {
            return;
        }
        $exportHelper = new ExportHelper();
        $exportHelper->setDownloadHeaders($filename);
        echo file_get_contents(ACYM_MEDIA.'import'.DS.$filename.'.csv');
        exit;
    }

    public function getColumnsFromTable()
    {
        $tableName = acym_secureDBColumn(acym_getVar('string', 'tablename', ''));
        if (empty($tableName)) {
            exit;
        }
        $columns = acym_getColumns($tableName, false, false);
        $allColumnsSelect = '<option value=""></option>';
        foreach ($columns as $oneColumn) {
            $allColumnsSelect .= '<option value="'.acym_escape($oneColumn).'">'.$oneColumn.'</option>';
        }

        echo $allColumnsSelect;
        exit;
    }
}
