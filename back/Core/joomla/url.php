<?php

use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;

function acym_route($url, $xhtml = true, $ssl = null)
{
    if (ACYM_J40) {
        global $Itemid;
        if (!acym_isAdmin() && !empty($Itemid) && strpos($url, 'Itemid') === false) {
            $url .= (strpos($url, '?') ? '&' : '?').'Itemid='.$Itemid;
        }
    }

    return Route::_($url, $xhtml, $ssl === null ? 0 : $ssl);
}

function acym_baseURI($pathonly = false)
{
    return Uri::base($pathonly);
}

function acym_completeLink($link, $popup = false, $redirect = false, $forceNoPopup = false)
{
    if (($popup || acym_isNoTemplate()) && $forceNoPopup == false) {
        $link .= '&'.acym_noTemplate();
    }

    return acym_route('index.php?option='.ACYM_COMPONENT.'&ctrl='.$link, !$redirect);
}

function acym_prepareAjaxURL($url)
{
    return htmlspecialchars_decode(acym_completeLink($url, true));
}

function acym_frontendLink(string $link, bool $complete = true, bool $sef = true)
{
    if ($complete) {
        $link = 'index.php?option='.ACYM_COMPONENT.'&ctrl='.$link;
    }

    if ($sef && ACYM_J39 && strpos($link, 'ctrl=cron') === false && strpos($link, 'ctrl=fronturl') === false) {
        $sh404SEF = acym_isExtensionActive('com_sh404sef') && defined('SH404SEF_IS_RUNNING') && SH404SEF_IS_RUNNING == 1;
        if ($sh404SEF && class_exists('Sh404sefHelperGeneral') && method_exists('Sh404sefHelperGeneral', 'getSefFromNonSef')) {
            // sh404 generates a PHP notice when the content language is missing in Joomla
            return @Sh404sefHelperGeneral::getSefFromNonSef($link);
        } else {
            try {
                return Route::link('site', $link, true, 0, true);
            } catch (Exception $e) {
            }
        }
    }

    $mainurl = acym_mainURL($link);

    return $mainurl.$link;
}

function acym_backendLink(string $link): string
{
    $link = 'index.php?option='.ACYM_COMPONENT.'&ctrl='.$link;

    try {
        return Route::link('administrator', $link, true, 0, true);
    } catch (Exception $e) {
    }

    return ACYM_LIVE.'administrator/'.$link;
}

function acym_getMenu()
{
    global $Itemid;

    $jsite = Factory::getApplication('site');
    $menus = $jsite->getMenu();
    $menu = $menus->getActive();

    if (empty($menu) && !empty($Itemid)) {
        $menus->setActive($Itemid);
        $menu = $menus->getItem($Itemid);
    }

    return $menu;
}
