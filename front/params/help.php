<?php

include_once __DIR__.DIRECTORY_SEPARATOR.'field.php';

class JFormFieldHelp extends acym_JFormField
{
    var $type = 'help';

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

        $config = acym_config();
        $level = $config->get('level');
        $link = ACYM_DOCUMENTATION;

        return '<a class="btn" target="_blank" href="'.$link.'">'.acym_translation('ACYM_HELP').'</a>';
    }
}
