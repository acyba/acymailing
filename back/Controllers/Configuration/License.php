<?php

namespace AcyMailing\Controllers\Configuration;

use AcyMailing\Helpers\UpdatemeHelper;

trait License
{
    public function unlinkLicense(): void
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return;
        }
        //__END__demo_
        $config = acym_getVar('array', 'config', []);
        $licenseKey = empty($config['license_key']) ? $this->config->get('license_key') : $config['license_key'];

        $resultUnlinkLicenseOnUpdateMe = $this->unlinkLicenseOnUpdateMe($licenseKey);

        if ($resultUnlinkLicenseOnUpdateMe['success'] === true) {
            $this->config->saveConfig(['license_key' => '']);
            UpdatemeHelper::getLicenseInfo();
        }


        if (!empty($resultUnlinkLicenseOnUpdateMe['message'])) {
            $this->displayMessage($resultUnlinkLicenseOnUpdateMe['message']);
        }

        //Display the configuration
        $this->listing();
    }

    public function attachLicense(): void
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return;
        }
        //__END__demo_

        $config = acym_getVar('array', 'config', []);
        if (empty($config['license_key'])) {
            $this->displayMessage(acym_translation('ACYM_PLEASE_SET_A_LICENSE_KEY'));
            $this->listing();

            return;
        }

        //We save the license key
        $this->config->saveConfig(['license_key' => $config['license_key']]);

        //We call updateme to attach the website to the license
        $resultAttachLicenseOnUpdateMe = $this->attachLicenseOnUpdateMe();

        if ($resultAttachLicenseOnUpdateMe['success'] === false) {
            $this->config->saveConfig(['license_key' => '']);
        } else {
            UpdatemeHelper::getLicenseInfo();
        }

        if (!empty($resultAttachLicenseOnUpdateMe['message'])) {
            $this->displayMessage($resultAttachLicenseOnUpdateMe['message']);
        }

        $this->listing();
    }

    public function attachLicenseOnUpdateMe(?string $licenseKey = null): array
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return ['success' => false];
        }
        //__END__demo_

        //We get the license key saved
        if (is_null($licenseKey)) {
            $licenseKey = $this->config->get('license_key', '');
        }

        $return = [
            'message' => '',
            'success' => false,
        ];

        if (empty($licenseKey)) {
            $return['message'] = 'LICENSE_NOT_FOUND';

            return $return;
        }

        $data = [
            'domain' => ACYM_LIVE,
            'cms' => ACYM_CMS,
            'version' => $this->config->get('version'),
        ];

        $resultAttach = UpdatemeHelper::call('api/websites/attach', 'POST', $data);

        // If it's not the result well formatted => don't save the license key and out
        // If there is an error when the website has been attached => don't save the license key in the configuration
        if (empty($resultAttach) || !$resultAttach['success']) {
            return $return;
        }

        acym_trigger('onAcymAttachLicense', [&$licenseKey]);

        return $resultAttach;
    }

    private function unlinkLicenseOnUpdateMe(?string $licenseKey = null): array
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return ['success' => false];
        }
        //__END__demo_
        //We get the license key saved
        if (is_null($licenseKey)) {
            $licenseKey = $this->config->get('license_key');
        }

        $return = [
            'message' => '',
            'success' => false,
        ];

        if (empty($licenseKey)) {
            $return['message'] = 'LICENSE_NOT_FOUND';

            return $return;
        }

        //First let's deactivate the cron
        $this->deactivateCron(false, $licenseKey);

        $data = [
            'domain' => ACYM_LIVE,
        ];

        //Call updateme to unlink the license from this website
        $resultUnlink = UpdatemeHelper::call('api/websites/unlink', 'POST', $data);

        //If it's not the result well formated => out
        if (empty($resultUnlink) || !$resultUnlink['success']) {
            return $return;
        }

        acym_trigger('onAcymDetachLicense');

        return $resultUnlink;
    }

    public function activateCron(): void
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return;
        }
        //__END__demo_

        $result = $this->modifyCron('activateCron');
        //If everything went ok we save config with an active_cron to true
        if (!empty($result) && !empty($this->displayMessage($result['message']))) {
            $this->config->saveConfig(['active_cron' => 1]);
        }

        $this->listing();
    }

    //The listing parameter allows us to know if we need to display the listing or not
    public function deactivateCron(bool $listing = true, ?string $licenseKey = null): void
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return;
        }
        //__END__demo_

        $result = $this->modifyCron('deactivateCron', $licenseKey);
        //If everything went ok we save config with an active_cron to false
        if (!empty($result) && !empty($this->displayMessage($result['message']))) {
            $this->config->saveConfig(['active_cron' => 0]);
        }

        if ($listing) {
            $this->listing();
        }
    }

    //The listing parameter allows us to know if we need to display the listing or not
    public function modifyCron(string $functionToCall, ?string $licenseKey = null): array
    {
        if (is_null($licenseKey)) {
            $config = acym_getVar('array', 'config', []);
            $licenseKey = empty($config['license_key']) ? '' : $config['license_key'];
        }

        //If the license is not set => out
        if (empty($licenseKey)) {
            $this->displayMessage('LICENSE_NOT_FOUND');

            return [];
        }

        $data = [
            'domain' => ACYM_LIVE,
            'cms' => ACYM_CMS,
            'version' => $this->config->get('version'),
            'level' => $this->config->get('level'),
            'activate' => $functionToCall === 'activateCron',
            'security_key' => $this->config->get('cron_key'),
        ];

        //We call updateme to activate/deactivate the cron
        $result = UpdatemeHelper::call('api/crons/modify', 'POST', $data);

        //If it's not the result well formated => out
        if (empty($result['success'])) {
            $this->displayMessage(empty($result['message']) ? 'CRON_NOT_SAVED' : $result['message']);

            return [];
        }

        return $result;
    }

    public function attachLicenseAcymailer(): void
    {
        $acyMailerLicenseKey = $this->config->get('acymailer_apikey');
        $acyMailingKey = $this->config->get('license_key');
        if (empty($acyMailerLicenseKey) && !empty($acyMailingKey)) {
            acym_trigger('onAcymAttachLicense', [&$acyMailingKey]);
        }

        $this->config->load();
        $acyMailerLicenseKey = $this->config->get('acymailer_apikey');

        if (empty($acyMailerLicenseKey)) {
            acym_enqueueMessage(acym_translation('ACYM_LICENCE_NO_SENDING_SERVICE'), 'error');
        } else {
            $this->config->saveConfig(['mailer_method' => 'acymailer']);
            acym_enqueueMessage(acym_translation('ACYM_SENDING_SERVICE_ACTIVATED'), 'success', false);
        }

        $this->listing();
    }
}
