<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class PluginClass extends acymClass
{
    var $table = 'plugin';
    var $pkey = 'id';

    public function getNotUptoDatePlugins()
    {
        $testPluginTable = 'SHOW TABLES LIKE "%_acym_plugin"';
        $result = acym_loadResult($testPluginTable);
        if (empty($result)) return 0;

        $query = 'SELECT count(id) FROM #__acym_plugin WHERE uptodate = 0';

        return acym_loadResult($query);
    }

    public function getOneByFolderName($folderName)
    {
        return acym_loadObject('SELECT * FROM #__acym_plugin WHERE folder_name = '.acym_escapeDB($folderName));
    }

    public function getSettings($addon)
    {
        $settings = acym_loadResult('SELECT settings FROM #__acym_plugin WHERE folder_name = '.acym_escapeDB($addon));

        return empty($settings) ? [] : json_decode($settings, true);
    }

    public function addIntegrationIfMissing($plugin)
    {
        if (empty($plugin->pluginDescription->name)) return;

        $data = $this->getOneByFolderName($plugin->name);

        // Prepare the missing entry in the db
        $newPlugin = new \stdClass();
        $newPlugin->title = $plugin->pluginDescription->name;
        $newPlugin->folder_name = $plugin->name;
        $newPlugin->version = '1.0';
        $newPlugin->active = 1;
        $newPlugin->category = $plugin->pluginDescription->category;
        $newPlugin->level = 'starter';
        $newPlugin->uptodate = 1;
        $newPlugin->features = $plugin->pluginDescription->features;
        $newPlugin->description = $plugin->pluginDescription->description;
        $newPlugin->latest_version = '1.0';
        $newPlugin->type = 'PLUGIN';

        if (!empty($data)) {
            $newPlugin->id = $data->id;
            $newPlugin->settings = $data->settings;

            if ($data->type === 'ADDON') {
                // The integration has just been activated and the old addon was installed

                // Remove the old addon's files
                if (file_exists(ACYM_ADDONS_FOLDER_PATH.$plugin->name)) {
                    acym_deleteFolder(ACYM_ADDONS_FOLDER_PATH.$plugin->name);
                }
            }
        }

        $this->save($newPlugin);
    }

    public function enable($folderName)
    {
        $plugin = $this->getOneByFolderName($folderName);
        if (empty($plugin)) return;

        $plugin->active = 1;
        $this->save($plugin);
    }

    public function disable($folderName)
    {
        $plugin = $this->getOneByFolderName($folderName);
        if (empty($plugin)) return;

        $plugin->active = 0;
        $this->save($plugin);
    }

    public function deleteByFolderName($folderName)
    {
        $plugin = $this->getOneByFolderName($folderName);
        if (empty($plugin)) return;

        parent::delete($plugin->id);
    }
}
