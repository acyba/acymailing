<?php

namespace AcyMailing\Controllers\Configuration;

use AcyMailing\Helpers\ExportHelper;

trait Mail
{
    public function ports()
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

    public function testCredentialsSendingMethod()
    {
        $sendingMethod = acym_getVar('string', 'sendingMethod', '');
        $config = acym_getVar('array', 'config', []);

        if (empty($sendingMethod) || empty($config)) acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_FIND_SENDING_METHOD'), [], false);
        acym_trigger('onAcymTestCredentialSendingMethod', [$sendingMethod, $config]);
    }

    public function copySettingsSendingMethod()
    {
        $plugin = acym_getVar('string', 'plugin', '');
        $method = acym_getVar('string', 'method', '');

        if (empty($plugin) || empty($method)) acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_RETRIEVE_DATA'), [], false);

        $data = [];

        if ($method == 'from_options') {
            $wpMailSmtpSetting = get_option('wp_mail_smtp', '');
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

        if (empty($data)) acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_RETRIEVE_DATA'), [], false);

        acym_sendAjaxResponse('', $data);
    }

    public function synchronizeExistingUsers()
    {
        $sendingMethod = acym_getVar('string', 'sendingMethod', '');

        if (empty($sendingMethod)) acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_FIND_SENDING_METHOD'), [], false);
        acym_trigger('onAcymSynchronizeExistingUsers', [$sendingMethod]);
    }

    public function downloadExportChangesFile()
    {
        $current = acym_getVar('boolean', 'export_changes_file_current', true);
        $dateTime = $current ? 'now' : '1 month ago';

        $exportHelper = new ExportHelper();

        $filenameToSearch = $exportHelper->getExportChangesFileName(acym_date($dateTime, 'Y'), acym_date($dateTime, 'm'), false);

        $exportFolder = acym_getLogPath('');
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

    public function loginForAuth2()
    {
        $auth2Smtp = [
            'smtp.gmail.com' => [
                'baseUrl' => 'https://accounts.google.com/o/oauth2/v2/auth?access_type=offline&prompt=consent&',
                'scope' => 'https%3A%2F%2Fmail.google.com%2F',
            ],
            'smtp-mail.outlook.com' => [
                'baseUrl' => 'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize?response_mode=query&',
                'scope' => 'openid%20offline_access%20https%3A%2F%2Fgraph.microsoft.com%2Fmail.read%20https%3A%2F%2Foutlook.office.com%2FSMTP.Send',
            ],
            'smtp.office365.com' => [
                'baseUrl' => 'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize?response_mode=query&',
                'scope' => 'openid%20offline_access%20https%3A%2F%2Fgraph.microsoft.com%2Fmail.read%20https%3A%2F%2Foutlook.office.com%2FSMTP.Send',
            ],
        ];

        $this->store();

        $smtpHost = strtolower($this->config->get('smtp_host'));
        $clientId = $this->config->get('smtp_clientId');
        $clientSecret = $this->config->get('smtp_secret');
        $redirect_url = $this->config->get('smtp_redirectUrl');

        if (empty($clientId) || empty($clientSecret) || empty($smtpHost) || !array_key_exists($smtpHost, $auth2Smtp)) {
            $this->listing();

            return;
        }

        if (in_array($smtpHost, ['smtp.office365.com', 'smtp-mail.outlook.com'])) {
            $tenant = $this->config->get('smtp_tenant');
            if (empty($tenant)) {
                acym_enqueueMessage(acym_translation('ACYM_TENANT_FIELD_IS_MISSING'), 'error');
                $this->listing();

                return;
            }
            $auth2Smtp[$smtpHost]['baseUrl'] = sprintf($auth2Smtp[$smtpHost]['baseUrl'], $tenant);
        }

        $redirectLink = $auth2Smtp[$smtpHost]['baseUrl'];
        $redirectLink .= 'client_id='.urlencode($clientId);
        $redirectLink .= '&response_type=code';
        $redirectLink .= '&redirect_uri='.urlencode($redirect_url);
        $redirectLink .= '&scope='.$auth2Smtp[$smtpHost]['scope'];
        $redirectLink .= '&state=acymailing';

        acym_redirect($redirectLink);
    }
}
