<?php

use AcyMailing\Helpers\HeaderHelper;

/**
 * @param array|string $message The message to display
 * @param string       $type    The type (success, error, warning, info, message, notice)
 */
function acym_enqueueMessage($message, string $type = 'success', bool $addNotification = true, array $addDashboardNotification = [], bool $addHeaderNotification = true): void
{
    $type = str_replace(['notice', 'message'], ['info', 'success'], $type);
    $message = is_array($message) ? implode('<br/>', $message) : $message;

    $handledTypes = ['info', 'warning', 'error'];

    if ($addNotification && acym_isAdmin()) {
        $notification = new stdClass();
        $notification->message = $message;
        $notification->date = time();
        $notification->read = false;
        $notification->level = $type;

        $helperHeader = new HeaderHelper();
        $helperHeader->addNotification($notification);
    } else {
        $handledTypes[] = 'success';
    }

    if (!empty($addDashboardNotification)) {
        $config = acym_config();
        $notRemindable = json_decode($config->get('remindme'), true);
        $existingNotifications = json_decode($config->get('dashboard_notif', '[]'), true);

        foreach ($addDashboardNotification as &$dashboardNotification) {
            if (in_array($dashboardNotification['name'], $notRemindable)) {
                continue;
            }
            $dashboardNotification['date'] = time();
            $dashboardNotification['level'] = $type;
            $dashboardNotification['message'] = $message;

            $found = false;
            foreach ($existingNotifications as &$existingNotification) {
                if (is_array($existingNotification) && $existingNotification['name'] === $dashboardNotification['name']) {
                    $existingNotification = $dashboardNotification;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $existingNotifications[] = $dashboardNotification;
            }
        }

        $config->saveConfig(['dashboard_notif' => json_encode($existingNotifications)], false);
    }

    if (in_array($type, $handledTypes) && $addHeaderNotification) {
        $acyapp = acym_getGlobal('app');

        // Display the translated text
        if (ACYM_J30) {
            $type = str_replace(
                ['info', 'success'],
                ['notice', 'message'],
                $type
            );
        }

        $acyapp->enqueueMessage($message, $type);
    }
}

function acym_displayMessages(): void
{
    $acyapp = acym_getGlobal('app');
    $messages = $acyapp->getMessageQueue(true);
    if (empty($messages)) {
        return;
    }

    $sorted = [];
    foreach ($messages as $oneMessage) {
        $sorted[$oneMessage['type']][] = $oneMessage['message'];
    }

    foreach ($sorted as $type => $message) {
        $type = str_replace(['notice', 'message'], ['info', 'success'], $type);
        acym_display($message, $type);
    }
}
