<?php

namespace AcyMailing\Init;

use AcyMailing\Helpers\CronHelper;

class acyCron extends acyHook
{
    public function __construct()
    {
        $ctrl = acym_getVar('string', 'ctrl', '');
        if ($ctrl != 'cron') add_action('init', [$this, 'callAcyCron']);
    }

    public function callAcyCron()
    {
        acym_asyncCurlCall([acym_frontendLink('cron')]);
    }
}

$acyCron = new acyCron();
