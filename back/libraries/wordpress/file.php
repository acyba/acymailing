<?php

function acym_fileGetContent($url, $timeout = 10)
{
    if (strpos($url, '_custom.ini') !== false && !file_exists($url)) {
        return '';
    }

    ob_start();
    $data = '';

    if (strpos($url, 'http') === 0 && class_exists('WP_Http') && method_exists('WP_Http', 'request')) {
        $args = ['timeout' => $timeout];
        $request = new WP_Http();
        $data = $request->request($url, $args);
        $data = (empty($data) || !is_array($data) || empty($data['body'])) ? '' : $data['body'];
    }

    if (empty($data) && function_exists('file_get_contents')) {
        if (!empty($timeout)) {
            ini_set('default_socket_timeout', $timeout);
        }
        $streamContext = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $data = file_get_contents($url, false, $streamContext);
    }

    if (empty($data) && function_exists('fopen') && function_exists('stream_get_contents')) {
        $handle = fopen($url, "r");
        if (!empty($timeout)) {
            stream_set_timeout($handle, $timeout);
        }
        $data = stream_get_contents($handle);
    }
    $warnings = ob_get_clean();

    if (acym_isDebug()) {
        echo $warnings;
    }

    return $data;
}

function acym_extractArchive($archive, $destination)
{
    if (substr($archive, strlen($archive) - 4) !== '.zip') {
        return false;
    }

    WP_Filesystem();

    return true === unzip_file($archive, $destination);
}
