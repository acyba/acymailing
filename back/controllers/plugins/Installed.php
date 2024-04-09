<?php

namespace AcyMailing\Controllers\Plugins;

use AcyMailing\Classes\PluginClass;
use AcyMailing\Helpers\WorkflowHelper;

trait Installed
{
    public function installed()
    {
        acym_setVar('layout', 'installed');

        $data = [];
        $data['tabs'] = $this->tabs;
        $data['tab'] = 'installed';
        $data['types'] = $this->types;
        $data['level'] = $this->level;
        $data['plugins'] = $this->getAllPlugins();
        $data['workflowHelper'] = new WorkflowHelper();

        parent::display($data);
    }

    public function checkUpdates()
    {
        acym_checkPluginsVersion();
        $this->installed();

        return true;
    }

    public function saveSettings()
    {
        $pluginFolderName = acym_getVar('string', 'plugin__folder_name', '');

        if (empty($pluginFolderName)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_SAVE_SETTINGS'));
            $this->installed();

            return;
        }

        $pluginClass = new PluginClass();
        $plugin = $pluginClass->getOnePluginByFolderName($pluginFolderName);

        if (empty($plugin)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_SAVE_SETTINGS'));
            $this->installed();

            return;
        }
        $plugin->settings = empty($plugin->settings) ? [] : json_decode($plugin->settings, true);

        $pluginSettings = acym_getVar('array', $pluginFolderName, []);

        if (empty($pluginSettings)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_SAVE_SETTINGS'));
            $this->installed();

            return;
        }

        foreach ($pluginSettings as $key => $value) {
            if ($value == 'on') $value = 1;
            $plugin->settings[$key] = ['value' => $value];
        }

        foreach ($plugin->settings as $key => $value) {
            if (!array_key_exists($key, $pluginSettings)) $plugin->settings[$key] = ['value' => ''];
        }

        $plugin->settings = json_encode($plugin->settings);
        $pluginClass->save($plugin);

        $this->installed();
    }

    private function isLatestAcyMailingVersion()
    {
        $currentVersion = $this->config->get('version', '');
        $latestVersion = $this->config->get('latestversion', '');
        if (!version_compare($currentVersion, $latestVersion, '>=')) {
            acym_sendAjaxResponse(acym_translation('ACYM_NEED_LATEST_VERSION_TO_DOWNLOAD'), [], false);
        }
    }

    public function update()
    {
        $this->isLatestAcyMailingVersion();

        $plugin = acym_getVar('array', 'plugin');

        $pluginClass = new PluginClass();
        $id = $pluginClass->updateAddon($plugin['folder_name']);
        if (!empty($id)) {
            acym_sendAjaxResponse(acym_translation('ACYM_ADD_ON_SUCCESSFULLY_UPDATED'));
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_UPDATE_ADD_ON'), [], false);
        }
    }

    public function deletePlugin()
    {
        $pluginClass = new PluginClass();
        $id = acym_getVar('int', 'id');

        $plugin = $pluginClass->getOneById($id);

        if (empty($plugin)) {
            acym_sendAjaxResponse(acym_translation('ACYM_ADD_ON_NOT_FOUND'), [], false);
        }

        $routePlugin = dirname(acym_getPluginPath($plugin->folder_name));

        if (file_exists($routePlugin)) {
            acym_deleteFolder($routePlugin);
            if ($pluginClass->delete($id)) {
                acym_sendAjaxResponse(acym_translation('ACYM_ADD_ON_SUCCESSFULLY_DELETED'));
            }
        }
        acym_sendAjaxResponse(acym_translation('ACYM_ADD_ON_NOT_FOUND'), [], false);
    }

    public function toggleActivate()
    {
        $pluginClass = new PluginClass();
        $id = acym_getVar('int', 'id');

        $plugin = $pluginClass->getOneById($id);


        if (empty($plugin)) {
            acym_sendAjaxResponse(acym_translation('ACYM_ADD_ON_NOT_FOUND'), [], false);
        }

        $plugin->active = $plugin->active == 0 ? 1 : 0;

        if (!$pluginClass->save($plugin)) {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_SAVE_ADD_ON'), [], false);
        }

        acym_sendAjaxResponse();
    }

    private function getPluginClassAjaxCustomView()
    {
        $return = [];
        $return['folderName'] = acym_getVar('string', 'plugin');
        $return['className'] = acym_getVar('string', 'plugin_class');

        if (empty($return['folderName']) && empty($return['className'])) {
            acym_sendAjaxResponse(acym_translation('ACYM_CUSTOM_VIEW_NOT_FOUND'), [], false);
        }

        return $return;
    }

    public function getCustomViewPlugin()
    {
        $plugin = $this->getPluginClassAjaxCustomView();

        $customLayoutPath = ACYM_CUSTOM_PLUGIN_LAYOUT.$plugin['folderName'].'.html';

        $customView = '';

        if (file_exists($customLayoutPath)) $customView = acym_fileGetContent($customLayoutPath);
        if (empty($customView)) acym_trigger('getStandardStructure', [&$customView], $plugin['className']);

        acym_sendAjaxResponse('', ['content' => $customView]);
    }

    public function saveCustomViewPlugin()
    {
        $plugin = $this->getPluginClassAjaxCustomView();
        $pluginCustomView = acym_getVar('string', 'custom_view', '');
        $pluginCustomView = urldecode($pluginCustomView);

        $customLayoutPath = ACYM_CUSTOM_PLUGIN_LAYOUT.$plugin['folderName'].'.html';

        $result = acym_writeFile($customLayoutPath, $pluginCustomView);

        if ($result) {
            acym_sendAjaxResponse(acym_translation('ACYM_CUSTOM_VIEW_WELL_SAVED'));
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_CUSTOM_VIEW_SAVED_FAILED'), [], false);
        }
    }

    public function deleteCustomViewPlugin()
    {
        $plugin = $this->getPluginClassAjaxCustomView();

        $customLayoutPath = ACYM_CUSTOM_PLUGIN_LAYOUT.$plugin['folderName'].'.html';

        if (file_exists($customLayoutPath) && !acym_deleteFile($customLayoutPath)) {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_DELETE_CUSTOM_VIEW'), [], false);
        }

        $customView = '';
        acym_trigger('getStandardStructure', [&$customView], $plugin['className']);

        acym_sendAjaxResponse(acym_translation('ACYM_CUSTOM_VIEW_WELL_DELETED'), ['content' => $customView]);
    }
}
