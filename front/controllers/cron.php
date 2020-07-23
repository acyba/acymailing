<?php

class CronController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultTask('cron');
        $this->authorizedFrontTasks = ['cron'];
        acym_setNoTemplate();
    }

    public function cron()
    {
        acym_header('Content-type:text/html; charset=utf-8');
        //We block the cron if there is no domain specified... it can happen if you created your own cron with a wrong command.
        //Why 10? Because it should be at least http://1.1
        if (strlen(ACYM_LIVE) < 10) {
            die(acym_translation_sprintf('ACYM_CRON_WRONG_DOMAIN', ACYM_LIVE));
        }

        $expirationDate = $this->config->get('expirationdate', 0);
        if (empty($expirationDate) || (time() - 604800) > $this->config->get('lastlicensecheck', 0)) {
            acym_checkVersion();
        }

        //removeIf(development)
        if ($expirationDate < time() && (empty($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'www.yourcrontask.com') === false)) {
            exit;
        }
        //endRemoveIf(development)


        echo '<html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8" /><title>'.acym_translation('ACYM_CRON').'</title></head><body>';
        $cronHelper = acym_get('helper.cron');
        $cronHelper->report = true;
        $cronHelper->skip = explode(',', acym_getVar('string', 'skip'));
        $emailtypes = acym_getVar('string', 'emailtypes');
        if (!empty($emailtypes)) {
            $cronHelper->emailtypes = explode(',', $emailtypes);
        }
        $cronHelper->cron();
        $cronHelper->report();
        echo '</body></html>';

        exit;
    }
}
