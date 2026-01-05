<?php

use AcyMailing\Core\AcymPlugin;

class plgAcymPostmark extends AcymPlugin
{
    const SENDING_METHOD_ID = 'postmark';
    const SENDING_METHOD_NAME = 'Postmark';
    const SENDING_METHOD_API_URL = 'https://api.postmarkapp.com/';

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = self::SENDING_METHOD_NAME;
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/postmark.svg',
            'image_class' => '',
        ];
    }

    public function onAcymGetSendingMethodsHtmlSetting(&$data)
    {
        $config = empty($data['tab']) ? $this->config : $data['tab']->config;
        $defaultApiKey = $config->get(self::SENDING_METHOD_ID.'_api_key');
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
                <?php echo $this->getLinks('https://account.postmarkapp.com/sign_up', 'https://postmarkapp.com/pricing'); ?>
				<input type="text"
					   id="<?php echo self::SENDING_METHOD_ID; ?>_settings_api-key"
					   value="<?php echo empty($defaultApiKey) ? $this->config->get(self::SENDING_METHOD_ID.'_api_key') : $defaultApiKey; ?>"
					   name="config[<?php echo self::SENDING_METHOD_ID; ?>_api_key]"
					   class="cell margin-next-1 acym__configuration__mail__settings__text">
                <?php echo $this->getTestCredentialsSendingMethodButton(self::SENDING_METHOD_ID); ?>
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label class="cell medium-6">
                    <?php echo acym_translation('ACYM_POSTMARK_STREAM_ID').acym_info(['textShownInTooltip' => 'ACYM_STREAM_ID_INFO']); ?>
					<input type="text"
						   class="cell auto"
						   name="config[postmark_stream_id]"
						   value="<?php echo acym_escape(empty($this->config->get('postmark_stream_id')) ? '' : $this->config->get('postmark_stream_id')); ?>">
				</label>
			</div>
		</div>
        <?php
        $data['sendingMethodsHtmlSettings'][self::SENDING_METHOD_ID] = ob_get_clean();
    }

    public function getHeadersSendingMethod($sendingMethod, $credentials = [], $sendingMethodListParams = [])
    {
        if (empty($credentials)) $this->onAcymGetCredentialsSendingMethod($credentials, $sendingMethod, $sendingMethodListParams);

        return [
            'X-Postmark-Server-Token:'.$credentials[self::SENDING_METHOD_ID.'_api_key'],
            'Accept: application/json',
            'Content-type: application/json',
        ];
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

        $key = self::SENDING_METHOD_ID.'_api_key';

        $credentials = [
            $key => $sendingMethodListParams[$key] ?? $this->config->get($key, ''),
        ];
    }

    public function onAcymTestCredentialSendingMethod($sendingMethod, $credentials)
    {
        if ($sendingMethod == self::SENDING_METHOD_ID) {
            $headers = $this->getHeadersSendingMethod(self::SENDING_METHOD_ID, $credentials);
            $response = $this->callApiSendingMethod(self::SENDING_METHOD_API_URL.'server', [], $headers);
            if (!empty($response['error_curl'])) {
                acym_sendAjaxResponse(acym_translationSprintf('ACYM_ERROR_OCCURRED_WHILE_CALLING_API', $response['error_curl']), [], false);
            } elseif (!empty($response['ErrorCode']) && in_array($response['ErrorCode'], ['Unauthorized', '10'])) {
                $message = acym_translation('ACYM_AUTHENTICATION_FAILS_WITH_API_KEY');
                acym_sendAjaxResponse($message, [], false);
            } elseif (!empty($response['ErrorCode'])) {
                $message = acym_translationSprintf('ACYM_API_RETURN_THIS_ERROR', $response['ErrorCode'].': '.$response['Message']);
                acym_sendAjaxResponse($message, [], false);
            } else {
                acym_sendAjaxResponse(acym_translation('ACYM_API_KEY_CORRECT'));
            }
        }
    }

    public function onAcymSendEmail(&$response, $mailerHelper, $to, $from, $reply_to, $bcc = [], $attachments = [], $sendingMethodListParams = [])
    {
        //https://postmarkapp.com/developer/api/email-api
        if ($mailerHelper->externalMailer !== self::SENDING_METHOD_ID) return;

        $data = [
            'From' => $from['email'],
            'ReplyTo' => $reply_to['email'],
            'To' => $to['email'],
            'Subject' => $mailerHelper->Subject,
            'HtmlBody' => $mailerHelper->Body,
            'MessageStream' => 'outbound',
        ];
        if (!empty($bcc)) $data['Bcc'] = $bcc[0][0];

        if (!empty($attachments)) {
            $attachFormated = [];
            foreach ($attachments as $oneAttach) {
                $attachFormated[] = [
                    'Name' => $oneAttach[1],
                    'Content' => $oneAttach['contentEncoded'],
                    'ContentType' => $oneAttach[4],
                ];
            }
            $data['Attachments'] = $attachFormated;
        }

        // Handle the Stream ID
        if (!empty($mailerHelper->mailId)) {
            $mailId = $mailerHelper->mailId;
            if (acym_isMultilingual()) {
                $parentId = acym_loadResult('SELECT parent_id FROM `#__acym_mail` WHERE id = '.intval($mailId));
                if (!empty($parentId)) $mailId = $parentId;
            }

            $sendParams = acym_loadResult('SELECT sending_params FROM `#__acym_campaign` WHERE mail_id = '.intval($mailId));
            if (!empty($sendParams)) {
                $sendParams = json_decode($sendParams, true);
                if (!empty($sendParams['message_stream_id'])) {
                    $data['MessageStream'] = $sendParams['message_stream_id'];
                } else {
                    $data['MessageStream'] = $this->config->get('postmark_stream_id');
                }
            }
        }

        $headers = $this->getHeadersSendingMethod(self::SENDING_METHOD_ID, [], $sendingMethodListParams);
        $responseMailer = $this->callApiSendingMethod(self::SENDING_METHOD_API_URL.'email', $data, $headers, 'POST');

        if (!empty($responseMailer['ErrorCode'])) {
            $response['error'] = true;
            $response['message'] = $responseMailer['Message'];
        } else {
            $response['error'] = false;
        }
    }
}
