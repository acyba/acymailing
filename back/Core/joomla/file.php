<?php

use Joomla\Archive\Archive;
use Joomla\CMS\Http\HttpFactory;

/**
 * Returns the url content or false if couldn't get it
 *
 * @param string $url
 * @param int    $timeout
 *
 * @return bool|mixed|string
 */
function acym_fileGetContent(string $url, int $timeout = 10)
{
    ob_start();
    // use the Joomla way first
    $data = '';

    if (!ACYM_J40) {
        if (function_exists('file_get_contents')) {
            if (!empty($timeout)) {
                ini_set('default_socket_timeout', $timeout);
            }
            $streamContext = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
            $data = @file_get_contents($url, false, $streamContext);
        }
    }

    if (empty($data)) {
        $http = HttpFactory::getHttp();
        try {
            $response = $http->get($url, [], $timeout);
        } catch (RuntimeException $e) {
            $response = null;
        }

        if ($response !== null && $response->code === 200) {
            $data = $response->body;
        }
    }

    if (empty($data) && function_exists('curl_exec') && filter_var($url, FILTER_VALIDATE_URL)) {
        $conn = curl_init($url);
        curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($conn, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($timeout)) {
            curl_setopt($conn, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($conn, CURLOPT_CONNECTTIMEOUT, $timeout);
        }

        $data = curl_exec($conn);
        if ($data === false) {
            echo curl_error($conn);
        }
        curl_close($conn);
    }

    if (empty($data) && function_exists('fopen') && function_exists('stream_get_contents')) {
        $handle = fopen($url, 'r');
        if (!empty($handle)) {
            if (!empty($timeout)) {
                stream_set_timeout($handle, $timeout);
            }
            $data = stream_get_contents($handle);
        }
    }
    $warnings = ob_get_clean();

    if (acym_isDebug()) {
        echo $warnings;
    }

    return $data;
}

function acym_extractArchive($archive, $destination)
{
    if (ACYM_J40) {
        $archiveManager = new Archive();

        return $archiveManager->extract($archive, $destination);
    } else {
        return JArchive::extract($archive, $destination);
    }
}
