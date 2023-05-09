<?php

namespace AcyMailing\Controllers\Plugins;

use AcyMailing\Classes\PluginClass;
use AcyMailing\Helpers\WorkflowHelper;

trait Available
{
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

    public function download($ajax = true, $pluginFromUpdate = '')
    {
        $plugin = [];

        $pluginClass = new PluginClass();
        if (empty($pluginFromUpdate)) {
            $this->isLatestAcyMailingVersion();
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
}
