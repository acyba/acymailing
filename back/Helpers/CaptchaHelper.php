<?php

namespace AcyMailing\Helpers;

use AcyMailing\Core\AcymObject;

class CaptchaHelper extends AcymObject
{
    public function display(string $formName = '', bool $loadJsModule = false): void
    {
        if (!acym_level(ACYM_ESSENTIAL)) {
            return;
        }

        $captchaPluginName = $this->config->get('captcha', 'none');
        if ($captchaPluginName === 'none') {
            return;
        }

        $id = empty($formName) ? 'acym-captcha' : $formName.'-captcha';

        if (in_array($captchaPluginName, ['acym_ireCaptcha', 'acym_reCaptcha_v3'])) {
            $pubkey = $this->config->get('recaptcha_sitekey', '');
            if (empty($pubkey)) {
                return;
            }

            if ($captchaPluginName === 'acym_ireCaptcha') {
                $jsScript = 'https://www.google.com/recaptcha/api.js?render=explicit&hl='.acym_getLanguageTag(true);
            } else {
                $jsScript = 'https://www.google.com/recaptcha/api.js?render='.acym_escape($pubkey);
            }

            if ($loadJsModule) {
                // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Option to include the JS inside the module.
                echo '<script src="'.acym_escapeUrl($jsScript).'" type="text/javascript" defer async></script>';
            } else {
                acym_addScript(false, $jsScript, ['defer' => true, 'async' => true]);
            }

            echo '<div id="'.acym_escape($id).'" 
                    data-size="invisible" 
                    class="acyg-recaptcha" 
                    data-sitekey="'.acym_escape($pubkey).'" 
                    data-captchaname="'.acym_escape($captchaPluginName).'"></div>';
        } elseif ($captchaPluginName === 'acym_hcaptcha') {
            $siteKey = $this->config->get('hcaptcha_sitekey');
            echo '<div class="h-captcha" data-sitekey="'.acym_escape($siteKey).'"></div>';
            $jsScript = 'https://js.hcaptcha.com/1/api.js';

            if ($loadJsModule) {
                // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Option to include the JS inside the module.
                echo '<script src="'.acym_escapeUrl($jsScript).'" type="text/javascript" defer async></script>';
            } else {
                acym_addScript(false, $jsScript, ['defer' => true, 'async' => true]);
            }
        } else {
            acym_loadCaptcha($captchaPluginName, $id);
        }
    }

    public function check(): bool
    {
        if (!acym_level(ACYM_ESSENTIAL)) {
            return true;
        }

        $captchaPluginName = $this->config->get('captcha', 'none');
        if ($captchaPluginName === 'none') {
            return true;
        }

        // The security key can be used for direct subscription links
        $secKey = acym_getVar('string', 'seckey', 'none');
        $securityKey = $this->config->get('security_key');
        if (!empty($securityKey) && hash_equals((string)$securityKey, (string)$secKey)) {
            return true;
        }

        if (in_array($captchaPluginName, ['acym_ireCaptcha', 'acym_reCaptcha_v3'])) {
            $privatekey = $this->config->get('recaptcha_secretkey', '');
            $response = acym_getVar('string', 'g-recaptcha-response', '');
            $remoteip = acym_getVar('string', 'REMOTE_ADDR', '', 'SERVER');
            if (empty($privatekey) || $response === '' || empty($remoteip)) {
                return false;
            }

            $url = 'https://www.google.com/recaptcha/api/siteverify?secret='.urlencode(stripslashes($privatekey));
            $url .= '&remoteip='.urlencode(stripslashes($remoteip));
            $url .= '&response='.urlencode(stripslashes($response));
            $getResponse = acym_fileGetContent($url);

            $answers = json_decode($getResponse, true);

            if (!is_array($answers) || empty($answers['success'])) {
                return false;
            }

            if ($captchaPluginName === 'acym_ireCaptcha') {
                return trim($answers['success']) !== '';
            } else {
                $score = $this->config->get('recaptcha_score', 0.5);

                return trim($answers['success']) == true && $answers['score'] >= $score;
            }
        } elseif ($captchaPluginName === 'acym_hcaptcha') {
            $data = [
                'secret' => $this->config->get('hcaptcha_secretkey'),
                'response' => acym_getVar('string', 'h-captcha-response'),
            ];

            $responseData = acym_makeCurlCall(
                'https://hcaptcha.com/siteverify',
                ['data' => $data, 'method' => 'POST']
            );

            return !empty($responseData['success']);
        } else {
            $response = acym_getVar('string', 'acym-captcha', '');

            return acym_checkCaptcha($captchaPluginName, $response);
        }
    }
}
