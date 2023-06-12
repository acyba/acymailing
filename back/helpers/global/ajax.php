<?php

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
