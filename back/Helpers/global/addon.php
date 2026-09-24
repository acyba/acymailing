<?php
// context verification

use AcyMailing\Classes\PluginClass;

global $acymPlugins;
global $acymAddonsForSettings;

function acym_trigger(string $method, array $args = [], ?string $plugin = null, ?callable $callbackOnePlugin = null): ?array
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
            if (isset($value)) {
                $result[] = $value;
            }

            if (!empty($onePlugin->errors)) {
                $onePlugin->errorCallback();
            }
        } catch (\Throwable $e) {
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

/**
 * Displays the option fields of a trigger / filter / condition / action declared by an add-on
 */
function acym_displayAddonOption(object $declaration, ?string $fieldName = null): void
{
    if (isset($declaration->displayOptions) && is_callable($declaration->displayOptions)) {
        ($declaration->displayOptions)($fieldName);

        return;
    }

    if (empty($declaration->option)) {
        return;
    }

    // TODO: remove this on version 12, only there for retro-compat of add-ons/plugins not updated along with AcyMailing
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Add-on that still declares its option as a string, escaped on its side. Migrate it to declareTrigger().
    echo preg_replace_callback(ACYM_REGEX_SWITCHES, 'acym_prefixSwitchIds', $declaration->option);
}

function acym_getAddonOption(object $declaration, ?string $fieldName = null): string
{
    ob_start();
    try {
        acym_displayAddonOption($declaration, $fieldName);
    } finally {
        $html = ob_get_clean();
    }

    return $html === false ? '' : $html;
}

/**
 * Makes the ids of a switch unique per row of a filter/condition form.
 *
 * TODO: remove this on version 12, along with the string options
 */
function acym_prefixSwitchIds(array $matches): string
{
    return '__numand__'.$matches[1].$matches[2].'__numand__'.$matches[3].'__numand__'.$matches[4].'__numand__'.$matches[5];
}
