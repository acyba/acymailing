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
        if ($type === 'update') {
            $db = Factory::getDbo();
            $query = $db->getQuery(true)->select('*')->from('#__extensions');
            $query->where('type = "component" AND element = "com_acym"');
            $db->setQuery($query);

            try {
                $extension = $db->loadObject();
            } catch (Exception $e) {
                echo Text::sprintf('JLIB_DATABASE_ERROR_FUNCTION_FAILED', $e->getCode(), $e->getMessage()).'<br />';

                return false;
            }

            if (!empty($extension->extension_id)) {
                $installer = new Installer();
                $installer->refreshManifestCache($extension->extension_id);
            }
        }

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
