<?php

// Front-end breadcrumb for Joomla
function acym_addBreadcrumb(string $title, string $link = ''): void
{
}

// Page title modification for Joomla
function acym_setPageTitle(string $title): void
{
}

function acym_isLeftMenuNecessary(): bool
{
    // Already one in WP
    return false;
}

function acym_getLeftMenu(string $name): string
{
    return '';
}

function acym_isPluginActive(string $plugin, string $family = 'system'): bool
{
    return true;
}

function acym_disableCmsEditor(): void
{
    add_filter(
        'user_can_richedit',
        function ($a) {
            return false;
        },
        50
    );
}

function acym_isElementorEdition(): bool
{
    global $post;

    if (empty($post) || !class_exists('\\Elementor\\Plugin')) return false;

    return \Elementor\Plugin::$instance->db->is_built_with_elementor($post->ID);
}
