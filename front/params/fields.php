<?php

use AcyMailing\Classes\FieldClass;

class JFormFieldFields extends JFormField
{
    var $type = 'fields';

    public function getInput()
    {
        //__START__joomla_
        if ('{__CMS__}' === 'Joomla' && !include_once(rtrim(JPATH_ADMINISTRATOR, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_acym'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php')) {
            echo 'This extension cannot work without AcyMailing';
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

        return acym_selectMultiple($fields, $this->name, $this->value, ['id' => $this->name]);
    }
}
