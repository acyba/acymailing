<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\UrlClass;
use AcyMailing\Classes\UrlClickClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\UserStatClass;
use AcyMailing\Libraries\acymController;

class FronturlController extends acymController
{
    public function __construct()
    {
        parent::__construct();

        $this->publicFrontTasks = [
            'click',
        ];
    }

    public function click()
    {
        $urlId = acym_getVar('int', 'urlid');
        $mailId = acym_getVar('int', 'mailid');
        $userId = acym_getVar('int', 'userid');

        $urlClass = new UrlClass();
        $urlObject = $urlClass->getOneUrlById($urlId);

        if (empty($urlObject->id)) {
            acym_raiseError(404, acym_translation('ACYM_PAGE_NOT_FOUND'));
        }

        $mailClass = new MailClass();
        $mail = $mailClass->getOneById($mailId);

        $userStatClass = new UserStatClass();
        $userStat = $userStatClass->getOneByMailAndUserId($mailId, $userId);

        // The mail has been deleted, or we didn't send this email to this user, something is wrong
        if (empty($mail) || empty($userStat)) {
            $urlObject->url = preg_replace(
                [
                    '#&idU=[0-9]+#Uis',
                    '#idU=[0-9]+&#Uis',
                    '#\?idU=[0-9]+#Uis',
                ],
                '',
                $urlObject->url
            );
            acym_redirect($urlObject->url);
        }

        if (!acym_isRobot()) {
            $urlClick = [
                'mail_id' => $mailId,
                'url_id' => $urlObject->id,
                'click' => 1,
                'user_id' => $userId,
                'date_click' => acym_date('now', 'Y-m-d H:i:s'),
            ];
            $urlClickClass = new UrlClickClass();
            $urlClickClass->save($urlClick);

            if (empty($userStat->open)) {
                $userStatToInsert = [];
                $userStatToInsert['user_id'] = $userId;
                $userStatToInsert['mail_id'] = $mailId;
                $userStatToInsert['open'] = 1;
                $userStatToInsert['open_date'] = acym_date('now', 'Y-m-d H:i:s');

                $mailStatToInsert = [];
                $mailStatToInsert['mail_id'] = $mailId;
                $mailStatToInsert['open_unique'] = 1;
                $mailStatToInsert['open_total'] = 1;
                $userStatClass->save($userStatToInsert);

                $mailStatClass = new MailStatClass();
                $mailStatClass->save($mailStatToInsert);
            }

            $userClass = new UserClass();
            $subscriber = $userClass->getOneById($userId);
            if (!empty($subscriber)) {
                $subscriber->last_open_date = acym_date('now', 'Y-m-d H:i:s');
                $subscriber->last_click_date = acym_date('now', 'Y-m-d H:i:s');
                $userClass->triggers = false;
                $userClass->save($subscriber);
            }
        }

        acym_redirect($urlObject->url);
    }
}
