<?php

namespace AcyMailing\Controllers\Configuration;

use AcyMailing\Helpers\HeaderHelper;

trait Subscription
{
    public function removeNotification()
    {
        $whichNotification = acym_getVar('string', 'id');

        if ($whichNotification != 0 && empty($whichNotification)) {
            acym_sendAjaxResponse(acym_translation('ACYM_NOTIFICATION_NOT_FOUND'), [], false);
        }

        if ('all' === $whichNotification) {
            $this->config->save(['notifications' => '[]']);
            $notifications = [];
        } else {
            $notifications = json_decode($this->config->get('notifications', '[]'), true);
            unset($notifications[$whichNotification]);
            $this->config->save(['notifications' => json_encode($notifications)]);
        }
        $helperHeader = new HeaderHelper();

        acym_sendAjaxResponse('', ['html' => $helperHeader->getNotificationCenterInner($notifications)]);
    }

    public function markNotificationRead()
    {
        $which = acym_getVar('string', 'id');

        $notifications = json_decode($this->config->get('notifications', '[]'), true);
        if (empty($notifications)) {
            acym_sendAjaxResponse('', []);
        }

        if (empty($which)) {
            foreach ($notifications as $key => $notification) {
                $notifications[$key]['read'] = true;
            }
        } else {
            foreach ($notifications as $key => $notification) {
                if ($notification['id'] != $which) continue;
                $notifications[$key]['read'] = true;
            }
        }


        $this->config->save(['notifications' => json_encode($notifications)]);

        acym_sendAjaxResponse('', []);
    }

    public function addNotification()
    {
        $message = acym_getVar('string', 'message');
        $level = acym_getVar('string', 'level');

        if (empty($message) || empty($level)) {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR'), [], false);
        }

        $helperHeader = new HeaderHelper();

        $newNotification = new \stdClass();
        $newNotification->message = $message;
        $newNotification->level = $level;
        $newNotification->read = false;
        $newNotification->date = time();

        $helperHeader->addNotification($newNotification);

        acym_sendAjaxResponse('', ['notificationCenter' => $helperHeader->getNotificationCenter()]);
    }

    private function loadSurveyAnswers(&$data)
    {
        $surveyTexts = $this->config->get('unsub_survey', '{}');
        $data['surveyAnswers'] = json_decode($surveyTexts, true);
    }
}
