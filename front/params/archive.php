<?php

class JFormFieldArchive extends JFormField
{
    var $type = 'archive';

    public function getInput()
    {
        if ('{__CMS__}' == 'Joomla' && !include_once(rtrim(JPATH_ADMINISTRATOR, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_acym'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php')) {
            echo 'This extension cannot work without AcyMailing';
        }

        $value = empty($this->value) ? 0 : $this->value;

        $return = '<input type="number" value="'.$value.'" name="'.$this->name.'" min="0">';

        return $return;
    }
}
