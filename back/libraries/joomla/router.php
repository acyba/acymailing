<?php

function acym_addScript($raw, $script, $type = 'text/javascript', $defer = true, $async = false, $needTagScript = false, $deps = ['jquery'])
{
    $acyDocument = acym_getGlobal('doc');

    if ($raw) {
        $acyDocument->addScriptDeclaration($script, $type);
    } else {
        if (ACYM_J40) {
            $acyDocument->addScript($script, [], ['defer' => $defer, 'async' => $async, 'type' => $type]);
        } else {
            $acyDocument->addScript($script, $type, $defer, $async);
        }
    }

    return 'acym_script';
}

function acym_addStyle($raw, $style, $type = 'text/css', $media = null, $attribs = [])
{
    $acyDocument = acym_getGlobal('doc');

    if ($raw) {
        $acyDocument->addStyleDeclaration($style, $type);
    } else {
        $acyDocument->addStyleSheet($style, $type, $media, $attribs);
    }
}

function acym_prepareFrontViewDisplay($ctrl)
{
}

function acym_loadCmsScripts()
{
    $toggleController = acym_isAdmin() ? 'toggle' : 'fronttoggle';
    acym_addScript(
        true,
        'var ACYM_TOGGLE_URL = "'.(acym_isAdmin() ? '' : acym_rootURI()).'index.php?option='.ACYM_COMPONENT.'&'.acym_noTemplate().'&ctrl='.$toggleController.'&'.acym_getFormToken(
        ).'";
        var ACYM_AJAX_URL = "'.(acym_isAdmin() ? '' : acym_rootURI()).'index.php?option='.ACYM_COMPONENT.'&'.acym_noTemplate().'&'.acym_getFormToken().'";
        var ACYM_JOOMLA_MEDIA_IMAGE = "'.ACYM_LIVE.'";
        var ACYM_JOOMLA_MEDIA_FOLDER = "'.addslashes(trim(JComponentHelper::getParams("com_media")->get('file_path', 'images'), '/').'/').'";
        var ACYM_IS_ADMIN = '.(acym_isAdmin() ? 'true' : 'false').';'
    );

    JHtml::_('jquery.framework');
    acym_addScript(false, 'https://code.jquery.com/ui/1.12.1/jquery-ui.min.js');
}

function acym_redirect($url, $msg = '', $msgType = 'message')
{
    $msg = acym_translation($msg);
    $acyapp = acym_getGlobal('app');

    if (ACYM_J40) {
        if (!empty($msg)) {
            acym_enqueueMessage($msg, $msgType);
        }

        return $acyapp->redirect($url);
    } else {
        return $acyapp->redirect($url, $msg, $msgType);
    }
}
