<?php

function acym_makeCurlCall($url, $fields, $headers = [])
{
    $urlPost = '';
    if (!empty($fields)) {
        foreach ($fields as $key => $value) {
            $urlPost .= $key.'='.urlencode($value).'&';
        }

        $urlPost = trim($urlPost, '&');
    }

    //open connection
    $ch = curl_init();

    //set the url, number of POST vars, POST data
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $urlPost);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

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

function acym_asyncCurlCall($urls)
{
    if (!function_exists('curl_multi_exec')) return;

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
        do {
            curl_multi_exec($mh, $running);
            usleep(100);
        } while ($running);

        foreach ($handles as $handle) {
            curl_multi_remove_handle($mh, $handle);
        }
        curl_multi_close($mh);
    } catch (Exception $exception) {
        $reportPath = $this->config->get('cron_savepath');
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
