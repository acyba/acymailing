<?php

function acym_sendAjaxResponse(string $message = '', array $data = [], bool $success = true): void
{
    $response = [
        'message' => $message,
        'data' => $data,
        'error' => !$success,
    ];

    // Get the document object.
    $document = acym_getGlobal('doc');

    // Set the MIME type for JSON output.
    $document->setMimeEncoding('application/json');

    // Output the JSON data.
    echo json_encode($response);
    exit;
}
