<?php

namespace AcyMailing\Controllers\Fields;

use AcyMailing\Classes\FieldClass;
use AcyMailing\Helpers\ToolbarHelper;

trait Listing
{
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
