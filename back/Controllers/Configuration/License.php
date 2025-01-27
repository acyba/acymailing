<?php

namespace AcyMailing\Controllers\Configuration;

use AcyMailing\Helpers\UpdatemeHelper;

trait License
{
    public function unlinkLicense()
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return true;
        }
        //__END__demo_
        $config = acym_getVar('array', 'config', []);
        $licenseKey = empty($config['license_key']) ? $this->config->get('license_key') : $config['license_key'];

        $resultUnlinkLicenseOnUpdateMe = $this->unlinkLicenseOnUpdateMe($licenseKey);

        if ($resultUnlinkLicenseOnUpdateMe['success'] === true) {
            $this->config->save(['license_key' => '']);
        }

        if (!empty($resultUnlinkLicenseOnUpdateMe['message'])) {
            $this->displayMessage($resultUnlinkLicenseOnUpdateMe['message']);
        }

        //Display the configuration
        $this->listing();

        return true;
    }

    public function attachLicense()
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return true;
        }
        //__END__demo_
        $config = acym_getVar('array', 'config', []);
        $licenseKey = $config['license_key'];

        if (empty($licenseKey)) {
            $this->displayMessage(acym_translation('ACYM_PLEASE_SET_A_LICENSE_KEY'));
            $this->listing();

            return true;
        }

        //We save the license key
        $this->config->save(['license_key' => $licenseKey]);

        //We call updateme to attach the website to the license
        $resultAttachLicenseOnUpdateMe = $this->attachLicenseOnUpdateMe();

        if ($resultAttachLicenseOnUpdateMe['success'] === false) {
            $this->config->save(['license_key' => '']);
        }

        if (!empty($resultAttachLicenseOnUpdateMe['message'])) {
            $this->displayMessage($resultAttachLicenseOnUpdateMe['message']);
        }

        $this->listing();

        return true;
    }

    public function attachLicenseOnUpdateMe($licenseKey = null)
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return true;
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

        acym_checkVersion();

        //If it's not the result well formatted => don't save the license key and out
        if (empty($resultAttach)) {
            $return['message'] = empty($resultAttach['message']) ? '' : $resultAttach['message'];

            return $return;
        }

        //If there is an error when the website has been attached => don't save the license key in the configuration
        if (!$resultAttach['success']) {
            return $resultAttach;
        }

        acym_trigger('onAcymAttachLicense', [&$licenseKey]);

        return $resultAttach;
    }

    private function unlinkLicenseOnUpdateMe($licenseKey = null)
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return true;
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

        //First let's deactivate the cron
        $this->deactivateCron(false, $licenseKey);

        $data = [
            'domain' => ACYM_LIVE,
        ];

        //Call updateme to unlink the license from this website
        $resultUnlink = UpdatemeHelper::call('api/websites/unlink', 'POST', $data);

        acym_checkVersion();

        //If it's not the result well formated => out
        if (empty($resultUnlink)) {
            $return['message'] = empty($resultUnlink['message']) ? '' : $resultUnlink['message'];

            return $return;
        }

        if (!$resultUnlink['success']) {
            return $resultUnlink;
        }

        acym_trigger('onAcymDetachLicense');

        return $resultUnlink;
    }

    public function activateCron($licenseKey = null)
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return true;
        }
        //__END__demo_
        $result = $this->modifyCron('activateCron', $licenseKey);
        //If everything went ok we save config with an active_cron to true
        if ($result !== false && $this->displayMessage($result['message'])) $this->config->save(['active_cron' => 1]);
        $this->listing();

        return true;
    }

    //The listing parameter allows us to know if we need to display the listing or not
    public function deactivateCron($listing = true, $licenseKey = null)
    {
        //__START__demo_
        if (!ACYM_PRODUCTION) {
            $this->listing();

            return true;
        }
        //__END__demo_
        $result = $this->modifyCron('deactivateCron', $licenseKey);
        //If everything went ok we save config with an active_cron to false
        if ($result !== false && $this->displayMessage($result['message'])) $this->config->save(['active_cron' => 0]);
        if ($listing) $this->listing();

        return true;
    }

    //The listing parameter allows us to know if we need to display the listing or not
    public function modifyCron(string $functionToCall, ?string $licenseKey = null)
    {
        if (is_null($licenseKey)) {
            $config = acym_getVar('array', 'config', []);
            $licenseKey = empty($config['license_key']) ? '' : $config['license_key'];
        }

        //If the license is not set => out
        if (empty($licenseKey)) {
            $this->displayMessage('LICENSE_NOT_FOUND');

            return false;
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

            return false;
        }

        return $result;
    }

    public function call($task, $allowedTasks = [])
    {
        $allowedTasks[] = 'markNotificationRead';
        $allowedTasks[] = 'removeNotification';
        $allowedTasks[] = 'getAjax';
        $allowedTasks[] = 'addNotification';

        parent::call($task, $allowedTasks);
    }

    public function attachLicenseAcymailer()
    {
        $acyMailerLicenseKey = $this->config->get('acymailer_apikey', '');
        $acyMailingKey = $this->config->get('license_key', '');
        if (empty($acyMailerLicenseKey) && !empty($acyMailingKey)) {
            acym_trigger('onAcymAttachLicense', [&$acyMailingKey]);
        }
        $this->config->load();
        $acyMailerLicenseKey = $this->config->get('acymailer_apikey', '');
        if (empty($acyMailerLicenseKey)) {
            acym_enqueueMessage(acym_translation('ACYM_LICENCE_NO_SENDING_SERVICE'), 'error');
        } else {
            $this->config->save(['mailer_method' => 'acymailer']);
            acym_enqueueMessage(acym_translation('ACYM_SENDING_SERVICE_ACTIVATED'), 'success', false);
        }
        $this->listing();
    }
}
