<?php

namespace AcyMailing\Controllers\Users;

use AcyMailing\Classes\MailpoetClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\EntitySelectHelper;
use AcyMailing\Helpers\ExportHelper;
use AcyMailing\Helpers\ImportHelper;
use AcyMailing\Helpers\TabHelper;

trait Import
{
    public function import()
    {
        acym_setVar('layout', 'import');

        $userClass = new UserClass();

        // Get tables from database
        $tables = acym_getTables();
        $arrayTables = [];
        foreach ($tables as $tableName) {
            $arrayTables[$tableName] = $tableName;
        }

        $data = [
            'tab' => new TabHelper(),
            'nbUsersAcymailing' => $userClass->getCountTotalUsers(),
            'nbUsersCMS' => acym_loadResult('SELECT count('.$this->cmsUserVars->id.') FROM '.$this->cmsUserVars->table),
            'tables' => $arrayTables,
            'entitySelect' => new EntitySelectHelper(),
            'importHelper' => new ImportHelper(),
        ];

        //__START__wordpress_
        if (ACYM_CMS === 'wordpress' && acym_isExtensionActive('mailpoet/mailpoet.php')) {
            $this->prepareMailPoetList($data);
        }
        //__END__wordpress_

        //__START__joomla_
        if (ACYM_CMS === 'joomla') {
            $this->prepareContacts($data);
        }
        //__END__joomla_

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

    //__START__joomla_
    private function prepareContacts(&$data)
    {
        $data['nbUsersContact'] = acym_loadResult('SELECT COUNT(*) FROM #__contact_details');
        $data['contactCategories'] = acym_loadObjectList('SELECT `id` AS `value`, `title` AS `text` FROM #__categories WHERE `extension` = "com_contact"');
    }

    //__END__joomla_

    public function doImport()
    {
        acym_checkToken();

        $function = acym_getVar('cmd', 'import_from');
        $allowedImportModes = acym_isAdmin() ? ['file', 'textarea', 'cms', 'database', 'mailpoet', 'contact'] : ['file', 'textarea'];
        if (!in_array($function, $allowedImportModes)) {
            die('Access denied for this import method');
        }

        $importHelper = new ImportHelper();

        if (empty($function) || !$importHelper->$function()) {
            $this->import();

            return;
        }

        if (in_array($function, ['file', 'textarea'])) {
            $importFile = ACYM_MEDIA.'import'.DS.acym_getVar('cmd', 'acym_import_filename');
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
            }
        } else {
            $this->listing();
        }
    }

    public function ajaxEncoding()
    {
        acym_setVar('layout', 'ajaxencoding');

        $data = [];

        ob_start();
        parent::display($data);

        $data = [
            'preview' => ob_get_clean(),
        ];

        acym_sendAjaxResponse('', $data);
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
