<?php

use AcyMailing\Classes\PluginClass;
use Joomla\CMS\Component\ComponentHelper;

function acym_isExtensionActive(string $extension): bool
{
    // Keep both to handle old Joomla 3 versions
    return ComponentHelper::isInstalled($extension) && ComponentHelper::isEnabled($extension, true);
}

function acym_getPluginPath(string $plugin): string
{
    return ACYM_ADDONS_FOLDER_PATH.$plugin.DS.'plugin.php';
}

function acym_syncUpdateSite(?string $licenseKey = null): void
{
    $extensionId = acym_loadResult(
        'SELECT `extension_id`
        FROM #__extensions
        WHERE `element` = "pkg_acymailing"
            AND `type` = "package"'
    );

    if (empty($extensionId)) {
        return;
    }

    if (is_null($licenseKey)) {
        $config = acym_config();
        $licenseKey = $config->get('license_key', '');
    }
    $extraQuery = empty($licenseKey) ? '' : 'licenseKey='.urlencode($licenseKey);

    // Must be the same URL as in pkg_acymailing.xml
    $location = ACYM_UPDATEME_API_URL.'public/updatexml/component?extension=acymailing&cms=joomla&level={__LEVEL__}&type=package';
    $updateSiteId = acym_loadResult('SELECT `update_site_id` FROM #__update_sites WHERE `location` = '.acym_escapeDB($location));

    if (empty($updateSiteId)) {
        // First install, add it and Joomla will reuse it
        $updateSiteDefinition = new stdClass();
        $updateSiteDefinition->enabled = 1;
        $updateSiteDefinition->type = 'extension';
        $updateSiteDefinition->name = 'AcyMailing';
        $updateSiteDefinition->location = $location;
        $updateSiteDefinition->extra_query = $extraQuery;

        $updateSiteId = acym_insertObject('#__update_sites', $updateSiteDefinition);
        if (empty($updateSiteId)) {
            return;
        }
    } else {
        acym_query(
            'UPDATE #__update_sites
            SET `enabled` = 1, `extra_query` = '.acym_escapeDB($extraQuery).'
            WHERE `update_site_id` = '.intval($updateSiteId)
        );
    }

    acym_query(
        'INSERT IGNORE INTO #__update_sites_extensions (update_site_id, extension_id)
        VALUES ('.intval($updateSiteId).','.intval($extensionId).')'
    );

    $oldUpdateSites = acym_loadResultArray(
        'SELECT `update_site_id`
        FROM #__update_sites
        WHERE `location` LIKE '.acym_escapeDB('%updatexml/component%extension=acymailing%').'
            AND `update_site_id` != '.intval($updateSiteId)
    );

    if (empty($oldUpdateSites)) {
        return;
    }

    $oldUpdateSites = implode(',', array_map('intval', $oldUpdateSites));

    acym_query('DELETE FROM #__updates WHERE `update_site_id` IN ('.$oldUpdateSites.')');
    acym_query('DELETE FROM #__update_sites_extensions WHERE `update_site_id` IN ('.$oldUpdateSites.')');
    acym_query('DELETE FROM #__update_sites WHERE `update_site_id` IN ('.$oldUpdateSites.')');
}

function acym_coreAddons(): array
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
        (object)[
            'title' => acym_translation('ACYM_CONTACTS'),
            'folder_name' => 'contact',
            'version' => '{__VERSION__}',
            'active' => '1',
            'category' => 'User management',
            'level' => 'entreprise',
            'uptodate' => '1',
            'description' => '- Filter your AcyMailing users based on their contact category<br/>- Auto-create AcyMailing users on contact creation<br/>- Import contacts into AcyMailing',
            'latest_version' => '{__VERSION__}',
            'type' => 'CORE',
        ],
    ];
}

function acym_isTrackingSalesActive(): bool
{
    return false;
}

function acym_loadPlugins(): void
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
