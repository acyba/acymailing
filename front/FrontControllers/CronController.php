<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Helpers\CronHelper;
use AcyMailing\Core\AcymController;

class CronController extends AcymController
{
    public function __construct()
    {
        parent::__construct();
        acym_setNoTemplate();
        $this->setDefaultTask('cron');

        $this->publicFrontTasks = [
            'cron',
        ];
    }

    public function isSecureCronUrl(): bool
    {
        $cronKey = acym_getVar('string', 'cronKey', '');

        return $cronKey === $this->config->get('cron_key', '');
    }

    public function cron()
    {
        //We check if the cron security is enabled
        if (!empty($this->config->get('cron_security', 0)) && !$this->isSecureCronUrl()) {
            die(acym_translation('ACYM_SECURITY_KEY_CRON_MISSING'));
        }

        //__START__demo_
        if (!ACYM_PRODUCTION) {
            exit;
        }
        //__END__demo_

        // Starter versions shouldn't have access to the cron
        if (!acym_level(ACYM_ESSENTIAL)) exit;

        acym_header('Content-type:text/html; charset=utf-8');
        //We block the cron if there is no domain specified... it can happen if you created your own cron with a wrong command.
        //Why 10? Because it should be at least http://1.1
        if (strlen(ACYM_LIVE) < 10) {
            die(acym_translationSprintf('ACYM_CRON_WRONG_DOMAIN', ACYM_LIVE));
        }


        //removeIf(development)
        if (!acym_isLicenseValidWeekly() && (empty($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'api.acymailing.com') === false)) {
            exit;
        }
        //endRemoveIf(development)


        echo '<html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8" /><title>'.acym_translation('ACYM_CRON').'</title></head><body>';
        $cronHelper = new CronHelper();
        $cronHelper->addSkipFromString(acym_getVar('string', 'skip', ''));
        $emailTypes = acym_getVar('string', 'emailtypes', '');
        if (!empty($emailTypes)) {
            $cronHelper->setEmailTypes(explode(',', $emailTypes));
        }
        $cronHelper->cron();
        echo '</body></html>';

        exit;
    }
}
