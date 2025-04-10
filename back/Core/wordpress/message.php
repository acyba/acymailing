<?php

use AcyMailing\Helpers\HeaderHelper;

function acym_enqueueMessage($message, string $type = 'success', bool $addNotification = true, array $addDashboardNotification = [], bool $addHeaderNotification = true)
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
        acym_session();
        if (empty($_SESSION['acymessage'.$type]) || !in_array($message, $_SESSION['acymessage'.$type])) {
            if (empty($notification->id)) {
                $_SESSION['acymessage'.$type][] = $message;
            } else {
                $_SESSION['acymessage'.$type][$notification->id] = $message;
            }
        }
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

        $config->save(['dashboard_notif' => json_encode($existingNotifications)], false);
    }

    return true;
}

function acym_displayMessages()
{
    $types = ['success', 'info', 'warning', 'error'];
    acym_session();
    foreach ($types as $type) {
        if (empty($_SESSION['acymessage'.$type])) continue;

        acym_display($_SESSION['acymessage'.$type], $type);
        unset($_SESSION['acymessage'.$type]);
    }
}
