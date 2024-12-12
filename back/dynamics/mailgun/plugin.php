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
        $config = empty($data['tab']) ? $this->config : $data['tab']->config;
        $defaultDomain = $config->get(self::SENDING_METHOD_ID.'_api_domain');
        $defaultApiKey = $config->get(self::SENDING_METHOD_ID.'_api_key');
        ob_start();
        ?>
		<div class="send_settings cell grid-x acym_vcenter" id="<?php echo self::SENDING_METHOD_ID; ?>_settings">
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label class="cell large-3 medium-4 margin-right-1">
                    <?php
                    echo acym_translationSprintf('ACYM_SENDING_METHOD_API_REGION', self::SENDING_METHOD_NAME);
                    echo acym_info(acym_translationSprintf('ACYM_SENDING_METHOD_API_REGION_DESC', self::SENDING_METHOD_NAME)); ?>
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
					   value="<?php echo empty($defaultDomain) ? $this->config->get(self::SENDING_METHOD_ID.'_api_domain') : $defaultDomain; ?>"
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
					   value="<?php echo empty($defaultApiKey) ? $this->config->get(self::SENDING_METHOD_ID.'_api_key') : $defaultApiKey; ?>"
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
        $headers = $this->getHeadersSendingMethod(self::SENDING_METHOD_ID);
        $authentication = $this->getAuthenticationSendingMethod(self::SENDING_METHOD_ID, $credentials);
        $data = [
            'from' => $this->config->get('from_email'),
            'to' => acym_currentUserEmail(),
            'subject' => 'Test email',
            'html' => 'Test email body',
            'o:testmode' => true,
        ];

        $response = $this->callApiSendingMethod($this->sendingMethodApiUrl.'messages', $data, $headers, 'POST', $authentication, true);

        // If call didn't work, the response is always null
        if (empty($response)) {
            acym_sendAjaxResponse(acym_translation('ACYM_AUTHENTICATION_FAILS_WITH_API_KEY'), [], false);
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_API_KEY_CORRECT'));
        }
    }

    public function onAcymSendEmail(&$response, $mailerHelper, $to, $from, $replyTo, $bcc = [], $attachments = [], $sendingMethodListParams = [])
    {
        //https://documentation.mailgun.com/en/latest/user_manual.html#sending-via-api
        if ($mailerHelper->externalMailer != self::SENDING_METHOD_ID) return;

        $this->setSendingMethodApiUrl();
        $headers = $this->getHeadersSendingMethod(self::SENDING_METHOD_ID);
        $authentication = $this->getAuthenticationSendingMethod(self::SENDING_METHOD_ID, [], $sendingMethodListParams);
        $fromData = $from['email'];
        $replyToData = $replyTo['email'] ?? '';
        $toData = $to['email'];
        if ($this->config->get('add_names', 1) == 1) {
            if (!empty($from['name'])) $fromData = $from['name'].' <'.$fromData.'>';
            if (!empty($replyTo['name']) && !empty($replyToData)) $replyToData = $replyTo['name'].' <'.$replyToData.'>';
            if (!empty($to['name'])) $toData = $to['name'].' <'.$toData.'>';
        }
        $data = [
            'from' => $fromData,
            'to' => $toData,
            'subject' => $mailerHelper->Subject,
            'html' => $mailerHelper->Body,
        ];

        if (!empty($replyToData)) {
            $data['h:Reply-To'] = $replyToData;
        }

        if (!empty($bcc)) {
            foreach ($bcc as $key => $bccEmail) {
                $data['bcc['.$key.']'] = $bccEmail[0];
            }
        }

        if (!empty($attachments)) {
            foreach ($attachments as $key => $attachment) {
                $data['attachment['.$key.']'] = curl_file_create($attachment[0]);
            }
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
        if (empty($credentials)) {
            $this->onAcymGetCredentialsSendingMethod($credentials, self::SENDING_METHOD_ID);
        }

        $this->sendingMethodApiUrl = self::SENDING_METHOD_API_URL_US;
        if (!empty($credentials[self::SENDING_METHOD_ID.'_api_region']) && $credentials[self::SENDING_METHOD_ID.'_api_region'] === 'eu') {
            $this->sendingMethodApiUrl = self::SENDING_METHOD_API_URL_EU;
        }

        if (!empty($credentials[self::SENDING_METHOD_ID.'_api_domain'])) {
            $this->sendingMethodApiUrl .= $credentials[self::SENDING_METHOD_ID.'_api_domain'].'/';
        }
    }

    /**
     * @param array  $credentials
     * @param string $sendingMethod
     * @param array  $sendingMethodListParams this parameter is only used for the plugin sending method list
     *
     * @return void
     */
    public function onAcymGetCredentialsSendingMethod(array &$credentials, string $sendingMethod, array $sendingMethodListParams = [])
    {
        if ($sendingMethod != self::SENDING_METHOD_ID) return;

        $apiKey = self::SENDING_METHOD_ID.'_api_key';
        $domain = self::SENDING_METHOD_ID.'_api_domain';
        $region = self::SENDING_METHOD_ID.'_api_region';

        $credentials = [
            $apiKey => $sendingMethodListParams[$apiKey] ?? $this->config->get($apiKey, ''),
            $domain => $sendingMethodListParams[$domain] ?? $this->config->get($domain, ''),
            $region => $sendingMethodListParams[$region] ?? $this->config->get($region, 'us'),
        ];
    }

    public function getHeadersSendingMethod($sendingMethod, $credentials = [])
    {
        return ['content-type: multipart/form-data'];
    }

    public function getAuthenticationSendingMethod($sendingMethod, $credentials = [], $sendingMethodListParams = [])
    {
        if (empty($credentials)) $this->onAcymGetCredentialsSendingMethod($credentials, $sendingMethod, $sendingMethodListParams);

        return [
            'name' => 'api',
            'pwd' => $credentials[self::SENDING_METHOD_ID.'_api_key'],
        ];
    }

    public function onAcymSendingMethodOptions(&$data)
    {
        $data['embedImage'][self::SENDING_METHOD_ID] = false;
    }

    public function onAcymGetSettingsSendingMethodFromPlugin(&$data, $plugin, $method)
    {
        if ($method != self::SENDING_METHOD_ID) return;

        //__START__wordpress_
        if (ACYM_CMS === 'wordpress' && $plugin === 'wp_mail_smtp') {
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
