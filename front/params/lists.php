<?php

use AcyMailing\Classes\ListClass;

class JFormFieldLists extends JFormField
{
    var $type = 'lists';

    public function getInput()
    {
        //__START__joomla_
        if ('{__CMS__}' === 'Joomla' && !include_once(rtrim(
                    JPATH_ADMINISTRATOR,
                    DIRECTORY_SEPARATOR
                ).DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_acym'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php')) {
            echo 'This extension cannot work without AcyMailing';
        }
        //__END__joomla_

        $listClass = new ListClass();
        $lists = $listClass->getAllWithoutManagement();
        foreach ($lists as $i => $oneList) {
            if ($oneList->active == 0) {
                unset($lists[$i]);
            }
        }

        // In Joomla, when the user chooses to empty a field, it sets the default value of this field... that's not what we want
        if (ACYM_CMS == 'joomla' && $this->value == 'All' && !empty($this->form)) {
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
            $visibleLists = [];
            foreach ($lists as $listId => $oneList) {
                if ($oneList->visible == 0) continue;

                $visibleLists[] = $listId;
            }
            $this->value = $visibleLists;
        }

        return acym_selectMultiple(
            $lists,
            $this->name,
            $this->value,
            [
                'class' => 'acym_simple_select2',
                'id' => $this->name,
            ],
            'id',
            'name'
        );
    }
}
