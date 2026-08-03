<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\UserStatClass;
use AcyMailing\Core\AcymController;
use AcyMailing\Libraries\Browser\BrowserDetection;

class FrontstatsController extends AcymController
{
    public function __construct()
    {
        parent::__construct();

        $this->publicFrontTasks = [
            'openStats',
        ];
    }

    public function openStats(): void
    {
        $mailId = acym_getVar('int', 'id');
        $userId = acym_getVar('int', 'userid');

        if (!empty($mailId) && !empty($userId) && !acym_isRobot()) {
            $this->recordOpen($mailId, $userId);
        }

        acym_noCache();
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        $statsPicture = ACYM_MEDIA_RELATIVE.'images/editor/statpicture.png';
        $statsPicture = ACYM_ROOT.ltrim(str_replace(['\\', '/'], DS, $statsPicture), DS);

        acym_header('Content-type: image/png');
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Efficient and less memory usage.
        readfile($statsPicture);
        exit;
    }

    private function recordOpen(int $mailId, int $userId): void
    {
        $userStatClass = new UserStatClass();
        $userStat = $userStatClass->getOneByMailAndUserId($mailId, $userId);

        // Ignore opens within X seconds following the email sending to prevent bots from impacting statistics
        $delay = $this->config->get('tracking_delay', 0);
        if (empty($userStat) || acym_isRobot() || (!empty($delay) && acym_getTimeFromUTCDate($userStat->send_date) > time() - $delay)) {
            return;
        }

        $mailStat = new \stdClass();
        $mailStat->mail_id = $mailId;
        $mailStat->open_unique = $userStat->open > 0 ? 0 : 1;
        $mailStat->open_total = 1;

        $mailStatClass = new MailStatClass();
        $mailStatClass->save($mailStat);

        $userStatToInsert = new \stdClass();
        $userStatToInsert->user_id = $userId;
        $userStatToInsert->mail_id = $mailId;
        $userStatToInsert->open = 1;
        $userStatToInsert->open_date = acym_date('now', 'Y-m-d H:i:s', false);
        $userStatToInsert->device = '';
        $userStatToInsert->opened_with = '';

        $userAgent = acym_getVar('string', 'HTTP_USER_AGENT', null, 'SERVER');
        if (!empty($userAgent)) {
            $browserDetection = new BrowserDetection();
            $openingInformation = $browserDetection->getAll($userAgent);

            $userStatToInsert->device = $openingInformation['os_name'] === 'unknown' ? '' : $openingInformation['os_name'];
            $userStatToInsert->opened_with = $openingInformation['browser_name'] === 'unknown' ? '' : $openingInformation['browser_name'];
        }

        $userStatClass->save($userStatToInsert);

        $userClass = new UserClass();
        $subscriber = $userClass->getOneById($userId);
        if (!empty($subscriber)) {
            $subscriber->last_open_date = $userStatToInsert->open_date;
            $userClass->triggers = false;
            $userClass->sendConf = false;
            $userClass->save($subscriber);
        }
    }
}
