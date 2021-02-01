<?php

use AcyMailing\Libraries\acymPlugin;

class plgAcymSendinblue extends acymPlugin
{
    const SENDING_METHOD_ID = 'sendinblue';
    const SENDING_METHOD_NAME = 'Sendinblue';
    const SENDING_METHOD_API_URL = 'https://api.sendinblue.com/v3/';

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = 'Sendinblue';
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/sendinblue.png',
            'premium' => true,
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
                <?php echo $this->getLinks(
                    'https://www.sendinblue.com/?tap_a=30591-fb13f0&tap_s=1371199-cf94c5',
                    'https://www.sendinblue.com/pricing/?tap_a=30591-fb13f0&tap_s=1371199-cf94c5'
                ); ?>
				<input type="text"
					   id="<?php echo self::SENDING_METHOD_ID; ?>_settings_api-key"
					   value="<?php echo empty($data['tab']->config->values[self::SENDING_METHOD_ID.'_api_key']) ? '' : $data['tab']->config->values[self::SENDING_METHOD_ID.'_api_key']->value; ?>"
					   name="config[<?php echo self::SENDING_METHOD_ID; ?>_api_key]"
					   class="cell margin-right-1 acym__configuration__mail__settings__text">
				<div class="cell grid-x margin-top-1">
                    <?php echo $this->getTestCredentialsSendingMethodButton(self::SENDING_METHOD_ID); ?>
				</div>
			</div>
		</div>
        <?php
        $data['sendingMethodsHtmlSettings'][self::SENDING_METHOD_ID] = ob_get_clean();
    }

    public function onAcymTestCredentialSendingMethod($sendingMethod, $credentials)
    {
        if ($sendingMethod == self::SENDING_METHOD_ID) {
            $headers = $this->getHeadersSendingMethod(self::SENDING_METHOD_ID, $credentials);
            $response = $this->callApiSendingMethod(self::SENDING_METHOD_API_URL.'account', [], $headers);
            if (!empty($response['error_curl'])) {
                acym_sendAjaxResponse(acym_translationSprintf('ACYM_ERROR_OCCURRED_WHILE_CALLING_API', $response['error_curl']), [], false);
            } elseif (!empty($response['code'])) {
                $message = $response['code'] == 'unauthorized'
                    ? acym_translation('ACYM_AUTHENTICATION_FAILS_WITH_API_KEY')
                    : acym_translationSprintf(
                        'ACYM_API_RETURN_THIS_ERROR',
                        $response['message']
                    );
                acym_sendAjaxResponse($message, [], false);
            } else {
                acym_sendAjaxResponse(acym_translation('ACYM_API_KEY_CORRECT'));
            }
        }
    }

    public function onAcymSendEmail(&$response, $sendingMethod, $to, $subject, $from, $reply_to, $body, $bcc = [], $attachments = [])
    {
        //https://developers.sendinblue.com/docs/send-a-transactional-email
        if ($sendingMethod != self::SENDING_METHOD_ID) return;
        $headers = $this->getHeadersSendingMethod(self::SENDING_METHOD_ID);
        $data = [
            'sender' => $from,
            'replyTo' => $reply_to,
            'to' => [
                [
                    'email' => $to['email'],
                ],
            ],
            'subject' => $subject,
            'htmlContent' => $body,
        ];

        if (!empty($bcc)) {
            $data['bcc'] = [
                [
                    'email' => $bcc[0][0],
                ],
            ];
        }

        if (!empty($attachments)) {
            $data['attachment'] = [];
            foreach ($attachments as $key => $attachment) {
                $data['attachment'][] = [
                    'content' => $attachment['contentEncoded'],
                    'name' => $attachment[1],
                ];
            }
        }

        $responseMailer = $this->callApiSendingMethod(self::SENDING_METHOD_API_URL.'smtp/email', $data, $headers, 'POST');

        if (!empty($responseMailer['code'])) {
            $response['error'] = true;
            $response['message'] = $responseMailer['message'];
        } else {
            $response['error'] = false;
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

        $response = $this->callApiSendingMethod(self::SENDING_METHOD_API_URL.'account', [], $headers);

        if (!empty($response['code'])) {
            $html = acym_translationSprintf(
                'ACYM_SENDING_METHOD_ERROR_WHILE_ACTION',
                self::SENDING_METHOD_NAME,
                acym_translation('ACYM_GETTING_REMAINING_CREDITS'),
                $response['message']
            );

            return;
        }

        $html = acym_translationSprintf(
            'ACYM_SENDING_METHOD_X_UNITY',
            self::SENDING_METHOD_NAME,
            $response['plan'][0]['credits'],
            '<span class="acym_not_bold">'.acym_translation('ACYM_CREDITS_REMAINING').'</span>'
        );
    }

    public function getHeadersSendingMethod($sendingMethod, $credentials = [])
    {

        if (empty($credentials)) $this->onAcymGetCredentialsSendingMethod($credentials, $sendingMethod);

        return [
            'api-key:'.$credentials[self::SENDING_METHOD_ID.'_api_key'],
            'accept: application/json',
            'content-type: application/json',
        ];
    }

    public function onAcymSendingMethodEmbedImage(&$data)
    {
        $data['embedImage'][self::SENDING_METHOD_ID] = false;
    }
}
