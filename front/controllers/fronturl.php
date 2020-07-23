<?php

class FrontUrlController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->authorizedFrontTasks = ['click'];
    }

    public function click()
    {
        $urlid = acym_getVar('int', 'urlid');
        $mailid = acym_getVar('int', 'mailid');
        $userid = acym_getVar('int', 'userid');

        $mailStatClass = acym_get('class.mailstat');
        $userStatClass = acym_get('class.userstat');
        $urlClass = acym_get('class.url');
        $urlObject = $urlClass->getOneUrlById($urlid);

        if (empty($urlObject->id)) {
            return acym_raiseError(E_ERROR, 404, acym_translation('Page not found'));
        }

        // Avoid issue with table constraint if the mail has been removed before the click
        $mailClass = acym_get('class.mail');
        $mail = $mailClass->getOneById($mailid);
        if (empty($mail)) {
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

        $urlClickClass = acym_get('class.urlclick');
        if (!acym_isRobot()) {
            $urlClick = [
                'mail_id' => $mailid,
                'url_id' => $urlObject->id,
                'click' => 1,
                'user_id' => $userid,
                'date_click' => acym_date('now', 'Y-m-d H:i:s'),
            ];
            $urlClickClass->save($urlClick);
            $userStat = $userStatClass->getOneByMailAndUserId($mailid, $userid);
            if (empty($userStat->open)) {
                $userStatToInsert = [];
                $userStatToInsert['user_id'] = $userid;
                $userStatToInsert['mail_id'] = $mailid;
                $userStatToInsert['open'] = 1;
                $userStatToInsert['open_date'] = acym_date('now', 'Y-m-d H:i:s');

                $mailStatToInsert = [];
                $mailStatToInsert['mail_id'] = $mailid;
                $mailStatToInsert['open_unique'] = 1;
                $mailStatToInsert['open_total'] = 1;
                $userStatClass->save($userStatToInsert);
                $mailStatClass->save($mailStatToInsert);
            }
        }

        acym_redirect($urlObject->url);
    }
}
