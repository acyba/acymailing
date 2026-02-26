<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\UrlClass;
use AcyMailing\Classes\UrlClickClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\UserStatClass;
use AcyMailing\Core\AcymController;

class FronturlController extends AcymController
{
    public function __construct()
    {
        parent::__construct();

        $this->publicFrontTasks = [
            'click',
        ];
    }

    public function click(): void
    {
        $urlId = acym_getVar('int', 'urlid');
        $mailId = acym_getVar('int', 'mailid');
        $userId = acym_getVar('int', 'userid');

        $urlClass = new UrlClass();
        $urlObject = $urlClass->getOneUrlById($urlId);

        if (empty($urlObject->id)) {
            acym_raiseError(404, acym_translation('ACYM_PAGE_NOT_FOUND'));
        }

        $urlObject->url = preg_replace(
            [
                '#&idU=[0-9]+#i',
                '#idU=[0-9]+&#i',
                '#\?idU=[0-9]+#i',
            ],
            '',
            $urlObject->url
        );

        $mailClass = new MailClass();
        $mail = $mailClass->getOneById($mailId);

        $userStatClass = new UserStatClass();
        $userStat = $userStatClass->getOneByMailAndUserId($mailId, $userId);

        // The mail has been deleted, or we didn't send this email to this user, or it's a bot
        if (empty($mail) || empty($userStat) || acym_isRobot()) {
            acym_redirect($this->resolveSubscriberTags($urlObject->url, $userId));
        }

        $urlClick = new \stdClass();
        $urlClick->mail_id = $mailId;
        $urlClick->url_id = $urlObject->id;
        $urlClick->click = 1;
        $urlClick->user_id = $userId;
        $urlClick->date_click = acym_date('now', 'Y-m-d H:i:s');

        $mailStatClass = new MailStatClass();
        $urlClickClass = new UrlClickClass();
        $urlClickClass->save($urlClick);

        if (empty($userStat->open)) {
            $userStatToInsert = new \stdClass();
            $userStatToInsert->user_id = $userId;
            $userStatToInsert->mail_id = $mailId;
            $userStatToInsert->open = 1;
            $userStatToInsert->open_date = acym_date('now', 'Y-m-d H:i:s');
            $userStatClass->save($userStatToInsert);

            $mailStat = new \stdClass();
            $mailStat->mail_id = $mailId;
            $mailStat->open_unique = 1;
            $mailStat->open_total = 1;
            $mailStatClass->save($mailStat);
        }

        $clickStats = $urlClickClass->getOneByMailIdAndUserId($mailId, $userId);
        $mailStatClass->incrementClicks($mailId, $clickStats->click == 1);

        $userClass = new UserClass();
        $subscriber = $userClass->getOneById($userId);
        if (!empty($subscriber)) {
            $subscriber->last_open_date = acym_date('now', 'Y-m-d H:i:s');
            $subscriber->last_click_date = acym_date('now', 'Y-m-d H:i:s');
            $userClass->triggers = false;
            $userClass->sendConf = false;
            $userClass->save($subscriber);
        }

        acym_redirect($this->resolveSubscriberTags($urlObject->url, $userId));
    }

    private function resolveSubscriberTags(string $url, int $userId): string
    {
        if (!preg_match('#\{|%7B#i', $url)) {
            return $url;
        }

        $userClass = new UserClass();
        $subscriber = $userClass->identify(true, 'userid', 'userkey');

        if (empty($subscriber) || (int)$subscriber->id !== $userId) {
            return $url;
        }

        $tempEmail = new \stdClass();
        $tempEmail->body = $url;
        acym_trigger('replaceUserInformation', [&$tempEmail, &$subscriber, true]);

        return $tempEmail->body;
    }
}
