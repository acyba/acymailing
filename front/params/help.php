<?php

class JFormFieldHelp extends JFormField
{
    var $type = 'help';

    public function getInput()
    {
        if ('{__CMS__}' == 'Joomla' && !include_once(rtrim(JPATH_ADMINISTRATOR, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_acym'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php')) {
            echo 'This extension cannot work without AcyMailing';
        }

        $config = acym_config();
        $level = $config->get('level');
        $link = ACYM_HELPURL.$this->value.'&level='.$level;

        return '<a class="btn" target="_blank" href="'.$link.'">'.acym_translation('ACYM_HELP').'</a>';
    }
}
