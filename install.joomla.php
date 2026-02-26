<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Installer\Installer;

class com_acymInstallerScript
{
    public function install($parent)
    {
    }

    public function update($parent)
    {
    }

    public function preflight($type, $parent)
    {
        return true;
    }

    public function postflight($type, $parent)
    {
        return true;
    }

    public function uninstall($parent)
    {
        $db = Factory::getDbo();
        $jversion = preg_replace('#[^0-9\.]#i', '', JVERSION);
        $method = version_compare($jversion, '4.0.0', '>=') ? 'execute' : 'query';

        $db->setQuery('UPDATE `#__modules` SET `published` = 0 WHERE `module` = "mod_acym"');
        $db->$method();
        $db->setQuery(
            'UPDATE `#__extensions` 
			SET `enabled` = 0 
			WHERE `enabled` = 1 
				AND `type` = "plugin" 
				AND `element` IN ("acymtriggers", "acymailoverride", "acymailing", "jceacym")'
        );
        $db->$method();
    }
}
