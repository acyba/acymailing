<?php

function acym_getAllHeaders()
{
    if (function_exists('getallheaders')) {
        return getallheaders();
    }

    $headers = [];

    foreach ($_SERVER as $name => $value) {
        if (substr($name, 0, 5) === 'HTTP_') {
            $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
        } else {
            $headers[$name] = $value;
        }
    }

    return $headers;
}

function acym_getHeader($headerName)
{
    $allHeaders = acym_getAllHeaders();

    if (!empty($allHeaders[$headerName])) {
        return $allHeaders[$headerName];
    }

    return '';
}

function acym_isAjax()
{
    $allHeaders = acym_getAllHeaders();

    if (!empty($allHeaders['Accept'])) {
        $headerAccept = $allHeaders['Accept'];
    } elseif (!empty($allHeaders['accept'])) {
        $headerAccept = $allHeaders['accept'];
    } else {
        $headerAccept = '';
    }

    return strpos($headerAccept, 'application/json') !== false;
}
