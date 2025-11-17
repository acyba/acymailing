<?php

namespace AcyMailing\Controllers\Plugins;

use AcyMailing\Classes\PluginClass;
use AcyMailing\Helpers\WorkflowHelper;

trait Available
{
    public function available(): void
    {
        acym_setVar('layout', 'available');
        $data = [];
        $data['tabs'] = $this->tabs;
        $data['tab'] = 'available';
        $data['types'] = $this->types;
        $data['level'] = $this->level;
        $data['workflowHelper'] = new WorkflowHelper();

        parent::display($data);
    }

    public function download(bool $ajax = true, string $pluginFromUpdate = ''): string
    {
        $plugin = [];

        if (empty($pluginFromUpdate)) {
            $this->isLatestAcyMailingVersion();
            $plugin = acym_getVar('array', 'plugin');
        } else {
            $plugins = acym_fileGetContent(ACYM_UPDATEME_API_URL.'public/addons');
            $plugins = @json_decode($plugins, true);
            if (empty($plugins)) {
                $this->handleError('ACYM_ISSUE_WHILE_INSTALLING', $ajax);
            }

            foreach ($plugins as $onePlugin) {
                if ($pluginFromUpdate === $onePlugin['file_name']) {
                    $plugin = $onePlugin;
                    break;
                }
            }

            if (empty($plugin)) {
                return acym_translation('ACYM_ADD_ON_NOT_FOUND');
            }
        }

        $pluginClass = new PluginClass();
        $errorMessage = $pluginClass->downloadAddon($plugin['file_name'], $ajax);
        if (!empty($errorMessage)) {
            return $errorMessage;
        }

        // We update the plugin info in DB
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
        $pluginToSave->id = $pluginClass->save($pluginToSave);

        if (!empty($pluginToSave->id)) {
            if ($ajax) {
                acym_sendAjaxResponse(acym_translation('ACYM_ADD_ON_SUCCESSFULLY_INSTALLED'));
            }

            return '';
        }

        return $this->handleError('ACYM_ISSUE_WHILE_INSTALLING', $ajax);
    }

    public function getAllPluginsAjax(): void
    {
        acym_sendAjaxResponse('', $this->getAllPlugins());
    }

    public function getAllPlugins(): array
    {
        $pluginClass = new PluginClass();
        $plugins = $pluginClass->getMatchingElements(['ordering' => 'title']);

        foreach ($plugins['elements'] as $key => $plugin) {
            if (!empty($plugin->settings)) {
                $plugins['elements'][$key]->settings = json_decode($plugin->settings, true);
            }
        }
        acym_trigger('onAcymAddSettings', [&$plugins['elements']]);

        return $plugins['elements'];
    }

    private function handleError(string $error, bool $ajax): string
    {
        if ($ajax) {
            acym_sendAjaxResponse(acym_translation($error), [], false);
        }

        return acym_translation($error);
    }
}
