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
 * @return array
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

        return [
            'error' => $error,
            'status_code' => $httpCode,
        ];
    }

    curl_close($ch);

    $result = json_decode($result, true);
    $result['status_code'] = $httpCode;

    return $result;
}

function acym_asyncUrlCalls(array $urls): void
{
    if (!function_exists('fsockopen')) {
        return;
    }

    foreach ($urls as $url) {
        $parts = parse_url($url);
        $isSecure = ($parts['scheme'] ?? 'http') === 'https';

        $scheme = $isSecure ? 'ssl://' : '';
        $host = $parts['host'] ?? '';
        $port = $parts['port'] ?? ($isSecure ? 443 : 80);
        $path = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');

        try {
            $fp = @fsockopen($scheme.$host, $port, $errno, $errstr, 1);
            if ($fp) {
                $out = "GET $path HTTP/1.1\r\n";
                $out .= "Host: $host\r\n";
                $out .= "Connection: Close\r\n\r\n";

                fwrite($fp, $out);
                fclose($fp);
            } else {
                throw new Exception($errstr.' ('.$errno.')');
            }
        } catch (Exception $e) {
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
                    ).'     ********************'.$lr.'An error occurred while calling the queue sending script, please make sure the PHP function "fsockopen" is activated on your server: '.$e->getMessage(
                    ),
                    FILE_APPEND
                );
            }
        }
    }
}
