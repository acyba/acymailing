<?php

namespace AcyMailing\Init;

class acyWpRocket extends acyHook
{
    public function __construct()
    {
        add_filter('rocket_exclude_static_dynamic_resources', [$this, 'excludeScriptForWpRocket']);
    }

    function excludeScriptForWpRocket(array $excluded_files)
    {
        $excluded_files[] = WP_PLUGIN_DIR.'/acymailing/back/partial/forms/cookie.php';

        return $excluded_files;
    }

}

$wprocket = new acyWpRocket();
