<?php

use AcyMailing\Helpers\HeaderHelper;

function acym_enqueueMessage($message, $type = 'success')
{
    $type = str_replace(['notice', 'message'], ['info', 'success'], $type);
    $message = is_array($message) ? implode('<br/>', $message) : $message;

    $notification = new stdClass();
    $notification->message = $message;
    $notification->date = time();
    $notification->read = false;
    $notification->level = $type;

    $handledTypes = ['info', 'warning', 'error'];

    if (acym_isAdmin()) {
        $helperHeader = new HeaderHelper();
        $notification->id = $helperHeader->addNotification($notification);
    } else {
        $handledTypes[] = 'success';
    }

    if (in_array($type, $handledTypes)) {
        acym_session();
        if (empty($_SESSION['acymessage'.$type]) || !in_array($message, $_SESSION['acymessage'.$type])) {
            $_SESSION['acymessage'.$type][$notification->id] = $message;
        }
    }

    return true;
}

function acym_displayMessages()
{
    $types = ['success', 'info', 'warning', 'error'];
    acym_session();
    foreach ($types as $id => $type) {
        if (empty($_SESSION['acymessage'.$type])) continue;

        acym_display($_SESSION['acymessage'.$type], $type);
        unset($_SESSION['acymessage'.$type]);
    }
}
