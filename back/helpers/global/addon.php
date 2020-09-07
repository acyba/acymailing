<?php

global $acymPlugins;
global $acymAddonsForSettings;
function acym_loadPlugins()
{
    $dynamics = acym_getFolders(ACYM_BACK.'dynamics');

    $pluginClass = acym_get('class.plugin');
    $plugins = $pluginClass->getAll('folder_name');

    foreach ($dynamics as $key => $oneDynamic) {
        if (!empty($plugins[$oneDynamic]) && '0' === $plugins[$oneDynamic]->active) unset($dynamics[$key]);
        if ('managetext' === $oneDynamic) unset($dynamics[$key]);
    }

    foreach ($plugins as $pluginFolder => $onePlugin) {
        if (in_array($pluginFolder, $dynamics) || '0' === $onePlugin->active) continue;
        $dynamics[] = $pluginFolder;
    }

    // Make sure the Manage text plugin is called last, we'll clean the inserted content in it
    $dynamics[] = 'managetext';

    global $acymPlugins;
    global $acymAddonsForSettings;
    foreach ($dynamics as $oneDynamic) {
        $dynamicFile = acym_getPluginPath($oneDynamic);
        $className = 'plgAcym'.ucfirst($oneDynamic);

        // Load the plugin
        if (isset($acymPlugins[$className]) || !file_exists($dynamicFile) || !include_once $dynamicFile) continue;
        if (!class_exists($className)) continue;

        // If it's for another CMS or if the related extension isn't installed, skip it
        $plugin = new $className();
        if (in_array($plugin->cms, ['all', '{__CMS__}'])) $acymAddonsForSettings[$className] = $plugin;
        if (!in_array($plugin->cms, ['all', '{__CMS__}']) || !$plugin->installed) continue;

        $acymPlugins[$className] = $plugin;
    }
}

function acym_trigger($method, $args = [], $plugin = null)
{
    // On WordPress we load the addons before the tables are created on installation
    if (!in_array(acym_getPrefix().'acym_configuration', acym_getTableList())) return null;

    global $acymPlugins;
    global $acymAddonsForSettings;
    if (empty($acymPlugins)) acym_loadPlugins();

    $result = [];
    $listAddons = $acymPlugins;
    if ($method == 'onAcymAddSettings') $listAddons = $acymAddonsForSettings;
    foreach ($listAddons as $class => $onePlugin) {
        if (!method_exists($onePlugin, $method)) continue;
        if (!empty($plugin) && $class != $plugin) continue;

        // There may be an error here, but I don't know how to handle it. At least don't block the execution
        try {
            $value = call_user_func_array([$onePlugin, $method], $args);
            if (isset($value)) $result[] = $value;
        } catch (Exception $e) {

        }
    }

    return $result;
}

function acym_checkPluginsVersion()
{
    //first we get all installed plugins
    $pluginClass = acym_get('class.plugin');
    $pluginsInstalled = $pluginClass->getMatchingElements();
    $pluginsInstalled = $pluginsInstalled['elements'];
    //if we don't have any no need to go further
    if (empty($pluginsInstalled)) return true;

    //we get all plugin available from our website
    $url = ACYM_UPDATEMEURL.'integrationv6&task=getAllPlugin&cms='.ACYM_CMS;

    $res = acym_fileGetContent($url);
    $pluginsAvailable = json_decode($res, true);

    foreach ($pluginsInstalled as $key => $pluginInstalled) {
        foreach ($pluginsAvailable as $pluginAvailable) {
            if (str_replace('.zip', '', $pluginAvailable['file_name']) == $pluginInstalled->folder_name && !version_compare($pluginInstalled->version, $pluginAvailable['version'], '>=')) {
                $pluginsInstalled[$key]->uptodate = 0;
                $pluginsInstalled[$key]->latest_version = $pluginAvailable['version'];
                $pluginClass->save($pluginsInstalled[$key]);
            }
        }
    }

    return true;
}
