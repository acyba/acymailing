<?php

use AcyMailing\Classes\ListClass;

include_once __DIR__.DIRECTORY_SEPARATOR.'AcymJFormField.php';

class JFormFieldLists extends AcymJFormField
{
    public function __construct($form = null)
    {
        $this->type = 'lists';
        parent::__construct($form);
    }

    public function getInput()
    {
        //__START__joomla_
        if ('{__CMS__}' === 'Joomla') {
            $ds = DIRECTORY_SEPARATOR;
            $helper = rtrim(JPATH_ADMINISTRATOR, $ds).$ds.'components'.$ds.'com_acym'.$ds.'Core'.$ds.'init.php';
            if (!include_once $helper) {
                echo 'This extension cannot work without AcyMailing';
            }
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
        if (ACYM_CMS == 'joomla' && $this->value === 'All' && !empty($this->form)) {
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
