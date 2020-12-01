<?php

function acym_sendAjaxResponse($message = '', $data = [], $success = true)
{
    $response = [
        'message' => $message,
        'data' => $data,
        'error' => !$success,
    ];

    wp_send_json($response);
}
