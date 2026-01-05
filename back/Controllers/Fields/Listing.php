<?php

namespace AcyMailing\Controllers\Fields;

use AcyMailing\Classes\FieldClass;
use AcyMailing\Helpers\ToolbarHelper;

trait Listing
{
    public function listing(): void
    {
        $data = [];
        //__START__enterprise_
        if (acym_level(ACYM_ENTERPRISE)) {
            acym_setVar('layout', 'listing');
            $fieldClass = new FieldClass();
            $fieldsElements = $fieldClass->getMatchingElements();

            $data['allFields'] = $fieldsElements['elements'];
            $data['languageFieldId'] = $fieldClass->getLanguageFieldId();

            $this->prepareToolbar($data);

            parent::display($data);

            return;
        }
        //__END__enterprise_

        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_setVar('layout', 'splashscreen');
        }

        parent::display($data);
    }

    protected function prepareToolbar(array &$data): void
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'edit'], 'add', true);

        $data['toolbar'] = $toolbarHelper;
    }

    public function ajaxSetOrdering(): void
    {
        $order = json_decode(acym_getVar('string', 'order'), true);
        if (empty($order)) {
            $order = [];
        }

        $i = 1;
        $error = false;
        foreach ($order as $field) {
            $query = 'UPDATE #__acym_field SET `ordering` = '.intval($i).' WHERE `id` = '.intval($field);
            $error = acym_query($query) < 0 || $error;
            $i++;
        }

        acym_sendAjaxResponse('', [], !$error);
    }

    public function delete(): void
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
