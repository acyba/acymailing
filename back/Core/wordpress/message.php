<?php

defined('ABSPATH') || die('Restricted Access');

use AcyMailing\Helpers\HeaderHelper;

/**
 * @param array|string $message The message to display
 * @param string       $type    The type (success, error, warning, info, message, notice)
 */
function acym_enqueueMessage($message, string $type = 'success', bool $addNotification = true, array $addDashboardNotification = [], bool $addHeaderNotification = true): void
{
    $type = str_replace(['notice', 'message'], ['info', 'success'], $type);
    $message = is_array($message) ? implode('<br/>', $message) : $message;

    $notification = new stdClass();
    $notification->message = $message;
    $notification->date = time();
    $notification->read = false;
    $notification->level = $type;

    $handledTypes = ['info', 'warning', 'error'];

    if ($addNotification && acym_isAdmin()) {
        $helperHeader = new HeaderHelper();
        $notification->id = $helperHeader->addNotification($notification);
    } else {
        $handledTypes[] = 'success';
    }

    if (in_array($type, $handledTypes) && $addHeaderNotification) {
        $messages = acym_getVar('array', 'acymessage'.$type, [], 'SESSION');

        if (empty($messages) || !in_array($message, $messages)) {
            if (empty($notification->id)) {
                $messages[] = $message;
            } else {
                $messages[$notification->id] = $message;
            }
        }

        acym_setSession('acymessage'.$type, $messages);
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
                if ($existingNotification['name'] === $dashboardNotification['name']) {
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
}

function acym_displayMessages(): void
{
    $types = ['success', 'info', 'warning', 'error'];
    foreach ($types as $type) {
        $messages = acym_getVar('array', 'acymessage'.$type, [], 'SESSION');
        if (empty($messages)) {
            continue;
        }

        acym_display($messages, $type);
        acym_setSession('acymessage'.$type, null, true);
    }
}
