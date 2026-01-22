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
            'giphy_key',
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

                if (!empty($message)) {
                    $notification = [
                        'name' => 'curl_error',
                        'removable' => 1,
                    ];
                    acym_enqueueMessage(acym_translationSprintf('ACYM_CURL_ERROR_MESSAGE', $message), 'error', true, [$notification]);
                }

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

    public function seeLogs(): void
    {
        $filename = acym_getVar('string', 'filename', '');

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
        // Step 1: prompt consent window to the user
        $this->store();

        $redirectUrl = '';
        acym_trigger('onAcymOauthAuthenticate', [&$redirectUrl, $isSmtp]);

        if (empty($redirectUrl)) {
            $this->listing();

            return;
        }

        acym_redirect($redirectUrl);
    }

    public function handleOauthAuthentication(): void
    {
        // Step 2: get the access and refresh tokens after the user is redirected to the config with a code in the URL
        $code = acym_getVar('string', 'code');

        if (empty($code)) {
            return;
        }

        $isSmtp = acym_getVar('string', 'auth_type', 'smtp') === 'smtp';
        acym_trigger('onAcymOauthCredentialsCreation', [$isSmtp, $code]);
    }
}
