<?php

namespace AcyMailing\WpInit;

class WpRocket
{
    public function __construct()
    {
        add_filter('rocket_exclude_static_dynamic_resources', [$this, 'excludeScriptForWpRocket']);
    }

    public function excludeScriptForWpRocket(array $excluded_files): array
    {
        $excluded_files[] = WP_PLUGIN_DIR.'/acymailing/back/Partial/forms/cookie.php';

        return $excluded_files;
    }
}
