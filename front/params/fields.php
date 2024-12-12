<?php

use AcyMailing\Classes\FieldClass;


include_once __DIR__.DIRECTORY_SEPARATOR.'field.php';

class JFormFieldFields extends acym_JFormField
{
    var $type = 'fields';
    public $value;

    public function getInput()
    {
        //__START__joomla_
        if ('{__CMS__}' === 'Joomla') {
            $ds = DIRECTORY_SEPARATOR;
            $helper = rtrim(JPATH_ADMINISTRATOR, $ds).$ds.'components'.$ds.'com_acym'.$ds.'helpers'.$ds.'helper.php';
            if (!include_once $helper) {
                echo 'This extension cannot work without AcyMailing';
            }
        }

        //__END__joomla_

        $fieldsClass = new FieldClass();
        $allFields = $fieldsClass->getAllFieldsForModuleFront();
        $fields = [];
        foreach ($allFields as $field) {
            $fields[$field->id] = acym_translation($field->name);
        }


        // In Joomla, when the user chooses to empty a field, it sets the default value of this field... that's not what we want
        if (ACYM_CMS == 'joomla' && $this->value == '1') {
            $formId = $this->form->getData()->get('id');
            if (!empty($formId)) {
                $this->value = '';
            }
        }

        if (is_string($this->value)) {
            $this->value = explode(',', $this->value);
        }

        if (in_array('None', $this->value)) {
            $this->value = [];
        }
        if (in_array('All', $this->value)) {
            $this->value = array_keys($fields);
        }

        return acym_selectMultiple(
            $fields,
            $this->name,
            $this->value,
            [
                'class' => 'acym_simple_select2',
                'id' => $this->name,
            ]
        );
    }
}
