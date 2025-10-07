<?php

use Joomla\CMS\Factory;

if (version_compare(PHP_VERSION, '7.4.0', '<')) {
    $app = Factory::getApplication();
    $app->enqueueMessage('This version of AcyMailing requires at least PHP 7.4.0, it is time to update the PHP version of your server!', 'error');
} else {
    $ds = DIRECTORY_SEPARATOR;
    $helperFile = rtrim(JPATH_ADMINISTRATOR, $ds).$ds.'components'.$ds.'com_acym'.$ds.'Core'.$ds.'init.php';
    if (!include_once $helperFile) {
        $app = Factory::getApplication();
        $app->enqueueMessage('Could not load AcyMailing helper file', 'error');

        return;
    }

    //Set the debug if the Joomla debug is ON
    if (acym_isDebug()) acym_displayErrors();

    $ctrl = acym_getVar('cmd', 'ctrl', 'dashboard');
    $task = acym_getVar('cmd', 'task');

    $config = acym_config();

    displayFreeTrialMessage();

    if (file_exists(ACYM_NEW_FEATURES_SPLASHSCREEN_JSON) && is_writable(ACYM_NEW_FEATURES_SPLASHSCREEN_JSON)) {
        $ctrl = 'dashboard';
        $task = 'features';
        acym_setVar('ctrl', $ctrl);
        acym_setVar('task', $task);
    }

    $needToMigrate = $config->get('migration') == 0 && acym_existsAcyMailing59() && acym_getVar('string', 'task') != 'migrationDone';
    $forceDashboard = ($needToMigrate || $config->get('walk_through', 0) == 1) && !acym_isNoTemplate() && $ctrl !== 'dynamics';
    if ($forceDashboard) {
        $ctrl = 'dashboard';
        acym_setVar('ctrl', $ctrl);
    }

    $controllerNamespace = 'AcyMailing\\Controllers\\'.ucfirst($ctrl).'Controller';

    if (!class_exists($controllerNamespace)) {
        acym_raiseError(404, acym_translation('ACYM_PAGE_NOT_FOUND').': '.$ctrl);
    }

    $controller = new $controllerNamespace();
    if (empty($controller)) {
        //We redirect to the dashboard...
        acym_redirect(acym_completeLink('dashboard'));

        return;
    }

    if (acym_level(ACYM_ENTERPRISE) && $ctrl === 'override' && !acym_isPluginActive('acymailoverride')) {
        $notification = [
            'name' => 'overrides_requirement',
            'removable' => 1,
        ];
        acym_enqueueMessage(acym_translation('ACYM_OVERRIDES_REQUIREMENT'), 'warning', true, [$notification]);
    }

    if (empty($task)) {
        $task = acym_getVar('cmd', 'defaulttask', $controller->defaulttask);
        acym_setVar('task', $task);
    }

    if ($forceDashboard && !method_exists($controller, $task)) {
        $task = 'listing';
        acym_setVar('task', $task);
    }

    $controller->call($task);
}
