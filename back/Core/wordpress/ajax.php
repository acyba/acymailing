<?php

function acym_sendAjaxResponse(string $message = '', array $data = [], bool $success = true): void
{
    $response = [
        'message' => $message,
        'data' => $data,
        'error' => !$success,
    ];

    wp_send_json($response);
}
