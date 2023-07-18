<?php

use AcyMailing\Classes\PluginClass;

function acym_isExtensionActive($extension)
{
    // Keep both to handle old Joomla 3 versions
    return JComponentHelper::isInstalled($extension) && JComponentHelper::isEnabled($extension, true);
}

function acym_getPluginsPath($file, $dir)
{
    return rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS;
}

function acym_getPluginPath($plugin)
{
    return ACYM_ADDONS_FOLDER_PATH.$plugin.DS.'plugin.php';
}

function acym_coreAddons()
{
    acym_loadLanguageFile('com_modules', JPATH_ADMINISTRATOR);

    return [
        (object)[
            'title' => acym_translation('ACYM_ARTICLE'),
            'folder_name' => 'article',
            'version' => '{__VERSION__}',
            'active' => '1',
            'category' => 'Content management',
            'level' => 'starter',
            'uptodate' => '1',
            'features' => '["content"]',
            'description' => '- Insert Joomla articles in your emails<br/>- Insert the latest articles of a category in an automatic email',
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
            'features' => '[]',
            'description' => '- Automatically creates a site user when an AcyMailing subscriber is created',
            'latest_version' => '{__VERSION__}',
            'type' => 'CORE',
        ],
        (object)[
            'title' => acym_translation('COM_MODULES_MODULE'),
            'folder_name' => 'module',
            'version' => '{__VERSION__}',
            'active' => '1',
            'category' => 'Content management',
            'level' => 'starter',
            'uptodate' => '1',
            'features' => '[]',
            'description' => '- Insert Joomla modules in your emails',
            'latest_version' => '{__VERSION__}',
            'type' => 'CORE',
        ],
    ];
}

function acym_isTrackingSalesActive()
{
    return false;
}

function acym_loadPlugins()
{
    $dynamicsLoadedLast = ['managetext'];
    $dynamics = acym_getFolders(ACYM_BACK.'dynamics');

    $pluginClass = new PluginClass();
    $plugins = $pluginClass->getAll('folder_name');

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
    foreach ($dynamics as $oneDynamic) {
        $dynamicFile = acym_getPluginPath($oneDynamic);
        $className = 'plgAcym'.ucfirst($oneDynamic);

        // Load the plugin
        if (isset($acymPlugins[$className]) || !file_exists($dynamicFile) || !include_once $dynamicFile) {
            continue;
        }

        if (!class_exists($className)) {
            continue;
        }

        // If it's for another CMS or if the related extension isn't installed, skip it
        $plugin = new $className();
        if (in_array($plugin->cms, ['all', '{__CMS__}'])) {
            $acymAddonsForSettings[$className] = $plugin;
        }

        if (!in_array($plugin->cms, ['all', '{__CMS__}']) || !$plugin->installed) {
            continue;
        }

        $acymPlugins[$className] = $plugin;
    }
}
