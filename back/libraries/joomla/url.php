<?php

function acym_route($url, $xhtml = true, $ssl = null)
{
    if (ACYM_J40) {
        global $Itemid;
        if (!acym_isAdmin() && !empty($Itemid) && strpos($url, 'Itemid') === false) {
            $url .= (strpos($url, '?') ? '&' : '?').'Itemid='.$Itemid;
        }
        $result = JRoute::_($url, $xhtml, $ssl === null ? 0 : $ssl);

        return $result;
    }

    return JRoute::_($url, $xhtml, $ssl);
}

function acym_baseURI($pathonly = false)
{
    return JURI::base($pathonly);
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

function acym_frontendLink($link, $complete = true)
{
    if ($complete) {
        $link = 'index.php?option='.ACYM_COMPONENT.'&ctrl='.$link;
    }

    if (ACYM_J39 && strpos($link, 'ctrl=cron') === false && strpos($link, 'ctrl=fronturl') === false) {
        return JRoute::link('site', $link, true, 0, true);
    }

    $mainurl = acym_mainURL($link);

    return $mainurl.$link;
}

function acym_getMenu()
{
    global $Itemid;

    $jsite = JFactory::getApplication('site');
    $menus = $jsite->getMenu();
    $menu = $menus->getActive();

    if (empty($menu) && !empty($Itemid)) {
        $menus->setActive($Itemid);
        $menu = $menus->getItem($Itemid);
    }

    return $menu;
}
