<?php

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
 * @return array|mixed
 */
function acym_makeCurlCall(string $url, array $options = []): array
{
    $options['verifySsl'] = $options['verifySsl'] ?? true;

    $headers = [];
    if (!empty($options['headers']) && is_array($options['headers'])) {
        $headers = $options['headers'];
    }

    $data = [];
    if (!empty($options['data']) && is_array($options['data'])) {
        $data = $options['data'];
    }

    $allowedMethods = ['GET', 'POST'];
    $method = 'GET';
    if (!empty($options['method']) && in_array($options['method'], $allowedMethods)) {
        $method = $options['method'];
    }

    $dataFormatted = '';
    if (!empty($data)) {
        $isHeaderContentTypeJson = !empty($headers['Content-Type']) && $headers['Content-Type'] === 'application/json';
        if ($method === 'POST' && $isHeaderContentTypeJson) {
            $dataFormatted = json_encode($data);
        } else {
            $dataFormatted = http_build_query($data);
        }
    }

    if ($method === 'GET' && !empty($dataFormatted)) {
        $url .= strpos($url, '?') === false ? '?'.$dataFormatted : '&'.$dataFormatted;
    }

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataFormatted);
        }
    }
    if (!$options['verifySsl']) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    }

    if (!empty($headers)) {
        // We have headers like this ['Content-Type' => 'application/json']
        // We need headers like this ['Content-Type: application/json']
        $headersFormatted = array_map(
            function ($key, $value) {
                return $key.': '.$value;
            },
            array_keys($headers),
            $headers
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headersFormatted);
    }

    if (!empty($options['dns']) && is_string($options['dns'])) {
        curl_setopt($ch, CURLOPT_DNS_SERVERS, $options['dns']);
    }

    if (!empty($options['proxy']) && is_array($options['proxy'])) {
        curl_setopt($ch, CURLOPT_PROXY, $options['proxy']['host']);
        if (!empty($options['proxy']['auth'])) {
            curl_setopt($ch, CURLOPT_PROXYUSERPWD, $options['proxy']['auth']);
        }
    }

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);

        curl_close($ch);

        return ['error' => $error, 'status_code' => $httpCode];
    }

    curl_close($ch);

    $result = json_decode($result, true);
    $result['status_code'] = $httpCode;

    return $result;
}

function acym_asyncCurlCall($urls)
{
    if (!function_exists('curl_multi_exec')) return;

    if (!is_array($urls)) $urls = [$urls];

    try {
        $mh = curl_multi_init();

        $handles = [];
        foreach ($urls as $url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }

        $running = null;
        $time = 1;
        do {
            curl_multi_exec($mh, $running);
            usleep(100);
            // if we are over 5s => we go out
            if ($time > 50000) {
                break;
            }
            $time++;
        } while ($running);

        foreach ($handles as $handle) {
            curl_multi_remove_handle($mh, $handle);
        }
        curl_multi_close($mh);
    } catch (Exception $exception) {
        $config = acym_config();
        $reportPath = $config->get('cron_savepath');
        if (!empty($reportPath)) {
            $reportPath = str_replace(['{year}', '{month}'], [date('Y'), date('m')], $reportPath);
            $reportPath = acym_cleanPath(ACYM_ROOT.trim(html_entity_decode($reportPath)));
            acym_createDir(dirname($reportPath), true, true);

            $lr = "\r\n";
            file_put_contents(
                $reportPath,
                $lr.$lr.'********************     '.acym_getDate(
                    time()
                ).'     ********************'.$lr.'An error occurred while launching the multiple cron system, please make sure the PHP function "curl_multi_exec" is activated on your server: '.$exception->getMessage(
                ),
                FILE_APPEND
            );
        }
    }
}
