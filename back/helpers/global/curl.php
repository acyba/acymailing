<?php

function acym_makeCurlCall($url, $fields)
{
    $urlPost = '';
    foreach ($fields as $key => $value) {
        $urlPost .= $key.'='.urlencode($value).'&';
    }

    $urlPost = trim($urlPost, '&');

    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $urlPost);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    //execute post
    $result = curl_exec($ch);

    //if something goes wrong
    if (curl_errno($ch)) {
        $error = curl_error($ch);

        //close connection
        curl_close($ch);

        return ['error' => $error];
    }

    //close connection
    curl_close($ch);

    return json_decode($result, true);
}
