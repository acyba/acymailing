<?php

class acymcaptchaHelper extends acymObject
{
    public function display($formName = '', $loadJsModule = false)
    {
        $pubkey = $this->config->get('recaptcha_sitekey', '');
        if ($this->config->get('captcha', '') != 1 || empty($pubkey)) return '';

        $return = '';

        if ($loadJsModule) {
            $return .= '<script src="https://www.google.com/recaptcha/api.js?render=explicit&hl='.acym_getLanguageTag(true).'" type="text/javascript" defer async></script>';
        } else {
            acym_addScript(false, 'https://www.google.com/recaptcha/api.js?render=explicit&hl='.acym_getLanguageTag(true), 'text/javascript', true, true);
        }

        $id = empty($formName) ? 'acym-captcha' : $formName.'-captcha';

        $return .= '<div id="'.acym_escape($id).'" data-size="invisible" class="acyg-recaptcha" data-sitekey="'.acym_escape($pubkey).'"></div>';

        return $return;
    }

    public function check()
    {
        $secKey = acym_getVar('string', 'seckey', 'none');
        if ($secKey == $this->config->get('security_key')) return true;

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
    }
}
