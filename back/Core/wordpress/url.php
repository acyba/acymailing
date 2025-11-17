<?php

function acym_route(string $url, bool $xhtml = true): string
{
    return acym_baseURI().$url;
}

function acym_addPageParam(string $url, bool $ajax = false, bool $front = false): string
{
    preg_match('#^([a-z]+)(?:[^a-z]|$)#Uis', $url, $ctrl);

    if ($front) {
        $link = 'index.php?page='.ACYM_COMPONENT.'_front&ctrl='.$url;
        if ($ajax) $link .= '&'.acym_noTemplate();
    } else {
        $link = 'admin.php?page='.ACYM_COMPONENT.'_'.$ctrl[1].'&ctrl='.$url;
        if ($ajax) {
            $link .= '&action='.ACYM_COMPONENT.'_router&'.acym_noTemplate();
        }
    }

    return $link;
}

function acym_baseURI(bool $pathonly = false): string
{
    if (acym_isAdmin()) {
        return admin_url();
    }

    return acym_rootURI();
}

function acym_rootURI(bool $pathonly = false, ?string $path = 'siteurl'): string
{
    $rootURI = rtrim(site_url(), '/').'/';

    // For WPML
    if (!acym_isAdmin()) {
        $wpmlSiteUrl = apply_filters('wpml_home_url', $rootURI);
        if ($wpmlSiteUrl !== $rootURI) {
            $rootURI = rtrim($wpmlSiteUrl, '/').'/';
        }
    }

    // For WordPress bedrock
    if (defined('CONTENT_DIR') && substr($rootURI, -3) === 'wp/') {
        $rootURI = substr($rootURI, 0, -3);
    }

    return $rootURI;
}

function acym_completeLink(string $link, bool $popup = false, bool $redirect = false, bool $forceNoPopup = false): string
{
    if (($popup || acym_isNoTemplate()) && $forceNoPopup == false) {
        $link .= '&'.acym_noTemplate();
    }

    $link = acym_addPageParam($link);

    return acym_route($link);
}

/**
 * If you use it to prepare a POST ajax, make sure you add the action and page parameters to the data passed, it's not taken into account if it's only in the URL
 */
function acym_prepareAjaxURL(string $url): string
{
    return htmlspecialchars_decode(acym_route(acym_addPageParam($url, true)));
}

function acym_frontendLink(string $link, bool $complete = true, bool $sef = true): string
{
    return acym_rootURI().acym_addPageParam($link, true, true);
}

function acym_backendLink(string $link): string
{
    return admin_url().acym_addPageParam($link);
}

function acym_getMenu()
{
    return get_post();
}
