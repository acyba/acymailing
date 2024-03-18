<?php

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;

function acym_formToken()
{
    return HTMLHelper::_('form.token');
}

/**
 * Check token with all the possibilities
 */
function acym_checkToken()
{
    Session::checkToken() || Session::checkToken('get') || die('Invalid Token');
}

function acym_getFormToken()
{
    return Session::getFormToken().'=1';
}

function acym_noTemplate(): string
{
    return 'tmpl=component';
}

function acym_isNoTemplate(): bool
{
    $tmpl = acym_getVar('cmd', 'tmpl');

    return in_array($tmpl, ['component', 'raw']);
}

function acym_setNoTemplate($status = true)
{
    if ($status) {
        acym_setVar('tmpl', 'component');
    } else {
        acym_setVar('tmpl', '');
    }
}

/**
 * @param bool   $token
 * @param string $task
 * @param string $currentStep
 * @param string $currentCtrl
 * @param bool   $addPage
 */
function acym_formOptions(bool $token = true, string $task = '', string $currentStep = '', string $currentCtrl = '', bool $addPage = true)
{
    if (!empty($currentStep)) {
        echo '<input type="hidden" name="step" value="'.$currentStep.'"/>';
    }
    echo '<input type="hidden" name="nextstep" value=""/>';
    echo '<input type="hidden" name="option" value="'.ACYM_COMPONENT.'"/>';
    echo '<input type="hidden" name="task" value="'.$task.'"/>';
    echo '<input type="hidden" name="ctrl" value="'.(empty($currentCtrl) ? acym_getVar('cmd', 'ctrl', '') : $currentCtrl).'"/>';
    if ($token) {
        echo acym_formToken();
    }
    echo '<button type="submit" class="is-hidden" id="formSubmit"></button>';
}

function acym_addMetadata($meta, $data, $name = 'name')
{
    $acyDocument = acym_getGlobal('doc');
    $acyDocument->setMetaData($meta, $data, $name);
}

function acym_includeHeaders()
{
}

function acym_getOptionRegacyPosition()
{
    return [
        acym_selectOption('email', 'ACYM_EMAIL'),
        acym_selectOption('password', 'ACYM_SMTP_PASSWORD'),
        acym_selectOption('custom', 'ACYM_CUSTOM_FIELD'),
    ];
}
