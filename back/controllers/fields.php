<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\FieldClass;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Libraries\acymController;
use AcyMailing\Types\OperatorType;

class FieldsController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_CUSTOM_FIELDS')] = acym_completeLink('fields');
    }

    public function listing()
    {
        $data = [];

        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_setVar('layout', 'splashscreen');
        }

        return parent::display($data);
    }

    protected function prepareToolbar(&$data)
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'edit'], 'add', true);

        $data['toolbar'] = $toolbarHelper;
    }

    public function edit()
    {
        acym_setVar('layout', 'edit');
        $id = acym_getVar('int', 'id');
        $fieldClass = new FieldClass();
        $languageFieldId = $fieldClass->getLanguageFieldId();

        if (empty($id)) {
            $field = new \stdClass();
            $field->id = 0;
            $field->name = '';
            $field->active = 1;
            $field->type = 'text';
            $field->value = '';
            $field->option = '';
            $field->default_value = '';
            $field->required = 0;
            $field->backend_edition = 1;
            $field->backend_listing = 0;
            $field->frontend_edition = 1;
            $field->frontend_listing = 0;
            $field->access = 1;
            $field->fieldDB = new \stdClass();
        } else {
            $field = $fieldClass->getOneById($id);
            $field->option = json_decode($field->option);
            $field->value = json_decode($field->value);
            $field->fieldDB = empty($field->option->fieldDB) ? new \stdClass() : json_decode($field->option->fieldDB);
            if (!in_array($id, [1, 2, $languageFieldId]) && !empty($field->fieldDB->table)) {
                $databaseExists = acym_loadResult('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '.acym_escapeDB($field->fieldDB->database));
                if (!empty($databaseExists)) {
                    $tables = acym_loadResultArray('SHOW TABLES FROM `'.acym_secureDBColumn($field->fieldDB->database).'`');
                    $field->fieldDB->tables = [];
                    foreach ($tables as $one) {
                        $field->fieldDB->tables[$one] = $one;
                    }
                    if (empty($field->fieldDB->table)) {
                        $columns = [];
                    } else {
                        $tables = acym_loadResultArray(
                            'SELECT table_name 
                            FROM information_schema.tables 
                            WHERE table_schema = '.acym_escapeDB($field->fieldDB->database)
                        );
                        if (in_array($field->fieldDB->table, $tables)) {
                            $columns = acym_loadResultArray(
                                'SHOW COLUMNS FROM '.acym_secureDBColumn($field->fieldDB->table).' FROM '.acym_secureDBColumn($field->fieldDB->database)
                            );
                        } else {
                            $columns = [];
                        }
                    }
                    $field->fieldDB->columns = [];
                    foreach ($columns as $one) {
                        $field->fieldDB->columns[$one] = $one;
                    }
                    array_unshift($field->fieldDB->columns, acym_translation('ACYM_CHOOSE_COLUMN'));
                }
            }
            //DONT ERASE PLEASE
            //$field->display = json_decode($field->option->display);
        }

        if (!empty($id)) {
            $this->breadcrumb[acym_escape(acym_translation($field->name))] = acym_completeLink('fields&task=edit&id='.$id);
        } else {
            $this->breadcrumb[acym_translation('ACYM_NEW_CUSTOM_FIELD')] = acym_completeLink('fields&task=edit');
        }

        $allFields = $fieldClass->getAll();

        $allFieldsName = [];
        foreach ($allFields as $one) {
            $allFieldsName[$one->id] = $one->name;
        }

        $data = [
            'field' => $field,
            'database' => acym_getDatabases(),
            'allFields' => $allFieldsName,
            'operatorType' => new OperatorType(),
            'languageFieldId' => $languageFieldId,
        ];

        $data['fieldType'] = [
            'text' => acym_translation('ACYM_TEXT'),
            'textarea' => acym_translation('ACYM_TEXTAREA'),
            'radio' => acym_translation('ACYM_RADIO'),
            'checkbox' => acym_translation('ACYM_CHECKBOX'),
            'single_dropdown' => acym_translation('ACYM_SINGLE_DROPDOWN'),
            'multiple_dropdown' => acym_translation('ACYM_MULTIPLE_DROPDOWN'),
            'date' => acym_translation('ACYM_DATE'),
            'file' => acym_translation('ACYM_FILE'),
            'phone' => acym_translation('ACYM_PHONE'),
            'custom_text' => acym_translation('ACYM_CUSTOM_TEXT'),
            'language' => acym_translation('ACYM_LANGUAGE'),
        ];

        $this->prepareMultilingualOption($data);

        return parent::display($data);
    }

    public function getTables()
    {
        $database = acym_getVar('string', 'database');
        $allTables = acym_loadResultArray('SHOW TABLES FROM '.$database);
        echo json_encode($allTables);
        exit;
    }

    public function setColumns()
    {
        $table = acym_getVar('string', 'table');
        $database = acym_getVar('string', 'database');
        $query = 'SHOW COLUMNS FROM '.$table.' FROM '.$database;
        $columns = acym_loadResultArray($query);
        array_unshift($columns, 'ACYM_CHOOSE_COLUMN');
        echo json_encode($columns);
        exit;
    }

    public function apply()
    {
        $this->saveField();
        $this->edit();
    }

    public function save()
    {
        $this->saveField();
        $this->listing();
    }

    protected function saveField()
    {
        $fieldClass = new FieldClass();
        $newField = $this->setFieldToSave();
        $id = $fieldClass->save($newField);
        if (!empty($id)) {
            acym_setVar('id', $id);
            acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
        }
    }

    private function setFieldToSave()
    {
        $fieldClass = new FieldClass();
        $languageFieldId = $fieldClass->getLanguageFieldId();
        $field = acym_getVar('array', 'field');
        $fieldDB = json_encode(acym_getVar('array', 'fieldDB'));
        $id = acym_getVar('int', 'id');
        if (in_array($id, [2, $languageFieldId])) {
            $field['required'] = 1;
        }
        if (empty($field['name'])) return false;

        if (in_array($id, [1, 2])) {
            $field['type'] = 'text';
        } elseif ($id == $languageFieldId) {
            $field['type'] = 'language';
        }

        $i = 0;
        $value = [];
        $fieldValues = $field['value'];
        foreach ($fieldValues['value'] as $one) {
            if (empty($one) && $one != '0' && ($i != 0 || !in_array($field['type'], ['single_dropdown', 'multiple_dropdown']))) {
                $i++;
                continue;
            }

            $value[$i] = [
                'value' => $one,
                'title' => $fieldValues['title'][$i],
                'disabled' => $fieldValues['disabled'][$i],
            ];
            $i++;
        }

        $field['name'] = strip_tags($field['name'], '<i><b><strong>');
        $field['namekey'] = empty($field['namekey']) ? $fieldClass->generateNamekey($field['name']) : $field['namekey'];
        $field['option']['format'] = $field['type'] === 'date' && empty($field['option']['format']) ? '%d%m%y' : strtolower($field['option']['format']);
        $field['option']['rows'] = ($field['type'] == 'textarea' && empty($field['option']['rows'])) ? '5' : $field['option']['rows'];
        $field['option']['columns'] = ($field['type'] == 'textarea' && empty($field['option']['columns'])) ? '30' : $field['option']['columns'];

        $field['value'] = json_encode($value);
        $field['option']['fieldDB'] = $fieldDB;
        if (!empty($field['option']['format'])) {
            $field['option']['format'] = preg_replace('/[^a-z\%]/', '', $field['option']['format']);
            $field['option']['format'] = preg_replace('/\%[^ymd]/', '', $field['option']['format']);
        }

        $newField = new \stdClass();
        $newField->name = $field['name'];
        $newField->active = $field['active'];
        $newField->namekey = $field['namekey'];
        $newField->type = $field['type'];
        $newField->required = $field['required'];
        $newField->option = json_encode($field['option']);
        $newField->value = $field['value'];
        $newField->default_value = $field['default_value'];
        if (ACYM_CMS == 'joomla') {
            $newField->frontend_edition = $field['frontend_edition'];
            $newField->frontend_listing = $field['frontend_listing'];
        }
        $newField->backend_edition = $field['backend_edition'];
        $newField->backend_listing = $field['backend_listing'];
        $newField->access = 'all';

        if (empty($id)) {
            $newField->ordering = $fieldClass->getOrdering() + 1;
        } else {
            $newField->id = $id;
        }

        if (!empty($field['translation'])) {
            $newField->translation = $field['translation'];
        }

        return $newField;
    }

    public function setOrdering()
    {
        $order = json_decode(acym_getVar('string', 'order'));
        $i = 1;
        $error = false;
        foreach ($order as $field) {
            $query = 'UPDATE #__acym_field SET `ordering` = '.intval($i).' WHERE `id` = '.intval($field);
            $error = acym_query($query) >= 0 ? false : true;
            $i++;
        }
        if ($error) {
            echo 'error';
        } else {
            echo 'updated';
        }
        exit;
    }

    public function delete()
    {
        $fieldClass = new FieldClass();
        $ids = acym_getVar('cmd', 'elements_checked');
        if (in_array('1', $ids) || in_array('2', $ids) || in_array($fieldClass->getLanguageFieldId(), $ids)) {
            acym_enqueueMessage(acym_translation('ACYM_CANT_DELETE'), 'error');
            $this->listing();
        } else {
            parent::delete();
        }
    }
}
