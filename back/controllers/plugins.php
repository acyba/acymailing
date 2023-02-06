<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\PluginClass;
use AcyMailing\Helpers\WorkflowHelper;
use AcyMailing\Libraries\acymController;

class PluginsController extends acymController
{
    var $tabs, $types, $level, $features, $errors;

    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_ADD_ONS')] = acym_completeLink('plugins');
        $this->loadScripts = [
            'available' => ['vue-applications' => ['available_plugins']],
            'installed' => ['datepicker', 'vue-prism-editor', 'vue-applications' => ['custom_view', 'installed_plugins']],
        ];

        $this->setDefaultTask('installed');

        $this->tabs = [
            'installed' => acym_translation('ACYM_MY_ADD_ONS'),
            'available' => acym_translation('ACYM_AVAILABLE_ADD_ONS'),
        ];

        $this->types = [
            '' => acym_translation('ACYM_ANY_CATEGORY'),
            'Files management' => acym_translation('ACYM_FILES_MANAGEMENT'),
            'E-commerce solutions' => acym_translation('ACYM_E_COMMERCE_SOLTIONS'),
            'Content management' => acym_translation('ACYM_CONTENT_MANAGEMENT'),
            'Subscription system' => acym_translation('ACYM_SUBSCRIPTION_SYSTEM'),
            'User management' => acym_translation('ACYM_USERS_MANAGEMENT'),
            'Events management' => acym_translation('ACYM_EVENTS_MANAGEMENT'),
            'Others' => acym_translation('ACYM_OTHER'),
        ];

        $this->level = [
            '' => acym_translation('ACYM_ACYMAILING_LEVEL'),
            'starter' => 'Starter',
            'essential' => 'Essential',
            'enterprise' => 'Enterprise',
        ];

        $this->features = [
            '' => acym_translation('ACYM_FEATURES'),
            'content' => acym_translation('ACYM_CONTENT'),
            'automation' => acym_translation('ACYM_AUTOMATION'),
        ];

        $this->errors = [
            'NOT_ALLOWED' => 'ACYM_NOT_ALLOWED_LEVEL',
            'MISSING_DOMAIN' => 'ACYM_MISSING_DOMAIN',
            'NOT_FOUND' => 'ACYM_ADD_ON_NOT_FOUND',
        ];
    }

    public function installed()
    {
        acym_setVar('layout', 'installed');

        $data = [];
        $data['tabs'] = $this->tabs;
        $data['tab'] = 'installed';
        $data['types'] = $this->types;
        $data['level'] = $this->level;
        $data['features'] = $this->features;
        $data['plugins'] = $this->getAllPlugins();
        $data['workflowHelper'] = new WorkflowHelper();

        parent::display($data);
    }

    public function available()
    {
        acym_setVar('layout', 'available');
        $data = [];
        $data['tabs'] = $this->tabs;
        $data['tab'] = 'available';
        $data['types'] = $this->types;
        $data['level'] = $this->level;
        $data['features'] = $this->features;
        $data['workflowHelper'] = new WorkflowHelper();

        parent::display($data);
    }

    private function handleError($error, $ajax)
    {
        if ($ajax) {
            acym_sendAjaxResponse(acym_translation($error), [], false);
        } else {
            return acym_translation($error);
        }
    }

    private function isLastestAcyMailingVersion()
    {
        $currentVersion = $this->config->get('version', '');
        $latestVersion = $this->config->get('latestversion', '');
        if (!version_compare($currentVersion, $latestVersion, '>=')) {
            acym_sendAjaxResponse(acym_translation('ACYM_NEED_LATEST_VERSION_TO_DOWNLOAD'), [], false);
        }
    }

    public function update()
    {
        $this->isLastestAcyMailingVersion();

        $plugin = acym_getVar('array', 'plugin');

        $pluginClass = new PluginClass();
        $id = $pluginClass->updateAddon($plugin['folder_name']);
        if (!empty($id)) {
            acym_sendAjaxResponse(acym_translation('ACYM_ADD_ON_SUCCESSFULLY_UPDATED'));
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_UPDATE_ADD_ON'), [], false);
        }
    }

    public function download($ajax = true, $pluginFromUpdate = '')
    {
        $plugin = [];

        $pluginClass = new PluginClass();
        if (empty($pluginFromUpdate)) {
            $this->isLastestAcyMailingVersion();
            $plugin = acym_getVar('array', 'plugin');
        } else {
            $urlDownload = ACYM_UPDATEMEURL.'integrationv6&task=getAllPlugin&cms='.ACYM_CMS;
            $plugins = acym_fileGetContent($urlDownload);
            $plugins = json_decode($plugins);
            if (!empty($plugins['error'])) $this->handleError('ACYM_ISSUE_WHILE_INSTALLING', $ajax);
            foreach ($plugins as $key => $onePlugin) {
                $folder = str_replace('.zip', '', $onePlugin->file_name);
                if ($pluginFromUpdate === $folder) {
                    $plugin = (array)$plugins[$key];
                    break;
                }
            }

            if (empty($plugin)) return '';
        }
        $plugin['file_name'] = str_replace('.zip', '', $plugin['file_name']);

        $pluginClass->downloadAddon($plugin['file_name'], $ajax);

        //We update the plugin info in DB
        $pluginToSave = new \stdClass();
        $pluginToSave->title = $plugin['name'];
        $pluginToSave->folder_name = $plugin['file_name'];
        $pluginToSave->version = $plugin['version'];
        $pluginToSave->latest_version = $plugin['version'];
        $pluginToSave->description = $plugin['description'];
        $pluginToSave->active = 1;
        $pluginToSave->category = $plugin['category'];
        $pluginToSave->level = $plugin['level'];
        $pluginToSave->uptodate = 1;
        $pluginToSave->features = json_encode($plugin['features']);
        $pluginToSave->id = $pluginClass->save($pluginToSave);

        if (!empty($pluginToSave->id)) {
            //we send the success message
            if ($ajax) {
                acym_sendAjaxResponse(acym_translation('ACYM_ADD_ON_SUCCESSFULLY_INSTALLED'));
            }
        } else {
            return $this->handleError('ACYM_ISSUE_WHILE_INSTALLING', $ajax);
        }
    }

    public function getAllPlugins()
    {
        $pluginClass = new PluginClass();
        $plugins = $pluginClass->getMatchingElements(['ordering' => 'title']);

        foreach ($plugins['elements'] as $key => $plugin) {
            if (!empty($plugin->settings)) {
                $plugins['elements'][$key]->settings = json_decode($plugin->settings, true);
            }
        }
        acym_trigger('onAcymAddSettings', [&$plugins['elements']]);

        return json_encode($plugins);
    }

    public function getAllPluginsAjax()
    {
        echo $this->getAllPlugins();
        exit;
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
        $plugin = $pluginClass->getOneByFolderName($pluginFolderName);

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
