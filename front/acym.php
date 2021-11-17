<?php
if (version_compare(PHP_VERSION, '5.6.0', '<')) {
    echo '<p style="color:red">This version of AcyMailing requires at least PHP 5.6.0, it is time to update the PHP version of your server!</p>';
    exit;
}

$ds = DIRECTORY_SEPARATOR;
$helperFile = rtrim(JPATH_ADMINISTRATOR, $ds).$ds.'components'.$ds.'com_acym'.$ds.'helpers'.$ds.'helper.php';
if (!include_once $helperFile) {
    echo 'Could not load AcyMailing library';

    return;
}
acym_loadLanguage();

//Set the debug if the Joomla debug is ON
if (acym_isDebug()) acym_displayErrors();

global $Itemid;
if (empty($Itemid)) {
    $urlItemid = acym_getVar('int', 'Itemid');
    if (!empty($urlItemid)) {
        $Itemid = $urlItemid;
    }
}

$ctrl = acym_getVar('cmd', 'ctrl', acym_getVar('cmd', 'view', ''));
if (empty($ctrl)) {
    acym_raiseError(404, acym_translation('ACYM_PAGE_NOT_FOUND'));
}

$controllerNamespace = 'AcyMailing\\FrontControllers\\'.ucfirst($ctrl).'Controller';

if (!class_exists($controllerNamespace)) {
    //We redirect to the homepage...
    acym_redirect(acym_rootURI());

    return;
}

$controller = new $controllerNamespace;
if (empty($controller)) {
    //We redirect to the homepage...
    acym_redirect(acym_rootURI());

    return;
}
acym_setVar('ctrl', $ctrl);

$task = acym_getVar('cmd', 'task', acym_getVar('cmd', 'layout', ''));
if (empty($task)) {
    $task = acym_getVar('cmd', 'defaulttask', $controller->defaulttask);
}
acym_setVar('task', $task);
acym_setVar('layout', $task);

$controller->checkTaskFront($task);
