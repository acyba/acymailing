<?php

use AcyMailing\Classes\PluginClass;

global $acymPlugins;
global $acymAddonsForSettings;

function acym_trigger($method, $args = [], $plugin = null, $callbackOnePlugin = null)
{
    // On WordPress we load the addons before the tables are created on installation
    if (!in_array(acym_getPrefix().'acym_configuration', acym_getTableList())) {
        return null;
    }

    // Handle multilingual
    if (in_array($method, ['replaceContent', 'replaceUserInformation']) && !empty($args[0]->language)) {
        $previousLanguage = acym_setLanguage($args[0]->language);
        acym_loadLanguage($args[0]->language);
    }

    global $acymPlugins;
    global $acymAddonsForSettings;
    if (empty($acymPlugins)) {
        acym_loadPlugins();
    }

    $result = [];
    $listAddons = $acymPlugins;
    if ($method === 'onAcymAddSettings') {
        $listAddons = $acymAddonsForSettings;
    }

    foreach ($listAddons as $class => $onePlugin) {
        if (is_callable($callbackOnePlugin)) $callbackOnePlugin($onePlugin);
        if (!method_exists($onePlugin, $method)) continue;
        if (!empty($plugin) && $class !== $plugin) continue;

        // There may be an error here, but I don't know how to handle it. At least don't block the execution
        try {
            $value = call_user_func_array([$onePlugin, $method], $args);
            if (isset($value)) $result[] = $value;
            if (!empty($onePlugin->errors)) {
                $onePlugin->errorCallback();
            }
        } catch (Exception $e) {
            acym_logError('An error occurred when triggering the method '.$method.': '.$e->getMessage());
        }
    }

    if (!empty($previousLanguage)) {
        acym_setLanguage($previousLanguage);
        acym_loadLanguage($previousLanguage);
    }

    return $result;
}

function acym_checkPluginsVersion(): bool
{
    $pluginClass = new PluginClass();
    $pluginsInstalled = $pluginClass->getMatchingElements();
    $pluginsInstalled = $pluginsInstalled['elements'];

    if (empty($pluginsInstalled)) {
        return true;
    }

    $response = acym_fileGetContent(ACYM_UPDATEME_API_URL.'public/addons');
    $pluginsAvailable = @json_decode($response, true);
    if (empty($pluginsAvailable)) {
        return true;
    }

    foreach ($pluginsInstalled as $key => $pluginInstalled) {
        foreach ($pluginsAvailable as $pluginAvailable) {
            if ($pluginAvailable['file_name'] === $pluginInstalled->folder_name && !version_compare($pluginInstalled->version, $pluginAvailable['version'], '>=')) {
                $pluginInstalled->uptodate = 0;
                $pluginInstalled->latest_version = $pluginAvailable['version'];
                $pluginClass->save($pluginInstalled);
            }
        }
    }

    return true;
}
