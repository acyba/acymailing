<?php

function acym_addScript($raw, $script, $type = 'text/javascript', $defer = true, $async = false, $needTagScript = false, $deps = ['jquery'])
{
    $acyDocument = acym_getGlobal('doc');

    if ($raw) {
        $acyDocument->addScriptDeclaration($script, $type);
    } else {
        if (ACYM_J37) {
            $attributes = [];
            $attributes['type'] = $type;
            if ($defer) $attributes['defer'] = 'defer';
            if ($async) $attributes['async'] = 'async';
            $acyDocument->addScript($script, [], $attributes);
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
        if (ACYM_J37) {
            $attributes = [];
            $attributes['type'] = $type;
            if ($media) $attributes['media'] = $media;
            if (!empty($attribs)) {
                $attributes = array_merge($attributes, $attribs);
            }
            $acyDocument->addStyleSheet($style, [], $attributes);
        } else {
            $acyDocument->addStyleSheet($style, $type, $media, $attribs);
        }
    }
}

function acym_prepareFrontViewDisplay($ctrl, $task)
{
}

function acym_loadCmsScripts()
{
    $toggleController = acym_isAdmin() ? 'toggle' : 'fronttoggle';
    acym_addScript(
        true,
        '
        var ACYM_AJAX_URL = "'.(acym_isAdmin() ? '' : acym_rootURI()).'index.php?option='.ACYM_COMPONENT.'&'.acym_noTemplate().'&'.acym_getFormToken().'&nocache='.time().'";
        var ACYM_TOGGLE_URL = ACYM_AJAX_URL + "&ctrl='.$toggleController.'";
        var ACYM_JOOMLA_MEDIA_IMAGE = "'.ACYM_LIVE.'";
        var ACYM_JOOMLA_MEDIA_FOLDER = "'.addslashes(trim(JComponentHelper::getParams("com_media")->get('file_path', 'images'), '/').'/').'";
        var ACYM_IS_ADMIN = '.(acym_isAdmin() ? 'true' : 'false').';'
    );

    JHtml::_('jquery.framework');
    acym_addScript(false, ACYM_JS.'libraries/jquery-ui.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'jquery-ui.min.js'));
}

function acym_redirect($url, $msg = '', $msgType = 'message', $safe = false)
{
    $msg = acym_translation($msg);
    $acyapp = acym_getGlobal('app');

    if ($safe && !acym_checkRedirect($url)) {
        $url = acym_rootURI();
        acym_enqueueMessage(acym_translation('ACYM_REDIRECT_NOT_ALLOWED'), 'warning');
    }

    if (ACYM_J40) {
        if (!empty($msg)) {
            acym_enqueueMessage($msg, $msgType);
        }

        return $acyapp->redirect($url);
    } else {
        return $acyapp->redirect($url, $msg, $msgType);
    }
}

function acym_checkRedirect($redirectUrl)
{
    $config = acym_config();
    $allowedHosts = $config->get('allowed_hosts', '');
    $allowedHosts = str_replace(',', '|', $allowedHosts);
    $allowedHosts = str_replace(['https://', 'http://', 'www.'], '', $allowedHosts);

    //Check the redirect url..
    $regex = trim(preg_replace('#[^a-z0-9\|\.]#i', '', $allowedHosts), '|');
    if (empty($regex) || $regex == 'all' || empty($redirectUrl)) return true;

    preg_match('#^(https?://)?(www.)?([^/]*)#i', $redirectUrl, $resultsurl);
    $domainredirect = preg_replace('#[^a-z0-9\.]#i', '', @$resultsurl[3]);
    if (preg_match('#^'.$regex.'$#i', $domainredirect)) return true;

    return false;
}
