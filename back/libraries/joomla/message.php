<?php

use AcyMailing\Helpers\HeaderHelper;

/**
 * @param string $message The message to display
 * @param string $type    The type (success, error, warning, info, message, notice)
 */
function acym_enqueueMessage($message, $type = 'success', $addNotification = true)
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

    if (in_array($type, $handledTypes)) {
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

    return true;
}

function acym_displayMessages()
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
        acym_display($message, $type);
    }
}
