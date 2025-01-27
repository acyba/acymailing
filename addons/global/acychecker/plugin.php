<?php

use AcyMailing\Core\AcymPlugin;
use AcyChecker\Classes\ConfigurationClass;
use AcyChecker\Services\ApiService;

class plgAcymAcychecker extends AcymPlugin
{
    private function loadAcychecker()
    {
        if (ACYM_CMS === 'joomla') {
            $cteFolder = rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_acychecker'.DS;
        } else {
            $cteFolder = WP_PLUGIN_DIR.DS.'acychecker'.DS;
        }
        include_once $cteFolder.'vendor'.DS.'autoload.php';
        include_once $cteFolder.'defines.php';
    }

    public function onBeforeSaveConfigFields(&$formData)
    {
        if (!isset($formData['email_verification'])) return;
        if (!acym_isAcyCheckerInstalled()) return;
        $this->loadAcychecker();

        $cteConfig = new ConfigurationClass();
        $registrationIntegrations = explode(',', $cteConfig->get('registration_integrations'));
        if (empty($formData['email_verification'])) {
            if (in_array('acymailing', $registrationIntegrations)) {
                unset($registrationIntegrations[array_search('acymailing', $registrationIntegrations)]);

                $cteConfig->save(
                    [
                        'registration_integrations' => implode(',', $registrationIntegrations),
                    ]
                );
            }
        } else {
            if (!in_array('acymailing', $registrationIntegrations)) {
                $registrationIntegrations[] = 'acymailing';
            }

            $verificationOptions = [
                'email_verification_non_existing' => 'invalid_smtp',
                'email_verification_disposable' => 'disposable',
                'email_verification_free' => 'free_domain',
                'email_verification_role' => 'role_based',
                'email_verification_acceptall' => 'accept_all',
                'email_checkdomain' => 'domain_not_exists',
            ];
            $registrationConditions = [];
            $newConfig = [];
            $acy5installed = false;
            if (ACYM_CMS === 'joomla') {
                if (acym_isExtensionActive('com_acymailing')) {
                    include_once rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_acymailing'.DS.'helpers'.DS.'helper.php';
                    $acy5installed = true;
                }
            } else {
                if (acym_isExtensionActive('acymailing5/index.php')) {
                    include_once WP_PLUGIN_DIR.DS.'acymailing5'.DS.'back'.DS.'helpers'.DS.'helper.php';
                    $acy5installed = true;
                }
            }
            if ($acy5installed) {
                $acym5Config = acymailing_config();
            }

            foreach ($verificationOptions as $acymOption => $acycOption) {
                if (!empty($formData[$acymOption])) {
                    $registrationConditions[] = $acycOption;
                    $newConfig[$acymOption] = 1;
                } else {
                    $newConfig[$acymOption] = 0;
                }
            }
            if ($acy5installed) {
                $acym5Config->save($newConfig);
            }

            $cteConfig->save(
                [
                    'registration_integrations' => trim(implode(',', $registrationIntegrations), ','),
                    'registration_conditions' => trim(implode(',', $registrationConditions), ','),
                ]
            );
        }
    }

    public function onAcymBeforeUserCreate(&$user)
    {
        // CTE isn't installed
        if (!acym_isAcyCheckerInstalled()) return true;

        // The email verification is disabled in the configuration
        if ($this->config->get('email_verification') == 0) return true;

        $this->loadAcychecker();

        $cteConfig = new ConfigurationClass();
        $conditions = $cteConfig->get('registration_conditions');

        // If no condition is selected, return
        if (empty($conditions) || $conditions === 'domain_not_exists') return true;

        // Perform test using CTE code API
        $apiService = new ApiService();
        $emailOk = $apiService->testEmail($user->email, $conditions);
        if ($emailOk !== true) {
            acym_setVar('acychecker_error', acym_translation('ACYM_INVALID_EMAIL_ADDRESS'));

            return false;
        }

        return true;
    }
}
