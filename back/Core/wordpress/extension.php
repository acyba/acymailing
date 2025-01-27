<?php

use AcyMailing\Classes\PluginClass;

function acym_isExtensionActive($extension)
{
    // Test first as mu-plugins don't have an active status for WP
    if (acym_isMuPlugin($extension)) return true;

    if (function_exists('is_plugin_active')) return is_plugin_active($extension);

    return file_exists(WP_PLUGIN_DIR.DS.$extension);
}

function acym_isMuPlugin($extension)
{
    return file_exists(WPMU_PLUGIN_DIR.DS.$extension);
}

function acym_getPluginPath($plugin)
{
    $corePath = ACYM_BACK.'dynamics'.DS.$plugin.DS.'plugin.php';
    if (file_exists($corePath)) return $corePath;

    return ACYM_ADDONS_FOLDER_PATH.$plugin.DS.'plugin.php';
}

function acym_coreAddons()
{
    return [
        (object)[
            'title' => acym_translation('ACYM_ARTICLE'),
            'folder_name' => 'post',
            'version' => '{__VERSION__}',
            'active' => '1',
            'category' => 'Content management',
            'level' => 'starter',
            'uptodate' => '1',
            'description' => '- Insert WordPress posts in your emails<br/>- Insert the latest posts of a category in an automatic email',
            'latest_version' => '{__VERSION__}',
            'type' => 'CORE',
        ],
        (object)[
            'title' => acym_translation('ACYM_PAGE'),
            'folder_name' => 'page',
            'version' => '{__VERSION__}',
            'active' => '1',
            'category' => 'Content management',
            'level' => 'starter',
            'uptodate' => '1',
            'description' => '- Insert pages in your emails',
            'latest_version' => '{__VERSION__}',
            'type' => 'CORE',
        ],
        (object)[
            'title' => acym_translation('ACYM_CREATE_USER'),
            'folder_name' => 'createuser',
            'version' => '{__VERSION__}',
            'active' => '1',
            'category' => 'User management',
            'level' => 'starter',
            'uptodate' => '1',
            'description' => '- Automatically creates a site user when an AcyMailing subscriber is created',
            'latest_version' => '{__VERSION__}',
            'type' => 'CORE',
        ],
    ];
}

function acym_isTrackingSalesActive()
{
    $trackingWoocommerce = false;
    acym_trigger('onAcymIsTrackingWoocommerce', [&$trackingWoocommerce], 'plgAcymWoocommerce');

    return $trackingWoocommerce;
}

function acym_loadPlugins()
{
    $dynamicsLoadedLast = ['managetext'];
    $dynamics = acym_getFolders(ACYM_BACK.'dynamics');

    $pluginClass = new PluginClass();
    $plugins = $pluginClass->getPlugins();

    foreach ($dynamics as $key => $oneDynamic) {
        if (!empty($plugins[$oneDynamic]) && 0 === intval($plugins[$oneDynamic]->active)) {
            unset($dynamics[$key]);
        }

        if ('managetext' === $oneDynamic) {
            unset($dynamics[$key]);
        }
    }

    $pluginsLoadedLast = ['tableofcontents'];
    foreach ($plugins as $pluginFolder => $onePlugin) {
        if (in_array($pluginFolder, $dynamics) || 0 === intval($onePlugin->active)) {
            continue;
        }

        if (in_array($pluginFolder, $pluginsLoadedLast)) {
            array_unshift($dynamicsLoadedLast, $pluginFolder);
        } else {
            $dynamics[] = $pluginFolder;
        }
    }

    // Some plugins need to be called last
    $dynamics = array_merge($dynamics, $dynamicsLoadedLast);

    global $acymPlugins;
    global $acymAddonsForSettings;

    // Load the installed integrations
    $integrationsRaw = [];
    $acyVersion = acym_config()->get('version');
    do_action_ref_array('acym_load_installed_integrations', [&$integrationsRaw, $acyVersion]);

    $integrations = [];
    foreach ($integrationsRaw as $oneIntegration) {
        $addonName = strtolower(substr($oneIntegration['className'], 7));
        $integrations[$addonName] = $oneIntegration;

        if (!in_array($addonName, $dynamics)) {
            $dynamics[] = $addonName;
        }
    }
    $integrationsClasses = array_keys($integrations);

    foreach ($dynamics as $oneDynamic) {
        if (in_array($oneDynamic, $integrationsClasses)) {
            $dynamicFile = $integrations[$oneDynamic]['path'].DS.'plugin.php';
        } else {
            $dynamicFile = acym_getPluginPath($oneDynamic);
        }
        $className = 'plgAcym'.ucfirst($oneDynamic);

        // Load the plugin
        if (isset($acymPlugins[$className]) || !file_exists($dynamicFile) || (!class_exists($className) && !include_once $dynamicFile)) {
            continue;
        }

        if (!class_exists($className)) {
            continue;
        }

        $plugin = new $className();

        if (in_array($oneDynamic, $integrationsClasses)) {
            $pluginClass->addIntegrationIfMissing($plugin);
        }

        // If it's for another CMS or if the related extension isn't installed, skip it
        if (in_array($plugin->cms, ['all', '{__CMS__}'])) {
            $acymAddonsForSettings[$className] = $plugin;
        }

        if (!in_array($plugin->cms, ['all', '{__CMS__}']) || !$plugin->installed) {
            continue;
        }

        $acymPlugins[$className] = $plugin;
    }
}
