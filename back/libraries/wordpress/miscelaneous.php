<?php

// Front-end breadcrumb for Joomla
function acym_addBreadcrumb($title, $link = '')
{
}

// Page title modification for Joomla
function acym_setPageTitle($title)
{
}

function acym_isLeftMenuNecessary()
{
    // Already one in WP
    return false;
}

function acym_getLeftMenu($name)
{
    return '';
}

function acym_isPluginActive($plugin, $family = 'system')
{
    return true;
}

function acym_menuOnly($link)
{
}

function acym_disableCmsEditor()
{
    add_filter(
        'user_can_richedit',
        function ($a) {
            return false;
        },
        50
    );
}

function acym_isElementorEdition()
{
    global $post;

    if (empty($post) || !class_exists('\\Elementor\\Plugin')) return false;

    return \Elementor\Plugin::$instance->db->is_built_with_elementor($post->ID);
}
