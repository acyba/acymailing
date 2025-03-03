<?php

namespace AcyMailing\Controllers;

use AcyMailing\Controllers\Configuration\Language;
use AcyMailing\Core\AcymController;
use AcyMailing\Controllers\Configuration\Listing;
use AcyMailing\Controllers\Configuration\Security;
use AcyMailing\Controllers\Configuration\Mail;
use AcyMailing\Controllers\Configuration\Queue;
use AcyMailing\Controllers\Configuration\Subscription;
use AcyMailing\Controllers\Configuration\License;

class ConfigurationController extends AcymController
{
    use Listing;
    use Security;
    use Mail;
    use Queue;
    use Subscription;
    use License;
    use Language;

    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_CONFIGURATION')] = acym_completeLink('configuration');

        $this->loadScripts = [
            'listing' => [
                'colorpicker',
            ],
        ];
    }

    public function getAjax(): void
    {
        acym_checkToken();

        $field = acym_getVar('string', 'field', '');

        $whitelistedFields = [
            'level',
            'unsplash_key',
            'tenor_key',
        ];

        if (acym_isAdmin()) {
            $whitelistedFields[] = 'save_thumbnail';
        }

        if (!in_array($field, $whitelistedFields)) {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_LOAD_INFORMATION'), [], false);
        }

        $res = $this->config->get($field, '');

        if (intval($res) !== 0 && empty($res)) {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_LOAD_INFORMATION'), [], false);
        } else {
            acym_sendAjaxResponse('', ['value' => $res]);
        }
    }

    public function displayMessage(string $message, bool $ajax = false): array
    {
        $correspondences = [
            'WEBSITE_NOT_FOUND' => ['message' => 'ACYM_WEBSITE_NOT_FOUND', 'type' => 'error'],
            'LICENSE_NOT_FOUND' => ['message' => 'ACYM_LICENSE_NOT_FOUND', 'type' => 'error'],
            'WELL_ATTACH' => ['message' => 'ACYM_LICENSE_WELL_ATTACH', 'type' => 'info'],
            'ISSUE_WHILE_ATTACH' => ['message' => 'ACYM_ISSUE_WHILE_ATTACHING_LICENSE', 'type' => 'error'],
            'ALREADY_ATTACH' => ['message' => 'ACYM_WEBSITE_ALREADY_ATTACHED', 'type' => 'info'],
            'LICENSES_DONT_MATCH' => ['message' => 'ACYM_CANT_UNLINK_WEBSITE_LICENSE_DONT_MATCH', 'type' => 'error'],
            'MAX_SITES_ATTACH' => ['message' => 'ACYM_REACHED_MAX_SITES_ATTACHED', 'type' => 'error'],
            'SITE_NOT_FOUND' => ['message' => 'ACYM_ISSUE_WHILE_ATTACHING_LICENSE', 'type' => 'error'],
            'UNLINK_SUCCESSFUL' => ['message' => 'ACYM_LICENSE_UNLINK_SUCCESSFUL', 'type' => 'info'],
            'UNLINK_FAILED' => ['message' => 'ACYM_ERROR_WHILE_UNLINK_LICENSE', 'type' => 'error'],
            'CRON_WELL_ACTIVATED' => ['message' => 'ACYM_AUTOMATIC_SEND_PROCESS_WELL_ACTIVATED', 'type' => 'info'],
            'CRON_WELL_DEACTIVATED' => ['message' => 'ACYM_AUTOMATIC_SEND_PROCESS_WELL_DEACTIVATED', 'type' => 'info'],
            'CRON_NOT_SAVED' => ['message' => 'ACYM_AUTOMATIC_SEND_PROCESS_NOT_ENABLED', 'type' => 'error'],
        ];

        if (!$ajax) {
            if (empty($message) || empty($correspondences[$message])) {
                acym_enqueueMessage(acym_translation('ACYM_ERROR_ON_CALL_ACYBA_WEBSITE'), 'error');

                if (!empty($message)) acym_enqueueMessage(acym_translationSprintf('ACYM_CURL_ERROR_MESSAGE', $message), 'error');

                return [];
            }

            acym_enqueueMessage(acym_translation($correspondences[$message]['message']), $correspondences[$message]['type']);

            return $correspondences[$message]['type'] === 'info' ? $correspondences[$message] : [];
        } else {
            if (empty($message) || empty($correspondences[$message])) {
                $response = ['message' => acym_translation('ACYM_ERROR_ON_CALL_ACYBA_WEBSITE'), 'type' => 'error'];

                if (!empty($message)) $response['message'] = acym_translationSprintf('ACYM_CURL_ERROR_MESSAGE', $message);

                return $response;
            }

            $response = $correspondences[$message];
            $response['message'] = acym_translation($response['message']);

            return $response;
        }
    }

    function seeLogs(): void
    {
        $filename = acym_getVar('string', 'filename');

        if (empty($filename) || !acym_fileNameValid($filename)) {
            echo acym_translation('ACYM_FILENAME_EMPTY_OR_NOT_VALID');
            exit;
        }

        $reportPath = acym_getLogPath($filename);

        if (!file_exists($reportPath)) {
            echo acym_translation('ACYM_EXIST_LOG');
            exit;
        }

        if (ACYM_CMS === 'wordpress') @ob_get_clean();

        $final = acym_fileGetContent($reportPath);
        echo nl2br($final);

        exit;
    }

    private function loginForOAuth2(bool $isSmtp = true): void
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
            'imap.gmail.com' => [
                'baseUrl' => 'https://accounts.google.com/o/oauth2/v2/auth?access_type=offline&prompt=consent&',
                'scope' => 'https%3A%2F%2Fmail.google.com%2F',
            ],
            'outlook.office365.com' => [
                'baseUrl' => 'https://login.microsoftonline.com/%s/oauth2/v2.0/authorize?',
                'scope' => 'offline_access+https%3A%2F%2Foutlook.office.com%2FIMAP.AccessAsUser.All',
            ],
        ];

        $this->store();

        $this->config->save(['oauth_auth_type' => $isSmtp ? 'smtp' : 'bounce']);

        $host = strtolower($this->config->get($isSmtp ? 'smtp_host' : 'bounce_server'));
        $clientId = $this->config->get($isSmtp ? 'smtp_clientId' : 'bounce_client_id');
        $clientSecret = $this->config->get($isSmtp ? 'smtp_secret' : 'bounce_client_secret');
        if ($isSmtp) {
            $redirect_url = $this->config->get('smtp_redirectUrl');
        } else {
            $redirect_url = acym_baseURI();
        }

        if (empty($clientId) || empty($clientSecret) || empty($host) || !array_key_exists($host, $auth2Smtp)) {
            $this->listing();

            return;
        }

        if (in_array($host, ['smtp.office365.com', 'smtp-mail.outlook.com', 'outlook.office365.com'])) {
            $tenant = $this->config->get($isSmtp ? 'smtp_tenant' : 'bounce_tenant', 'consumers');
            if (empty($tenant)) {
                acym_enqueueMessage(acym_translation('ACYM_TENANT_FIELD_IS_MISSING'), 'error');
                $this->listing();

                return;
            }
            $auth2Smtp[$host]['baseUrl'] = sprintf($auth2Smtp[$host]['baseUrl'], $tenant);
        }

        $redirectLink = $auth2Smtp[$host]['baseUrl'];
        $redirectLink .= 'client_id='.urlencode($clientId);
        $redirectLink .= '&response_type=code';
        $redirectLink .= '&redirect_uri='.urlencode($redirect_url);
        $redirectLink .= '&scope='.$auth2Smtp[$host]['scope'];
        $redirectLink .= '&state=acymailing';

        acym_redirect($redirectLink);
    }

    public function getAccessToken(): void
    {
        $isBounce = $this->config->get('oauth_auth_type', 'bounce') == 'bounce';
        $code = acym_getVar('string', 'code');
        $clientId = trim($this->config->get($isBounce ? 'bounce_client_id' : 'smtp_clientId'));
        $secret = trim($this->config->get($isBounce ? 'bounce_client_secret' : 'smtp_secret'));
        $host = strtolower(trim($this->config->get($isBounce ? 'bounce_server' : 'smtp_host')));
        if ($isBounce) {
            $redirectUrl = acym_baseURI();
        } else {
            $smtpRedirectUrl = trim($this->config->get('smtp_redirectUrl'));
            $redirectUrl = empty($smtpRedirectUrl) ? acym_baseURI() : $smtpRedirectUrl;
        }
        $scope = '';

        if (empty($clientId) || empty($secret) || empty($code)) {
            return;
        }

        if (in_array($host, ['imap.gmail.com', 'smtp.gmail.com'])) {
            $url = 'https://oauth2.googleapis.com/token';
        } else {
            $tenant = trim($this->config->get($isBounce ? 'bounce_tenant' : 'smtp_tenant', 'consumers'));
            if (empty($tenant)) {
                acym_enqueueMessage(acym_translation('ACYM_TENANT_FIELD_IS_MISSING'), 'error');
            }
            $url = 'https://login.microsoftonline.com/'.$tenant.'/oauth2/v2.0/token';
            $scope = 'https://outlook.office.com/IMAP.AccessAsUser.All';
        }

        $params = ['client_id' => $clientId, 'grant_type' => 'authorization_code', 'client_secret' => $secret, 'code' => $code, 'redirect_uri' => $redirectUrl];

        if (!empty($scope)) {
            $params['scope'] = $scope;
        }

        $requestOption = [
            'method' => 'POST',
            'data' => $params,
        ];

        $response = acym_makeCurlCall($url, $requestOption);

        acym_logError('Response from OAuth call: '.json_encode($response), 'imap_oauth');

        if (empty($response['error'])) {
            $token = $response['token_type'].' '.$response['access_token'];
            $expireIn = time() + (int)$response['expires_in'];

            if ($isBounce) {
                $config = ['bounce_token' => $token, 'bounce_token_expireIn' => $expireIn];
            } else {
                $config = ['smtp_token' => $token, 'smtp_token_expireIn' => $expireIn];
            }

            if (!empty($response['refresh_token'])) {
                if ($isBounce) {
                    $config['bounce_refresh_token'] = $response['refresh_token'];
                } else {
                    $config['smtp_refresh_token'] = $response['refresh_token'];
                }
            }

            $config['oauth_auth_type'] = '';
            $this->config->save($config);

            acym_enqueueMessage(acym_translation('ACYM_SMTP_OAUTH_OK'), 'info');
        } else {
            acym_enqueueMessage(acym_translationSprintf('ACYM_SMTP_OAUTH_ERROR', $response['error']), 'error', false);
        }
    }
}
