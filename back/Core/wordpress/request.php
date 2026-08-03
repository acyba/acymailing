<?php
// context verification

/**
 * @param string $url
 * @param array  $options
 *                - verifySsl: boolean, default true
 *                - headers: array for example ['Content-Type' => 'application/json']
 *                - data: array, if Content-Type is application/json, the data will be json_encoded otherwise it will be urlencoded
 *                - dns: string
 *                - proxy: array ['host' => '127.0.0.1:8888', 'auth' => 'user:password'] the auth is optional
 *                - method: string GET, POST, etc
 *
 * @return array
 */
function acym_makeCurlCall(string $url, array $options = []): array
{
    $verifySsl = $options['verifySsl'] ?? true;
    $headers = (!empty($options['headers']) && is_array($options['headers'])) ? $options['headers'] : [];
    $data = (!empty($options['data']) && is_array($options['data'])) ? $options['data'] : [];
    $files = (!empty($options['files']) && is_array($options['files'])) ? $options['files'] : [];

    $method = (!empty($options['method']) && in_array($options['method'], ['GET', 'POST'], true)) ? $options['method'] : 'GET';

    $multipart = !empty($options['multipart']) || !empty($files);

    $contentTypeKey = '';
    foreach (array_keys($headers) as $headerName) {
        if (strtolower($headerName) === 'content-type') {
            $contentTypeKey = $headerName;
            break;
        }
    }

    $args = [
        'method' => $method,
        'sslverify' => (bool)$verifySsl,
        'timeout' => 30,
    ];

    if ($method === 'POST' && $multipart) {
        if (!empty($files)) {
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once ABSPATH.'wp-admin/includes/file.php';
                WP_Filesystem();
            }
        }

        $boundary = wp_generate_password(24, false);
        $eol = "\r\n";
        $body = '';
        foreach ($data as $name => $value) {
            $body .= '--'.$boundary.$eol;
            $body .= 'Content-Disposition: form-data; name="'.$name.'"'.$eol.$eol;
            $body .= $value.$eol;
        }
        foreach ($files as $file) {
            if (empty($file['path']) || empty($wp_filesystem) || !$wp_filesystem->exists($file['path'])) {
                continue;
            }

            $content = $wp_filesystem->get_contents($file['path']);
            if ($content === false) {
                continue;
            }

            $fileName = $file['filename'] ?? basename($file['path']);
            $body .= '--'.$boundary.$eol;
            $body .= 'Content-Disposition: form-data; name="'.$file['name'].'"; filename="'.$fileName.'"'.$eol;
            $body .= 'Content-Type: application/octet-stream'.$eol.$eol;
            $body .= $content.$eol;
        }
        $body .= '--'.$boundary.'--'.$eol;

        if ($contentTypeKey !== '') {
            unset($headers[$contentTypeKey]);
        }
        $headers['Content-Type'] = 'multipart/form-data; boundary='.$boundary;
        $args['body'] = $body;
    } else {
        $dataFormatted = '';
        if (!empty($data)) {
            $isJson = $contentTypeKey !== '' && strtolower(trim($headers[$contentTypeKey])) === 'application/json';
            $dataFormatted = ($method === 'POST' && $isJson) ? wp_json_encode($data) : http_build_query($data);
        }
        if ($method === 'GET' && !empty($dataFormatted)) {
            $url .= (strpos($url, '?') === false ? '?' : '&').$dataFormatted;
        }
        if ($method === 'POST' && !empty($dataFormatted)) {
            $args['body'] = $dataFormatted;
        }
    }

    $args['headers'] = $headers;

    $response = wp_remote_request($url, $args);

    if (is_wp_error($response)) {
        return ['error' => $response->get_error_message(), 'status_code' => 0];
    }

    $httpCode = (int)wp_remote_retrieve_response_code($response);
    $decoded = json_decode(wp_remote_retrieve_body($response), true);

    if (!is_array($decoded)) {
        $decoded = [];
    }
    $decoded['status_code'] = $httpCode;

    return $decoded;
}

function acym_asyncUrlCalls(array $urls): void
{
    $args = [
        'timeout' => 5,
        'blocking' => false,
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Applying SSL WP filter.
        'sslverify' => apply_filters('https_local_ssl_verify', false),
    ];

    $errors = [];
    foreach ($urls as $url) {
        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            $errors[] = $response->get_error_message();
        }
    }

    if (empty($errors)) {
        return;
    }

    $config = acym_config();
    $reportPath = $config->get('cron_savepath');
    if (empty($reportPath)) {
        return;
    }

    global $wp_filesystem;
    if (empty($wp_filesystem)) {
        require_once ABSPATH.'wp-admin/includes/file.php';
        WP_Filesystem();
    }
    if (empty($wp_filesystem)) {
        return;
    }

    $reportPath = str_replace(['{year}', '{month}'], [gmdate('Y'), gmdate('m')], $reportPath);
    $reportPath = acym_cleanPath(ACYM_ROOT.trim(html_entity_decode($reportPath)));
    acym_createDir(dirname($reportPath), true, true);

    $lr = "\r\n";
    $message = $lr.$lr.'********************     '.acym_getDate(time()).'     ********************'.$lr
        .'An error occurred while calling the queue sending script: '.implode(' | ', $errors);

    $existing = $wp_filesystem->exists($reportPath) ? $wp_filesystem->get_contents($reportPath) : '';
    $wp_filesystem->put_contents($reportPath, $existing.$message, FS_CHMOD_FILE);
}
