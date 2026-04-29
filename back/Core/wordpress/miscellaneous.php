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

function acym_scheduleTask(array $options, int $taskId = 0): ?int
{
    if (wp_next_scheduled($options['name'])) {
        return 1;
    }

    return true === wp_schedule_event(time(), $options['taskFrequency'], $options['name']) ? 1 : 0;
}

function acym_deleteScheduledTask(array $options): bool
{
    $timestamp = wp_next_scheduled($options['name']);
    if ($timestamp) {
        return true === wp_unschedule_event($timestamp, $options['name']);
    }

    return true;
}
