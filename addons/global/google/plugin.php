<?php

use AcyMailing\Core\AcymPlugin;
use AcyMailing\Helpers\MailerHelper;

class plgAcymGoogle extends AcymPlugin
{
    const SENDING_METHOD_ID = 'google';
    const SENDING_METHOD_NAME = 'Google';

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_GOOGLE');
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        if ($isMailer) {
            return;
        }

        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/google.svg',
        ];
    }

    public function onAcymGetSendingMethodsHtmlSetting(&$data)
    {
        $username = $this->config->get('google_username', $this->config->get('from_email'));
        $setupDocumentation = ACYM_DOCUMENTATION.'setup/configuration/mail-configuration/set-up-oauth-2.0';
        $redirectUrl = trim($this->config->get('google_redirect_url', acym_baseURI()));

        $refreshToken = $this->config->get('google_refresh_token');
        $refreshTokenExpiration = $this->config->get('google_refresh_token_expiration');
        $mustAuthenticate = empty($refreshToken) || (!empty($refreshTokenExpiration) && $refreshTokenExpiration < time());

        $mailerHelper = new MailerHelper();

        ob_start();
        ?>
		<div class="send_settings grid-x cell large-6 xlarge-5 xxlarge-4 margin-auto" id="<?php echo self::SENDING_METHOD_ID; ?>_settings">
			<div class="acym_port_465_closed is-hidden">
                <?php acym_display(acym_translation('ACYM_PORT_NEEDED'), 'error', false); ?>
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="google_username" class="cell">
                    <?php echo acym_translation('ACYM_SMTP_USERNAME').acym_info(['textShownInTooltip' => 'ACYM_SMTP_USERNAME_DESC']); ?>
				</label>
				<input id="google_username"
					   class="cell"
					   type="text"
					   name="config[google_username]"
					   value="<?php echo acym_escape($username); ?>">
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="google_client_id" class="cell"><?php echo acym_externalLink('ACYM_SMTP_CLIENT_ID', $setupDocumentation, false); ?></label>
				<input id="google_client_id"
					   class="cell"
					   type="text"
					   name="config[google_client_id]"
					   value="<?php echo acym_escape($this->config->get('google_client_id')); ?>">
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="google_client_secret" class="cell"><?php echo acym_externalLink('ACYM_SMTP_CLIENT_SECRET', $setupDocumentation, false); ?></label>
				<input id="google_client_secret"
					   class="cell"
					   type="text"
					   name="config[google_client_secret]"
					   value="<?php echo acym_escape($this->config->get('google_client_secret')); ?>">
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="google_redirect_url" class="cell"><?php echo acym_translation('ACYM_SMTP_REDIRECT_URL'); ?></label>
				<input id="google_redirect_url"
					   class="cell"
					   type="text"
					   name="config[google_redirect_url]"
					   value="<?php echo acym_escape($redirectUrl); ?>">
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
                <?php if ($mustAuthenticate) { ?>
					<button acym-data-before="jQuery.acymConfigSave();"
							data-task="loginForOAuth2Smtp"
							class="button acy_button_submit margin-next-1">
                        <?php echo acym_translation('ACYM_AUTHENTICATE'); ?>
					</button>
                    <?php echo $this->getCopySettingsButton($data, self::SENDING_METHOD_ID, 'wp_mail_smtp', false); ?>
                <?php } else { ?>
					<button acym-data-before="jQuery.acymConfigSave();"
							data-task="logoutForOAuth2Smtp"
							class="button acy_button_submit button-secondary margin-bottom-1">
                        <?php echo acym_translation('ACYM_REVOKE_PERMISSIONS'); ?>
					</button>
                    <?php
                    if (!empty($refreshTokenExpiration)) {
                        echo acym_translationSprintf('ACYM_AUTHENTICATION_WILL_EXPIRE', acym_date($refreshTokenExpiration, 'ACYM_DATE_FORMAT_LC2'));
                    }
                    ?>
                <?php } ?>
			</div>
		</div>
        <?php
        $data['sendingMethodsHtmlSettings'][self::SENDING_METHOD_ID] = ob_get_clean();
    }

    public function onAcymGetSettingsSendingMethodFromPlugin(&$data, $plugin, $method)
    {
        if ($method !== self::SENDING_METHOD_ID) {
            return;
        }

        //__START__wordpress_
        if (ACYM_CMS === 'wordpress' && $plugin === 'wp_mail_smtp') {
            $wpMailSmtpSetting = get_option('wp_mail_smtp', '');

            if (empty($wpMailSmtpSetting['gmail'])) {
                return;
            }

            $data['google_username'] = $wpMailSmtpSetting['gmail']['user_details']['email'] ?? '';
            $data['google_client_id'] = $wpMailSmtpSetting['gmail']['client_id'] ?? '';
            $data['google_client_secret'] = $wpMailSmtpSetting['gmail']['client_secret'] ?? '';
        }
        //__END__wordpress_
    }

    /**
     * Doc: https://developers.google.com/identity/protocols/oauth2/web-server#httprest_2
     */
    public function onAcymOauthAuthenticate(string &$consentUrl, bool $isSmtp): void
    {
        if ($isSmtp) {
            if ($this->config->get('mailer_method') !== self::SENDING_METHOD_ID) {
                return;
            }

            $clientId = $this->config->get('google_client_id');
            $clientSecret = $this->config->get('google_client_secret');
            $redirectUrl = $this->config->get('google_redirect_url');
        } else {
            if ($this->config->get('bounce_server') !== 'imap.gmail.com') {
                return;
            }

            $clientId = $this->config->get('bounce_client_id');
            $clientSecret = $this->config->get('bounce_client_secret');
            $redirectUrl = acym_baseURI();
        }

        if (empty($clientId) || empty($clientSecret) || empty($redirectUrl)) {
            acym_enqueueMessage(acym_translation('ACYM_PLEASE_FILL_CLIENT_ID_SECRET'), 'error', false);

            return;
        }

        $consentUrl = 'https://accounts.google.com/o/oauth2/v2/auth?access_type=offline&prompt=consent&';
        $consentUrl .= 'client_id='.urlencode($clientId);
        $consentUrl .= '&response_type=code';
        $consentUrl .= '&redirect_uri='.urlencode($redirectUrl);
        $consentUrl .= '&scope='.urlencode('https://mail.google.com/');
        $consentUrl .= '&state=acymailing'.($isSmtp ? 'smtp' : 'bounce');
    }

    /**
     * Doc: https://developers.google.com/identity/protocols/oauth2/web-server#httprest_3
     */
    public function onAcymOauthCredentialsCreation(bool $isSmtp, string $code): void
    {
        if ($isSmtp) {
            if ($this->config->get('mailer_method') !== self::SENDING_METHOD_ID) {
                return;
            }

            $clientId = trim($this->config->get('google_client_id'));
            $clientSecret = trim($this->config->get('google_client_secret'));
            $redirectUrl = trim($this->config->get('google_redirect_url', acym_baseURI()));
        } else {
            if ($this->config->get('bounce_server') !== 'imap.gmail.com') {
                return;
            }

            $clientId = trim($this->config->get('bounce_client_id'));
            $clientSecret = trim($this->config->get('bounce_client_secret'));
            $redirectUrl = acym_baseURI();
        }

        if (empty($clientId) || empty($clientSecret)) {
            acym_enqueueMessage(acym_translation('ACYM_PLEASE_FILL_CLIENT_ID_SECRET'), 'error', false);

            return;
        }

        $response = acym_makeCurlCall(
            'https://oauth2.googleapis.com/token',
            [
                'method' => 'POST',
                'data' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri' => $redirectUrl,
                ],
            ]
        );

        acym_logError('Response from OAuth call: '.json_encode($response), self::SENDING_METHOD_ID);

        if (!empty($response['error'])) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_SMTP_OAUTH_ERROR', $response['error']), 'error', false);

            return;
        }

        $expiringTime = time() + (int)$response['expires_in'];

        if (!empty($response['refresh_token_expires_in'])) {
            $refreshExpiringTime = time() + (int)$response['refresh_token_expires_in'];

            acym_enqueueMessage(
                acym_translationSprintf(
                    'ACYM_OAUTH_TEMPORARY',
                    acym_date($refreshExpiringTime, 'ACYM_DATE_FORMAT_LC2')
                ),
                'info',
                false
            );
        } else {
            $refreshExpiringTime = 0;
            acym_enqueueMessage(acym_translation('ACYM_SMTP_OAUTH_OK'), 'info');
        }

        if ($isSmtp) {
            $newConfig = [
                'google_access_token' => $response['access_token'],
                'google_access_token_expiration' => $expiringTime,
                'google_refresh_token' => $response['refresh_token'],
                'google_refresh_token_expiration' => $refreshExpiringTime,
            ];
        } else {
            $newConfig = [
                'bounce_access_token' => $response['access_token'],
                'bounce_access_token_expiration' => $expiringTime,
                'bounce_refresh_token' => $response['refresh_token'],
                'bounce_refresh_token_expiration' => $refreshExpiringTime,
            ];
        }

        $this->config->saveConfig($newConfig);
        acym_config(true);
    }

    public function onAcymOauthRevoke(): void
    {
        if ($this->config->get('mailer_method') !== self::SENDING_METHOD_ID) {
            return;
        }

        $this->config->saveConfig(
            [
                'google_refresh_token' => '',
                'google_refresh_token_expiration' => '',
            ]
        );
    }
}
