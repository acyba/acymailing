<?php

use AcyMailing\Libraries\acymPlugin;

class plgAcymMailgun extends acymPlugin
{
    const SENDING_METHOD_ID = 'mailgun';
    const SENDING_METHOD_NAME = 'Mailgun';
    const SENDING_METHOD_API_URL_US = 'https://api.mailgun.net/v3/';
    const SENDING_METHOD_API_URL_EU = 'https://api.eu.mailgun.net/v3/';

    public $sendingMethodApiUrl;

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = self::SENDING_METHOD_NAME;
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/mailgun.svg',
            'image_class' => 'acym__selection__card__image__mailgun',
        ];
    }

    public function onAcymGetSendingMethodsHtmlSetting(&$data)
    {
        $regions = [
            'us' => acym_translation('ACYM_US'),
            'eu' => acym_translation('ACYM_EU'),
        ];
        ob_start();
        ?>
		<div class="send_settings cell grid-x acym_vcenter" id="<?php echo self::SENDING_METHOD_ID; ?>_settings">
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label class="cell large-3 medium-4 margin-right-1">
                    <?php
                    echo acym_translationSprintf('ACYM_SENDING_METHOD_API_REGION', self::SENDING_METHOD_NAME);
                    echo acym_info(acym_translationSprintf('ACYM_SENDING_METHOD_API_REGION_DESC', self::SENDING_METHOD_NAME)) ?>
				</label>
                <?php
                echo acym_radio(
                    $regions,
                    'config['.self::SENDING_METHOD_ID.'_api_region]',
                    $this->config->get(self::SENDING_METHOD_ID.'_api_region', 'us')
                );
                ?>
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label class="cell" for="<?php echo self::SENDING_METHOD_ID; ?>_settings_api-domain">
                    <?php echo acym_translationSprintf(
                        'ACYM_SENDING_METHOD_API_DOMAIN',
                        self::SENDING_METHOD_NAME
                    ); ?>
				</label>
				<input type="text"
					   id="<?php echo self::SENDING_METHOD_ID; ?>_settings_api-domain"
					   value="<?php echo empty($data['tab']->config->values[self::SENDING_METHOD_ID.'_api_domain']) ? '' : $data['tab']->config->values[self::SENDING_METHOD_ID.'_api_domain']->value; ?>"
					   name="config[<?php echo self::SENDING_METHOD_ID; ?>_api_domain]"
					   class="cell acym__configuration__mail__settings__text">
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label class="cell shrink margin-right-1" for="<?php echo self::SENDING_METHOD_ID; ?>_settings_api-key">
                    <?php echo acym_translationSprintf(
                        'ACYM_SENDING_METHOD_API_KEY',
                        self::SENDING_METHOD_NAME
                    ); ?>
				</label>
                <?php echo $this->getLinks('https://signup.mailgun.com/new/signup', 'https://www.mailgun.com/pricing/'); ?>
				<input type="text"
					   id="<?php echo self::SENDING_METHOD_ID; ?>_settings_api-key"
					   value="<?php echo empty($data['tab']->config->values[self::SENDING_METHOD_ID.'_api_key']) ? '' : $data['tab']->config->values[self::SENDING_METHOD_ID.'_api_key']->value; ?>"
					   name="config[<?php echo self::SENDING_METHOD_ID; ?>_api_key]"
					   class="cell acym__configuration__mail__settings__text">
                <?php echo $this->getTestCredentialsSendingMethodButton(self::SENDING_METHOD_ID); ?>
                <?php echo $this->getCopySettingsButton($data, self::SENDING_METHOD_ID, 'wp_mail_smtp'); ?>
			</div>
		</div>
        <?php
        $data['sendingMethodsHtmlSettings'][self::SENDING_METHOD_ID] = ob_get_clean();
    }

    public function onAcymTestCredentialSendingMethod($sendingMethod, $credentials)
    {
        if ($sendingMethod !== self::SENDING_METHOD_ID) return;

        $this->setSendingMethodApiUrl($credentials);
        $authentication = $this->getAuthenticationSendingMethod(self::SENDING_METHOD_ID, $credentials);
        $response = $this->callApiSendingMethod($this->sendingMethodApiUrl.'log', [], [], 'GET', $authentication);

        if (empty($response)) {
            $errorMsg = acym_translation('ACYM_NO_ANSWER');
            acym_sendAjaxResponse(acym_translationSprintf('ACYM_ERROR_OCCURRED_WHILE_CALLING_API', $errorMsg), [], false);
        } elseif (!empty($response['error_curl'])) {
            acym_sendAjaxResponse(acym_translationSprintf('ACYM_ERROR_OCCURRED_WHILE_CALLING_API', $response['error_curl']), [], false);
        } elseif (!empty($response['message']) && $response['message'] == 'Invalid private key') {
            acym_sendAjaxResponse(acym_translation('ACYM_AUTHENTICATION_FAILS_WITH_API_KEY'), [], false);
        } elseif (!empty($response['message'])) {
            acym_sendAjaxResponse(acym_translation('ACYM_AUTHENTICATION_FAILS_WITH_DOMAIN_REGION'), [], false);
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_API_KEY_CORRECT'));
        }
    }

    public function onAcymSendEmail(&$response, $sendingMethod, $to, $subject, $from, $reply_to, $body, $bcc = [], $attachments = [])
    {
        //https://documentation.mailgun.com/en/latest/user_manual.html#sending-via-api
        if ($sendingMethod != self::SENDING_METHOD_ID) return;

        $this->setSendingMethodApiUrl();
        $headers = $this->getHeadersSendingMethod(self::SENDING_METHOD_ID);
        $authentication = $this->getAuthenticationSendingMethod(self::SENDING_METHOD_ID);
        $fromData = $from['email'];
        $toData = $to['email'];
        if ($this->config->get('add_names', 1) == 1) {
            if (!empty($from['name'])) $fromData = $from['name'].' <'.$fromData.'>';
            if (!empty($to['name'])) $toData = $to['name'].' <'.$toData.'>';
        }
        $data = [
            'from' => $fromData,
            'to' => $toData,
            'subject' => $subject,
            'html' => $body,
        ];
        if (!empty($bcc)) $data['bcc'] = $bcc[0][0];

        if (!empty($attachments)) {
            $data['attachment'] = curl_file_create($attachments[0][0]);
        }

        $responseMailer = $this->callApiSendingMethod($this->sendingMethodApiUrl.'messages', $data, $headers, 'POST', $authentication, true);

        if (empty($responseMailer['message']) || empty($responseMailer['id']) || $responseMailer['message'] != 'Queued. Thank you.') {
            $response['error'] = true;
            $response['message'] = $responseMailer['message'];
        } else {
            $response['error'] = false;
        }
    }

    private function setSendingMethodApiUrl($credentials = [])
    {
        if (empty($credentials)) $this->onAcymGetCredentialsSendingMethod($credentials, self::SENDING_METHOD_ID);

        $this->sendingMethodApiUrl = self::SENDING_METHOD_API_URL_US;
        if ($credentials[self::SENDING_METHOD_ID.'_api_region'] === 'eu') {
            $this->sendingMethodApiUrl = self::SENDING_METHOD_API_URL_EU;
        }
        if (!empty($credentials[self::SENDING_METHOD_ID.'_api_domain'])) {
            $this->sendingMethodApiUrl .= $credentials[self::SENDING_METHOD_ID.'_api_domain'].'/';
        }
    }

    public function onAcymGetCredentialsSendingMethod(&$credentials, $sendingMethod)
    {
        if ($sendingMethod != self::SENDING_METHOD_ID) return;

        $credentials = [
            self::SENDING_METHOD_ID.'_api_key' => $this->config->get(self::SENDING_METHOD_ID.'_api_key', ''),
            self::SENDING_METHOD_ID.'_api_domain' => $this->config->get(self::SENDING_METHOD_ID.'_api_domain', ''),
            self::SENDING_METHOD_ID.'_api_region' => $this->config->get(self::SENDING_METHOD_ID.'_api_region', 'us'),
        ];
    }

    public function getHeadersSendingMethod($sendingMethod, $credentials = [])
    {
        return ['content-type: multipart/form-data'];
    }

    public function getAuthenticationSendingMethod($sendingMethod, $credentials = [])
    {
        if (empty($credentials)) $this->onAcymGetCredentialsSendingMethod($credentials, $sendingMethod);

        return [
            'name' => 'api',
            'pwd' => $credentials[self::SENDING_METHOD_ID.'_api_key'],
        ];
    }

    public function onAcymSendingMethodEmbedImage(&$data)
    {
        $data['embedImage'][self::SENDING_METHOD_ID] = false;
    }

    public function onAcymGetSettingsSendingMethodFromPlugin(&$data, $plugin, $method)
    {
        if ($method != self::SENDING_METHOD_ID) return;

        //__START__wordpress_
        if (ACYM_CMS == 'wordpress' && $plugin == 'wp_mail_smtp') {
            $wpMailSmtpSetting = get_option('wp_mail_smtp', '');
            if (empty($wpMailSmtpSetting) || empty($wpMailSmtpSetting['mailgun'])) return;

            $settings = $wpMailSmtpSetting['mailgun'];

            if (empty($settings['api_key']) || empty($settings['domain']) || empty($settings['region'])) return;

            $data['mailgun_api_domain'] = $settings['domain'];
            $data['mailgun_api_key'] = $settings['api_key'];
            $data['mailgun_api_region'] = $settings['region'];
        }
        //__END__wordpress_
    }
}
