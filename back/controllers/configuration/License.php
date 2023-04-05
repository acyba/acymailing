<?php

namespace AcyMailing\Controllers\Configuration;

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

        $url = ACYM_UPDATEMEURL.'license&task=attachWebsiteKey';

        $fields = [
            'domain' => ACYM_LIVE,
            'license_key' => $licenseKey,
        ];

        $resultAttach = acym_makeCurlCall($url, $fields);

        acym_checkVersion();

        //If it's not the result well formatted => don't save the license key and out
        if (empty($resultAttach) || !empty($resultAttach['error'])) {
            $return['message'] = empty($resultAttach['error']) ? '' : $resultAttach['error'];

            return $return;
        }

        $return['message'] = $resultAttach['message'];
        //If there is an error when the website has been attached => don't save the license key in the configuration
        if ($resultAttach['type'] == 'error') {

            return $return;
        }

        $return['success'] = true;

        acym_trigger('onAcymAttachLicense', [&$licenseKey]);

        return $return;
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

        $level = $this->config->get('level', '');

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

        $url = ACYM_UPDATEMEURL.'license&task=unlinkWebsiteFromLicense';

        $fields = [
            'domain' => ACYM_LIVE,
            'license_key' => $licenseKey,
            'level' => $level,
            'component' => ACYM_COMPONENT_NAME_API,
        ];

        //Call updateme to unlink the license from this website
        $resultUnlink = acym_makeCurlCall($url, $fields);

        acym_checkVersion();

        //If it's not the result well formated => out
        if (empty($resultUnlink) || !empty($resultUnlink['error'])) {
            $return['message'] = empty($resultUnlink['error']) ? '' : $resultUnlink['error'];

            return $return;
        }

        if ($resultUnlink['type'] === 'error') {
            //If we can't retrieve the license, we set that the unlink is ok.
            //Example: if you don't have the license on acymailing.com, you need to unlink the license
            if ($resultUnlink['message'] == 'LICENSE_NOT_FOUND' || $resultUnlink['message'] == 'LICENSES_DONT_MATCH') {
                $return['message'] = 'UNLINK_SUCCESSFUL';
                $return['success'] = true;

                return $return;
            }
        }

        if ($resultUnlink['type'] === 'info') {
            $return['success'] = true;
        }

        $return['message'] = $resultUnlink['message'];

        acym_trigger('onAcymDetachLicense');

        return $return;
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
        //If everything went ok we save config with a active_cron to true
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
        //If everything went ok we save config with a active_cron to false
        if ($result !== false && $this->displayMessage($result['message'])) $this->config->save(['active_cron' => 0]);
        if ($listing) $this->listing();

        return true;
    }

    //The listing parameter allows us to know if we need to display the listing or not
    public function modifyCron($functionToCall, $licenseKey = null)
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

        $url = ACYM_UPDATEMEURL.'launcher&task='.$functionToCall;

        $fields = [
            'domain' => ACYM_LIVE,
            'license_key' => $licenseKey,
            'cms' => ACYM_CMS,
            'frequency' => 900,
            'level' => $this->config->get('level', ''),
            'url_version' => 'secured',
        ];

        //We call updateme to activate/deactivate the cron
        $result = acym_makeCurlCall($url, $fields);


        //If it's not the result well formated => out
        if (empty($result) || !empty($result['error'])) {
            $this->displayMessage(empty($result['error']) ? '' : $result['error']);

            return false;
        }

        //If there is an error during the process on updateme => out
        if ($result['type'] == 'error') {
            $this->displayMessage($result['message']);

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
}
