<?php
// context verification

include_once __DIR__.DIRECTORY_SEPARATOR.'AcymJFormField.php';

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Joomla specific class naming with imposed prefix "JFormField".
class JFormFieldHelp extends AcymJFormField
{
    public function __construct($form = null)
    {
        $this->type = 'help';
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

        $config = acym_config();
        $level = $config->get('level');
        $link = ACYM_DOCUMENTATION;

        return '<a class="btn" target="_blank" href="'.$link.'">'.acym_translation('ACYM_HELP').'</a>';
    }
}
