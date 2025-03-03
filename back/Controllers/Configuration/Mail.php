<?php

namespace AcyMailing\Controllers\Configuration;

use AcyMailing\Helpers\ExportHelper;

trait Mail
{
    public function ports(): void
    {
        if (!function_exists('fsockopen')) {
            echo '<span style="color:red">'.acym_translation('ACYM_FSOCKOPEN').'</span>';
            exit;
        }

        $tests = [
            25 => 'smtp.sendgrid.com',
            2525 => 'smtp.sendgrid.com',
            587 => 'smtp.sendgrid.com',
            465 => 'ssl://smtp.gmail.com',
        ];

        foreach ($tests as $port => $server) {
            $fp = @fsockopen($server, $port, $errno, $errstr, 5);
            if ($fp) {
                echo '<span style="color:#3dea91">'.acym_translationSprintf('ACYM_SMTP_AVAILABLE_PORT', $port).'</span><br />';
                fclose($fp);
            } else {
                echo '<span style="color:#ff5259">'.acym_translationSprintf('ACYM_SMTP_NOT_AVAILABLE_PORT', $port, $errno.' - '.acym_utf8Encode($errstr)).'</span><br />';
            }
        }

        exit;
    }

    public function testCredentialsSendingMethod(): void
    {
        $sendingMethod = acym_getVar('string', 'sendingMethod', '');
        $config = acym_getVar('array', 'config', []);

        if (empty($sendingMethod) || empty($config)) acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_FIND_SENDING_METHOD'), [], false);
        acym_trigger('onAcymTestCredentialSendingMethod', [$sendingMethod, $config]);
    }

    public function copySettingsSendingMethod(): void
    {
        $plugin = acym_getVar('string', 'plugin', '');
        $method = acym_getVar('string', 'method', '');

        if (empty($plugin) || empty($method)) {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_RETRIEVE_DATA'), [], false);
        }

        $data = [];

        if ($method === 'from_options') {
            $wpMailSmtpSetting = acym_getCMSConfig('wp_mail_smtp', '');
            if (!empty($wpMailSmtpSetting) && !empty($wpMailSmtpSetting['mail'])) {
                $mailSettings = $wpMailSmtpSetting['mail'];

                if (!empty($mailSettings['from_email']) && !empty($mailSettings['from_name'])) {
                    $data['from_email'] = $mailSettings['from_email'];
                    $data['from_name'] = $mailSettings['from_name'];
                }
            }
        } else {
            acym_trigger('onAcymGetSettingsSendingMethodFromPlugin', [&$data, $plugin, $method]);
        }

        if (empty($data)) {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_RETRIEVE_DATA'), [], false);
        }

        acym_sendAjaxResponse('', $data);
    }

    public function synchronizeExistingUsers(): void
    {
        $sendingMethod = acym_getVar('string', 'sendingMethod', '');

        if (empty($sendingMethod)) {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_FIND_SENDING_METHOD'), [], false);
        }

        acym_trigger('onAcymSynchronizeExistingUsers', [$sendingMethod]);
    }

    public function downloadExportChangesFile(): void
    {
        $current = acym_getVar('boolean', 'export_changes_file_current', true);
        $dateTime = $current ? 'now' : '1 month ago';

        $exportHelper = new ExportHelper();

        $filenameToSearch = $exportHelper->getExportChangesFileName(acym_date($dateTime, 'Y'), acym_date($dateTime, 'm'), false);

        $exportFolder = acym_getLogPath();
        $files = scandir($exportFolder);
        if (empty($files)) {
            acym_enqueueMessage(acym_translation('ACYM_NO_FILE_TO_EXPORT'), 'info');
            $this->listing();

            return;
        }

        $filename = acym_getLogPath($filenameToSearch);

        $zipFiles = [];

        foreach ($files as $file) {
            if (strpos($file, $filenameToSearch) === false) continue;
            $zipFiles[] = [
                'name' => $file,
                'data' => acym_fileGetContent(acym_getLogPath($file)),
            ];
        }

        if (empty($zipFiles)) {
            acym_enqueueMessage(acym_translation('ACYM_NO_FILE_TO_EXPORT'), 'info');
            $this->listing();

            return;
        }

        acym_createArchive($filename, $zipFiles);

        if (ACYM_CMS === 'wordpress') @ob_get_clean();
        $exportHelper->setDownloadHeaders($filenameToSearch, 'zip');
        readfile($filename.'.zip');
        acym_deleteFile($filename.'.zip');

        exit;
    }

    public function loginForOAuth2Bounce(): void
    {
        $this->loginForOAuth2(false);
    }

    public function loginForOAuth2Smtp(): void
    {
        $this->loginForOAuth2();
    }
}
