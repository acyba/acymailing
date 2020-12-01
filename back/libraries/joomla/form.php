<?php

function acym_formToken()
{
    return JHTML::_('form.token');
}

/**
 * Check token with all the possibilities
 */
function acym_checkToken()
{
    if (ACYM_J40) {
        \JSession::checkToken() || \JSession::checkToken('get') || die('Invalid Token');
    } else {
        if (!JRequest::checkToken() && !JRequest::checkToken('get')) {
            JSession::checkToken() || JSession::checkToken('get') || die('Invalid Token');
        }
    }
}

function acym_getFormToken()
{
    if (ACYM_J30) {
        return JSession::getFormToken().'=1';
    }

    return JUtility::getToken().'=1';
}

function acym_noTemplate($component = true)
{
    return 'tmpl='.($component || ACYM_J40 ? 'component' : 'raw');
}

function acym_isNoTemplate()
{
    return acym_getVar('cmd', 'tmpl') == 'component';
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
 */
function acym_formOptions($token = true, $task = '', $currentStep = null, $currentCtrl = '')
{
    if (!empty($currentStep)) {
        echo '<input type="hidden" name="step" value="'.$currentStep.'"/>';
        echo '<input type="hidden" name="nextstep" value=""/>';
    }
    echo '<input type="hidden" name="option" value="'.ACYM_COMPONENT.'"/>';
    echo '<input type="hidden" name="nextstep" value=""/>';
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
