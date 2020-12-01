<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\UserStatClass;
use AcyMailing\Libraries\acymController;

class FrontstatsController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->authorizedFrontTasks = ['openStats'];
    }

    public function openStats()
    {
        $mailId = acym_getVar('int', 'id');
        $userId = acym_getVar('int', 'userid');

        if (!empty($mailId) && !empty($userId)) {
            $userStatClass = new UserStatClass();
            $userStat = $userStatClass->getOneByMailAndUserId($mailId, $userId);
            if (!empty($userStat)) {
                $openUnique = 1;
                if ($userStat->open > 0) {
                    $openUnique = 0;
                }

                $mailStat = [];
                $mailStat['mail_id'] = $mailId;
                $mailStat['open_unique'] = $openUnique;
                $mailStat['open_total'] = 1;

                $mailStatClass = new MailStatClass();
                $mailStatClass->save($mailStat);

                $device = '';
                $openedWith = '';
                if (isset($_SERVER['HTTP_USER_AGENT'])) {
                    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
                    $allDevices = array_merge($userStatClass::DESKTOP_DEVICES, $userStatClass::MOBILE_DEVICES);

                    foreach ($allDevices as $oneDeviceKey => $oneDevice) {
                        if (preg_match('/'.$oneDeviceKey.'/', $userAgent)) {
                            $device = $oneDeviceKey;
                            break;
                        }
                    }

                    require_once ACYM_INC.'browser'.DS.'browser.php';
                    $browser = new \Browser($userAgent);
                    $openedWith = $browser->getBrowser();
                }

                $userStatToInsert = [];
                $userStatToInsert['user_id'] = $userId;
                $userStatToInsert['mail_id'] = $mailId;
                $userStatToInsert['open'] = 1;
                $userStatToInsert['open_date'] = acym_date('now', 'Y-m-d H:i:s');
                $userStatToInsert['device'] = $device;
                $userStatToInsert['opened_with'] = $openedWith;

                $userStatClass->save($userStatToInsert);
            }
        }

        acym_noCache();

        ob_end_clean();

        $picture = ACYM_MEDIA_RELATIVE.'images/statpicture.png';

        $picture = ltrim(str_replace(['\\', '/'], DS, $picture), DS);

        $imagename = ACYM_ROOT.$picture;
        $handle = fopen($imagename, 'r');
        if (!$handle) {
            exit;
        }

        acym_header('Content-type: image/png');
        $contents = fread($handle, filesize($imagename));
        fclose($handle);
        echo $contents;
        exit;
    }
}
