<?php

use AcyMailing\Types\DelayType;

class SendinblueCredentials extends SendinblueClass
{
    public function onAcymGetCredentialsSendingMethod(&$credentials, $sendingMethod)
    {
        if ($sendingMethod != plgAcymSendinblue::SENDING_METHOD_ID) return;

        $credentials = [
            plgAcymSendinblue::SENDING_METHOD_ID.'_api_key' => $this->config->get(plgAcymSendinblue::SENDING_METHOD_ID.'_api_key', ''),
        ];
    }

    public function getHeadersSendingMethod($sendingMethod, $credentials = [])
    {

        if (empty($credentials)) $this->onAcymGetCredentialsSendingMethod($credentials, $sendingMethod);

        return [
            'api-key:'.$credentials[plgAcymSendinblue::SENDING_METHOD_ID.'_api_key'],
            'accept: application/json',
            'content-type: application/json',
        ];
    }

    public function getSendingMethodsHtmlSetting(&$data)
    {
        $delayType = new DelayType();
        ob_start();
        ?>
		<div class="send_settings cell grid-x acym_vcenter" id="<?php echo plgAcymSendinblue::SENDING_METHOD_ID; ?>_settings">
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label class="cell shrink margin-right-1" for="<?php echo plgAcymSendinblue::SENDING_METHOD_ID; ?>_settings_api-key">
                    <?php echo acym_translationSprintf(
                        'ACYM_SENDING_METHOD_API_KEY',
                        plgAcymSendinblue::SENDING_METHOD_NAME
                    ); ?>
				</label>
                <?php echo $this->getLinks(
                    'https://www.sendinblue.com/?tap_a=30591-fb13f0&tap_s=1371199-cf94c5',
                    'https://www.sendinblue.com/pricing/?tap_a=30591-fb13f0&tap_s=1371199-cf94c5'
                ); ?>
				<input type="text"
					   id="<?php echo plgAcymSendinblue::SENDING_METHOD_ID; ?>_settings_api-key"
					   value="<?php echo empty($data['tab']->config->values[plgAcymSendinblue::SENDING_METHOD_ID.'_api_key']) ? '' : $data['tab']->config->values[plgAcymSendinblue::SENDING_METHOD_ID.'_api_key']->value; ?>"
					   name="config[<?php echo plgAcymSendinblue::SENDING_METHOD_ID; ?>_api_key]"
					   class="cell margin-right-1 acym__configuration__mail__settings__text">
                <?php echo $this->getTestCredentialsSendingMethodButton(plgAcymSendinblue::SENDING_METHOD_ID); ?>
                <?php echo $this->getCopySettingsButton($data, plgAcymSendinblue::SENDING_METHOD_ID, 'wp_mail_smtp'); ?>
				<div class="cell grid-x margin-top-1 acym__sending__methods__synch">
					<button type="button"
							sending-method-id="<?php echo plgAcymSendinblue::SENDING_METHOD_ID; ?>"
							class="acym__configuration__sending__synch__users cell shrink button button-secondary">
                        <?php echo acym_translation('ACYM_SYNCHRO_EXISTING_USERS'); ?>
					</button>
					<span class="acym__configuration__sending__method-icon cell shrink margin-left-1 acym_vcenter"></span>
					<span class="acym__configuration__sending__method-synch__message cell shrink margin-left-1 acym_vcenter"></span>
				</div>
                <?php if (!$this->plugin->isLogFileEmpty()) { ?>
					<div class="cell grid-x margin-top-1 acym__sending__methods__log">
						<a href="<?php echo acym_completeLink('configuration&task=downloadLogFile&filename='.urlencode($this->plugin->logFilename)) ?>"
						   target="_blank"
						   class="cell shrink button button-secondary">
                            <?php echo acym_translation('ACYM_DOWNLOAD_LOG_FILE'); ?>
						</a>
					</div>
                <?php } ?>
				<div class="cell grid-x margin-top-1 acym_vcenter acym__sending__methods__clean__data">
                    <?php echo acym_translationSprintf(
                        'ACYM_CLEAN_DATA_ON_X_X',
                        plgAcymSendinblue::SENDING_METHOD_NAME,
                        $delayType->display(
                            'config['.plgAcymSendinblue::SENDING_METHOD_ID.'_clean_frequency]',
                            $this->config->get(plgAcymSendinblue::SENDING_METHOD_ID.'_clean_frequency', 2592000),//one month
                            4
                        )
                    ) ?>
				</div>
			</div>
		</div>
        <?php
        $data['sendingMethodsHtmlSettings'][plgAcymSendinblue::SENDING_METHOD_ID] = ob_get_clean();
    }

    public function testCredentialSendingMethod($sendingMethod, $credentials)
    {
        if ($sendingMethod == plgAcymSendinblue::SENDING_METHOD_ID) {
            $headers = $this->getHeadersSendingMethod(plgAcymSendinblue::SENDING_METHOD_ID, $credentials);
            $response = $this->callApiSendingMethod('account', [], $headers);
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

    public function getCreditRemainingSendingMethod(&$html)
    {
        $sendingMethod = $this->config->get('mailer_method', '');
        if (empty($sendingMethod) || $sendingMethod != plgAcymSendinblue::SENDING_METHOD_ID) return;
        $apiKey = $this->config->get(plgAcymSendinblue::SENDING_METHOD_ID.'_api_key', '');
        if (empty($apiKey)) return;

        $headers = $this->getHeadersSendingMethod(plgAcymSendinblue::SENDING_METHOD_ID);
        $response = $this->callApiSendingMethod('account', [], $headers);

        if (!empty($response['code'])) {
            $html = acym_translationSprintf(
                'ACYM_SENDING_METHOD_ERROR_WHILE_ACTION',
                plgAcymSendinblue::SENDING_METHOD_NAME,
                acym_translation('ACYM_GETTING_REMAINING_CREDITS'),
                $response['message']
            );

            return;
        }

        $html = acym_translationSprintf(
            'ACYM_SENDING_METHOD_X_UNITY',
            plgAcymSendinblue::SENDING_METHOD_NAME,
            $response['plan'][0]['credits'],
            '<span class="acym_not_bold">'.acym_translation('ACYM_CREDITS_REMAINING').'</span>'
        );
    }
}
