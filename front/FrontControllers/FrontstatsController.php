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

        $handle = fopen($statsPicture, 'r');
        if (!$handle) {
            exit;
        }

        acym_header('Content-type: image/png');
        $contents = fread($handle, filesize($statsPicture));
        fclose($handle);
        echo $contents;
        exit;
    }

    private function recordOpen(int $mailId, int $userId): void
    {
        $userStatClass = new UserStatClass();
        $userStat = $userStatClass->getOneByMailAndUserId($mailId, $userId);
        if (empty($userStat)) {
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
        $userStatToInsert->open_date = acym_date('now', 'Y-m-d H:i:s');
        $userStatToInsert->device = '';
        $userStatToInsert->opened_with = '';

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $browserDetection = new BrowserDetection();
            $openingInformation = $browserDetection->getAll($_SERVER['HTTP_USER_AGENT']);

            $userStatToInsert->device = $openingInformation['os_name'] === 'unknown' ? '' : $openingInformation['os_name'];
            $userStatToInsert->opened_with = $openingInformation['browser_name'] === 'unknown' ? '' : $openingInformation['browser_name'];
        }

        $userStatClass->save($userStatToInsert);

        $userClass = new UserClass();
        $subscriber = $userClass->getOneById($userId);
        if (!empty($subscriber)) {
            $subscriber->last_open_date = acym_date('now', 'Y-m-d H:i:s');
            $userClass->triggers = false;
            $userClass->sendConf = false;
            $userClass->save($subscriber);
        }
    }
}
