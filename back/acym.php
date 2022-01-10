<?php

use AcyMailing\Helpers\UpdateHelper;

if (version_compare(PHP_VERSION, '5.6.0', '<')) {
    echo '<p style="color:red">This version of AcyMailing requires at least PHP 5.6.0, it is time to update the PHP version of your server!</p>';
    exit;
}

$helperFile = rtrim(
        JPATH_ADMINISTRATOR,
        DIRECTORY_SEPARATOR
    ).DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_acym'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
if (!include_once $helperFile) {
    echo 'Could not load AcyMailing helper file';

    return;
}

//Set the debug if the Joomla debug is ON
if (acym_isDebug()) acym_displayErrors();

$ctrl = acym_getVar('cmd', 'ctrl', 'dashboard');
$task = acym_getVar('cmd', 'task');

$config = acym_config();

if (file_exists(ACYM_NEW_FEATURES_SPLASHSCREEN) && is_writable(ACYM_NEW_FEATURES_SPLASHSCREEN)) {
    $ctrl = 'dashboard';
    $task = 'features';
    acym_setVar('ctrl', $ctrl);
    acym_setVar('task', $task);
}

$needToMigrate = $config->get('migration') == 0 && acym_existsAcyMailing59() && acym_getVar('string', 'task') != 'migrationDone';
$forceDashboard = ($needToMigrate || $config->get('walk_through') == 1) && !acym_isNoTemplate() && $ctrl !== 'dynamics';
if ($forceDashboard) {
    $ctrl = 'dashboard';
    acym_setVar('ctrl', $ctrl);
}

$controllerNamespace = 'AcyMailing\\Controllers\\'.ucfirst($ctrl).'Controller';

if (!class_exists($controllerNamespace)) return acym_raiseError(404, acym_translation('ACYM_PAGE_NOT_FOUND').': '.$ctrl);

$controller = new $controllerNamespace;
if (empty($controller)) {
    //We redirect to the dashboard...
    acym_redirect(acym_completeLink('dashboard'));

    return;
}

if ($ctrl === 'override' && !acym_isPluginActive('acymailoverride')) {
    acym_enqueueMessage(acym_translation('ACYM_OVERRIDES_REQUIREMENT'), 'warning');
}

if (empty($task)) {
    $task = acym_getVar('cmd', 'defaulttask', $controller->defaulttask);
    acym_setVar('task', $task);
}

if (file_exists(ACYM_BACK.'extensions')) {
    $updateHelper = new UpdateHelper();
    $updateHelper->installExtensions(false);
}

if ($forceDashboard && !method_exists($controller, $task)) {
    $task = 'listing';
    acym_setVar('task', $task);
}

$controller->call($task);
