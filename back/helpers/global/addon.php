<?php

use AcyMailing\Classes\PluginClass;

global $acymPlugins;
global $acymAddonsForSettings;

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
    $pluginClass = new PluginClass();
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
            if (str_replace('.zip', '', $pluginAvailable['file_name']) == $pluginInstalled->folder_name && !version_compare(
                    $pluginInstalled->version,
                    $pluginAvailable['version'],
                    '>='
                )) {
                $pluginsInstalled[$key]->uptodate = 0;
                $pluginsInstalled[$key]->latest_version = $pluginAvailable['version'];
                $pluginClass->save($pluginsInstalled[$key]);
            }
        }
    }

    return true;
}
