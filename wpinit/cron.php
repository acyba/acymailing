<?php

namespace AcyMailing\Init;

class acyCron extends acyHook
{
    public function __construct()
    {
        $ctrl = acym_getVar('string', 'ctrl', '');
        if (acym_level(ACYM_ESSENTIAL) && !acym_isNoTemplate() && $ctrl !== 'cron') {
            add_action('init', [$this, 'callAcyCron']);
        }
    }

    public function callAcyCron()
    {
        $config = acym_config();
        $cronFrequency = $config->get('cron_frequency', 0);
        $cronBatches = $config->get('queue_batch_auto', 1);

        $queueType = $config->get('queue_type', 'manual');
        $cronNext = $config->get('cron_next', 0);
        if ($cronNext > time() || $queueType == 'manual') return;

        if (intval($cronFrequency) < 900 || intval($cronBatches) > 1) {
            acym_asyncCurlCall([acym_frontendLink('cron')]);
        }
    }
}

$acyCron = new acyCron();
