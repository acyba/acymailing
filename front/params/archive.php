<?php

class JFormFieldArchive extends JFormField
{
    var $type = 'archive';

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

        $value = empty($this->value) ? 0 : $this->value;

        return acym_select(
            [
                '5' => '5',
                '10' => '10',
                '15' => '15',
                '20' => '20',
                '30' => '30',
                '50' => '50',
                '100' => '100',
                '200' => '200',
            ],
            $this->name,
            $value
        );
    }
}
