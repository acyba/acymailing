<?php

namespace AcyMailing\Controllers\Configuration;

use AcyMailing\Controllers\DashboardController;
use AcyMailing\Helpers\HeaderHelper;

trait Subscription
{
    public function removeNotification(): void
    {
        $whichNotification = acym_getVar('string', 'id');

        if ($whichNotification !== '0' && empty($whichNotification)) {
            acym_sendAjaxResponse(acym_translation('ACYM_NOTIFICATION_NOT_FOUND'), [], false);
        }

        $notifications = json_decode($this->config->get('notifications', '[]'), true) ?? [];
        $dashboardNotifications = json_decode($this->config->get('dashboard_notif', '[]'), true) ?? [];

        if ('all' === $whichNotification) {
            $notifications = [];
        } else {
            $notifications = array_values(array_filter($notifications, fn($notif) => !isset($notif['id']) || $notif['id'] !== $whichNotification));

            $dashboardNotifications = array_values(array_filter($dashboardNotifications, fn($notif) => !isset($notif['name']) || $notif['name'] !== $whichNotification));

            if (is_numeric($whichNotification)) {
                $notifications = array_values(array_filter($notifications, fn($notif, $key) => $key != $whichNotification, ARRAY_FILTER_USE_BOTH));
            }
        }

        $this->config->save(['notifications' => json_encode($notifications)]);
        $this->config->save(['dashboard_notif' => json_encode($dashboardNotifications)], false);

        $helperHeader = new HeaderHelper();
        $dashboardController = new DashboardController();

        $data = [
            'notifications' => $dashboardNotifications,
        ];
        $dashboardController->getDashboardNotifications($data);

        acym_sendAjaxResponse(
            '',
            [
                'headerHtml' => $helperHeader->getNotificationCenterInner($notifications),
                'dashboardHtml' => $data['dashboardNotifications'],
            ]
        );
    }

    public function markNotificationRead(): void
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

    public function addNotification(): void
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

    private function loadSurveyAnswers(array &$data): void
    {
        $surveyTexts = $this->config->get('unsub_survey', '{}');
        $data['surveyAnswers'] = json_decode($surveyTexts, true);
    }
}
