<?php
if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    echo '<p style="color:red">This version of AcyMailing requires at least PHP 5.4.0, it is time to update the PHP version of your server!</p>';
    exit;
}

$helperFile = rtrim(JPATH_ADMINISTRATOR, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_acym'.DIRECTORY_SEPARATOR.'helpers'.DIRECTORY_SEPARATOR.'helper.php';
if (!include_once $helperFile) {
    echo 'Could not load AcyMailing helper file';

    return;
}

//Set the debug if the Joomla debug is ON
if (acym_isDebug()) acym_displayErrors();

$ctrl = acym_getVar('cmd', 'ctrl', 'dashboard');
$task = acym_getVar('cmd', 'task');

$config = acym_config();

$needToMigrate = $config->get('migration') == 0 && acym_existsAcyMailing59() && acym_getVar('string', 'task') != 'migrationDone';

if ((($needToMigrate || $config->get('walk_through') == 1) && !acym_isNoTemplate()) && $ctrl != 'dynamics') {
    $ctrl = 'dashboard';
}

if (!include_once ACYM_CONTROLLER.$ctrl.'.php') {
    //We redirect to the dashboard...
    acym_redirect(acym_completeLink('dashboard'));

    return;
}

$className = ucfirst($ctrl).'Controller';
$controller = new $className();

if (empty($task)) {
    $task = acym_getVar('cmd', 'defaulttask', $controller->defaulttask);
    acym_setVar('task', $task);
}

if (file_exists(ACYM_BACK.'extensions')) {
    $updateHelper = acym_get('helper.update');
    $updateHelper->installExtensions(false);
}

$controller->$task();
