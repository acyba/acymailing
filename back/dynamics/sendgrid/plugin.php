<?php

use AcyMailing\Libraries\acymPlugin;

class plgAcymSendgrid extends acymPlugin
{
    const SENDING_METHOD_ID = 'sendgrid';
    const SENDING_METHOD_NAME = 'SendGrid';
    const SENDING_METHOD_API_URL = 'https://api.sendgrid.com/v3/';

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = 'SendGrid';
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/sendgrid.png',
        ];
    }

    public function onAcymGetSendingMethodsHtmlSetting(&$data)
    {
        ob_start();
        ?>
		<div class="send_settings cell grid-x acym_vcenter" id="<?php echo self::SENDING_METHOD_ID; ?>_settings">
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label class="cell shrink margin-right-1" for="<?php echo self::SENDING_METHOD_ID; ?>_settings_api-key">
                    <?php echo acym_translationSprintf(
                        'ACYM_SENDING_METHOD_API_KEY',
                        self::SENDING_METHOD_NAME
                    ); ?>
				</label>
                <?php echo $this->getLinks('https://signup.sendgrid.com/', 'https://sendgrid.com/pricing/'); ?>
				<input type="text"
					   id="<?php echo self::SENDING_METHOD_ID; ?>_settings_api-key"
					   value="<?php echo empty($data['tab']->config->values[self::SENDING_METHOD_ID.'_api_key']) ? '' : $data['tab']->config->values[self::SENDING_METHOD_ID.'_api_key']->value; ?>"
					   name="config[<?php echo self::SENDING_METHOD_ID; ?>_api_key]"
					   class="cell margin-right-1 acym__configuration__mail__settings__text">
                <?php echo $this->getTestCredentialsSendingMethodButton(self::SENDING_METHOD_ID); ?>
                <?php echo $this->getCopySettingsButton($data, self::SENDING_METHOD_ID, 'wp_mail_smtp'); ?>
			</div>
		</div>
        <?php
        $data['sendingMethodsHtmlSettings'][self::SENDING_METHOD_ID] = ob_get_clean();
    }

    public function onAcymTestCredentialSendingMethod($sendingMethod, $credentials)
    {
        if ($sendingMethod == self::SENDING_METHOD_ID) {
            $headers = $this->getHeadersSendingMethod(self::SENDING_METHOD_ID, $credentials);
            $response = $this->callApiSendingMethod(self::SENDING_METHOD_API_URL.'scopes', [], $headers);
            if (!empty($response['error_curl'])) {
                acym_sendAjaxResponse(acym_translationSprintf('ACYM_ERROR_OCCURRED_WHILE_CALLING_API', $response['error_curl']), [], false);
            } elseif (!empty($response['errors'])) {
                $message = $response['errors'][0]['message'] == 'authorization required'
                    ? acym_translation('ACYM_AUTHENTICATION_FAILS_WITH_API_KEY')
                    : acym_translationSprintf(
                        'ACYM_API_RETURN_THIS_ERROR',
                        $response['errors'][0]['message']
                    );
                acym_sendAjaxResponse($message, [], false);
            } else {
                acym_sendAjaxResponse(acym_translation('ACYM_API_KEY_CORRECT'));
            }
        }
    }

    public function onAcymSendEmail(&$response, $sendingMethod, $to, $subject, $from, $reply_to, $body, $bcc = [], $attachments = [])
    {
        //https://sendgrid.com/docs/API_Reference/Web_API_v3/Mail/index.html
        if ($sendingMethod != self::SENDING_METHOD_ID) return;
        $headers = $this->getHeadersSendingMethod(self::SENDING_METHOD_ID);
        $headers[] = 'Content-Type: application/json';
        $data = [
            'personalizations' => [
                [
                    'to' => [
                        [
                            'email' => $to['email'],
                        ],
                    ],
                    'subject' => $subject,
                ],
            ],
            'from' => $from,
            'reply_to' => $reply_to,
            'content' => [
                [
                    'type' => 'text/html',
                    'value' => $body,
                ],
            ],
        ];
        if (!empty($bcc)) $data['personalizations'][0]['bcc'] = [['email' => $bcc[0][0]]];


        if (!empty($attachments)) {
            $data['attachments'] = [];
            foreach ($attachments as $key => $attachment) {
                $data['attachments'][] = [
                    'content' => base64_encode(acym_fileGetContent($attachment[0])),
                    'filename' => $attachment[1],
                    'disposition' => 'attachment',
                ];
            }
        }

        $responseMailer = $this->callApiSendingMethod(self::SENDING_METHOD_API_URL.'mail/send', $data, $headers, 'POST');

        if (is_null($responseMailer)) {
            $response['error'] = false;
        } else {
            $response['error'] = true;
            $response['message'] = $responseMailer['errors'][0]['message'];
        }
    }

    public function onAcymGetCredentialsSendingMethod(&$credentials, $sendingMethod)
    {
        if ($sendingMethod != self::SENDING_METHOD_ID) return;

        $credentials = [
            self::SENDING_METHOD_ID.'_api_key' => $this->config->get(self::SENDING_METHOD_ID.'_api_key', ''),
        ];
    }

    public function onAcymGetCreditRemainingSendingMethod(&$html)
    {
        $sendingMethod = $this->config->get('mailer_method', '');
        if (empty($sendingMethod) || $sendingMethod != self::SENDING_METHOD_ID) return;

        $headers = $this->getHeadersSendingMethod(self::SENDING_METHOD_ID);

        $response = $this->callApiSendingMethod(self::SENDING_METHOD_API_URL.'user/credits', [], $headers);

        if (!empty($response['errors'])) {
            $html = acym_translationSprintf(
                'ACYM_SENDING_METHOD_ERROR_WHILE_ACTION',
                self::SENDING_METHOD_NAME,
                acym_translation('ACYM_GETTING_REMAINING_CREDITS'),
                $response['errors'][0]['message']
            );

            return;
        }

        if (!isset($response['remain']) || !isset($response['total'])) {

            $html = acym_translationSprintf(
                'ACYM_SENDING_METHOD_ERROR_WHILE_ACTION',
                self::SENDING_METHOD_NAME,
                acym_translation('ACYM_GETTING_REMAINING_CREDITS'),
                acym_translation('ACYM_CANT_RETRIEVE_CREDITS')
            );
        } else {
            $html = acym_translationSprintf(
                'ACYM_SENDING_METHOD_X_OF_X_UNITY',
                self::SENDING_METHOD_NAME,
                $response['remain'],
                $response['total'],
                '<span class="acym_not_bold">'.acym_translation('ACYM_CREDITS_REMAINING').'</span>'
            );
        }
    }

    public function getHeadersSendingMethod($sendingMethod, $credentials = [])
    {

        if (empty($credentials)) $this->onAcymGetCredentialsSendingMethod($credentials, $sendingMethod);

        return [
            'Authorization: Bearer '.$credentials[self::SENDING_METHOD_ID.'_api_key'],
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
            if (empty($wpMailSmtpSetting) || empty($wpMailSmtpSetting['sendgrid']) || (!empty($wpMailSmtpSetting['sendgrid'] && empty($wpMailSmtpSetting['sendgrid']['api_key'])))) {
                return;
            }

            $data['sendgrid_api_key'] = $wpMailSmtpSetting['sendgrid']['api_key'];
        }
        //__END__wordpress_

    }
}
