<?php

namespace AcyMailing\Helpers;

use AcyMailing\Libraries\acymObject;

class CaptchaHelper extends acymObject
{
    public function display($formName = '', $loadJsModule = false)
    {
        $captchaPluginName = $this->config->get('captcha', 'none');
        if ($captchaPluginName === 'none') return '';

        $id = empty($formName) ? 'acym-captcha' : $formName.'-captcha';

        if ($captchaPluginName === 'acym_ireCaptcha') {
            $pubkey = $this->config->get('recaptcha_sitekey', '');
            if (empty($pubkey)) return '';

            $return = '';
            $jsScript = 'https://www.google.com/recaptcha/api.js?render=explicit&hl='.acym_getLanguageTag(true);
            if ($loadJsModule) {
                $return .= '<script src="'.$jsScript.'" type="text/javascript" defer async></script>';
            } else {
                acym_addScript(false, $jsScript, 'text/javascript', true, true);
            }

            return $return.'<div id="'.acym_escape($id).'" data-size="invisible" class="acyg-recaptcha" data-sitekey="'.acym_escape($pubkey).'"></div>';
        } else {
            return acym_loadCaptcha($captchaPluginName, $id);
        }
    }

    public function check()
    {
        $captchaPluginName = $this->config->get('captcha', 'none');
        if ($captchaPluginName === 'none') return true;

        // The security key can be used for direct subscription links
        $secKey = acym_getVar('string', 'seckey', 'none');
        if ($secKey == $this->config->get('security_key')) return true;

        if ($captchaPluginName === 'acym_ireCaptcha') {
            $privatekey = $this->config->get('recaptcha_secretkey', '');
            $response = acym_getVar('string', 'g-recaptcha-response', '');
            $remoteip = acym_getVar('string', 'REMOTE_ADDR', '', 'SERVER');
            if (empty($privatekey) || $response === '' || empty($remoteip)) return false;

            $url = 'https://www.google.com/recaptcha/api/siteverify?secret='.urlencode(stripslashes($privatekey));
            $url .= '&remoteip='.urlencode(stripslashes($remoteip));
            $url .= '&response='.urlencode(stripslashes($response));
            $getResponse = acym_fileGetContent($url);

            $answers = json_decode($getResponse, true);

            return (is_array($answers) && !empty($answers['success']) && trim($answers['success']) !== '');
        } else {
            return acym_checkCaptcha($captchaPluginName);
        }
    }
}
