<?php

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;

function acym_addScript(bool $raw, string $script, array $params = []): string
{
    $acyDocument = acym_getGlobal('doc');

    if ($raw) {
        $acyDocument->addScriptDeclaration($script, 'text/javascript');
    } else {
        if (!isset($params['defer'])) {
            $params['defer'] = true;
        }

        if (ACYM_J37) {
            $attributes = [];
            $attributes['type'] = 'text/javascript';
            if (!empty($params['defer'])) {
                $attributes['defer'] = 'defer';
            }
            if (!empty($params['async'])) {
                $attributes['async'] = 'async';
            }
            $acyDocument->addScript($script, [], $attributes);
        } else {
            $acyDocument->addScript($script, 'text/javascript', !empty($params['defer']), !empty($params['async']));
        }
    }

    return 'acym_script';
}

function acym_addStyle(bool $raw, string $style): void
{
    $acyDocument = acym_getGlobal('doc');

    if ($raw) {
        $acyDocument->addStyleDeclaration($style, 'text/css');
    } else {
        if (ACYM_J37) {
            $acyDocument->addStyleSheet($style, [], ['type' => 'text/css']);
        } else {
            $acyDocument->addStyleSheet($style, 'text/css', null, []);
        }
    }
}

function acym_loadCmsScripts(): void
{
    $toggleController = acym_isAdmin() ? 'toggle' : 'fronttoggle';
    if (acym_isAdmin()) {
        $ajaxUrl = 'index.php?option='.ACYM_COMPONENT.'&'.acym_noTemplate().'&'.acym_getFormToken().'&nocache='.time();
    } else {
        $ajaxUrl = acym_rootURI().'index.php?option='.ACYM_COMPONENT.'&'.acym_noTemplate().'&'.acym_getFormToken().'&nocache='.time();
        $currentMenu = acym_getMenu();
        if (!empty($currentMenu->id)) {
            $ajaxUrl .= '&Itemid='.$currentMenu->id;
        }
    }
    acym_addScript(
        true,
        '
        var ACYM_AJAX_URL = "'.$ajaxUrl.'";
        var ACYM_TOGGLE_URL = ACYM_AJAX_URL + "&ctrl='.$toggleController.'";
        var ACYM_JOOMLA_MEDIA_IMAGE = "'.ACYM_LIVE.'";
        var ACYM_JOOMLA_MEDIA_FOLDER = "'.addslashes(trim(ComponentHelper::getParams("com_media")->get('file_path', 'images'), '/').'/').'";
        var ACYM_JOOMLA_MEDIA_FOLDER_IMAGES = "'.addslashes(trim(ComponentHelper::getParams("com_media")->get('image_path', 'images'), '/').'/').'";
        var ACYM_IS_ADMIN = '.(acym_isAdmin() ? 'true' : 'false').';'
    );

    HTMLHelper::_('jquery.framework');
    acym_addScript(false, ACYM_JS.'libraries/jquery-ui.min.js?v='.filemtime(ACYM_MEDIA.'js'.DS.'libraries'.DS.'jquery-ui.min.js'));
}

function acym_redirect(string $url, string $msg = '', string $msgType = 'message', bool $safe = false): void
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

        $acyapp->redirect($url);
    } else {
        $acyapp->redirect($url, $msg, $msgType);
    }
}

function acym_checkRedirect(string $redirectUrl): bool
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
