<?php

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];

        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) === 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }
}

function acym_isAjax()
{
    $allHeaders = getallheaders();

    if (!empty($allHeaders['Accept'])) {
        $headerAccept = $allHeaders['Accept'];
    } elseif (!empty($allHeaders['accept'])) {
        $headerAccept = $allHeaders['accept'];
    } else {
        $headerAccept = '';
    }

    return strpos($headerAccept, 'application/json') !== false;
}
