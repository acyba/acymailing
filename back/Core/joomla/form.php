<?php

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;

function acym_formToken(): void
{
    echo HTMLHelper::_('form.token');
}

/**
 * Check token with all the possibilities
 */
function acym_checkToken(): void
{
    Session::checkToken() || Session::checkToken('get') || die('Invalid Token');
}

function acym_getFormToken(): string
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

function acym_setNoTemplate(bool $status = true): void
{
    if ($status) {
        acym_setVar('tmpl', 'component');
    } else {
        acym_setVar('tmpl', '');
    }
}

function acym_formOptions(bool $token = true, string $task = '', string $currentStep = '', string $currentCtrl = '', bool $addPage = true): void
{
    if (!empty($currentStep)) {
        echo '<input type="hidden" name="step" value="'.acym_escape($currentStep).'"/>';
    }
    echo '<input type="hidden" name="nextstep" value=""/>';
    echo '<input type="hidden" name="option" value="'.acym_escape(ACYM_COMPONENT).'"/>';
    echo '<input type="hidden" name="task" value="'.acym_escape($task).'"/>';
    echo '<input type="hidden" name="ctrl" value="'.acym_escape(empty($currentCtrl) ? acym_getVar('cmd', 'ctrl', '') : $currentCtrl).'"/>';
    if ($token) {
        acym_formToken();
    }
    echo '<button type="submit" class="is-hidden" id="formSubmit"></button>';
}

function acym_addMetadata(string $meta, string $data, string $name = 'name'): void
{
    $acyDocument = acym_getGlobal('doc');
    $acyDocument->setMetaData($meta, $data, $name);
}

function acym_includeHeaders(): void
{
}

function acym_getOptionRegacyPosition(): array
{
    return [
        acym_selectOption('email', 'ACYM_EMAIL'),
        acym_selectOption('password', 'ACYM_SMTP_PASSWORD'),
        acym_selectOption('custom', 'ACYM_CUSTOM_FIELD'),
    ];
}

function acym_checked(bool $checked, bool $current = true, bool $display = true): string
{
    $result = '';
    if ($checked) {
        $result = 'checked="checked"';
    }

    if ($display) {
        echo $result;
    }

    return $result;
}

function acym_selected(bool $selected, bool $current = true, bool $display = true): string
{
    $result = '';
    if ($selected) {
        $result = ' selected="selected"';
    }

    if ($display) {
        echo $result;
    }

    return $result;
}

function acym_disabled(bool $disabled, bool $current = true, bool $display = true): string
{
    $result = '';
    if ($disabled) {
        $result = ' disabled="disabled"';
    }

    if ($display) {
        echo $result;
    }

    return $result;
}
