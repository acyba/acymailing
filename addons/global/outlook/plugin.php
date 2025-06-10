<?php

use AcyMailing\Core\AcymPlugin;
use AcyMailing\Helpers\MailerHelper;

class plgAcymOutlook extends AcymPlugin
{
    const SENDING_METHOD_ID = 'outlook';
    const SENDING_METHOD_NAME = 'Outlook';
    const SCOPE_SMTP = 'openid offline_access https://outlook.office.com/SMTP.Send';
    const SCOPE_IMAP = 'openid offline_access https://outlook.office.com/IMAP.AccessAsUser.All';

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_OUTLOOK');
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        if ($isMailer) {
            return;
        }

        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/outlook.svg',
        ];
    }

    public function onAcymGetSendingMethodsHtmlSetting(&$data)
    {
        $username = $this->config->get('outlook_username', $this->config->get('from_email'));
        $setupDocumentation = ACYM_DOCUMENTATION.'setup/configuration/mail-configuration/set-up-oauth-2.0';
        $redirectUrl = trim($this->config->get('outlook_redirect_url', acym_baseURI()));

        $refreshToken = $this->config->get('outlook_refresh_token');
        $refreshTokenExpiration = $this->config->get('outlook_refresh_token_expiration');
        $mustAuthenticate = empty($refreshToken) || (!empty($refreshTokenExpiration) && $refreshTokenExpiration < time());

        $mailerHelper = new MailerHelper();

        ob_start();
        ?>
		<div class="send_settings grid-x cell large-6 xlarge-5 xxlarge-4 margin-auto" id="<?php echo self::SENDING_METHOD_ID; ?>_settings">
            <?php
            if (!$mailerHelper->isPortOpen(465, 'ssl://smtp.gmail.com')) {
                acym_display(acym_translation('ACYM_PORT_NEEDED'), 'error', false);
            }
            ?>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="outlook_username" class="cell"><?php echo acym_translation('ACYM_SMTP_USERNAME').acym_info('ACYM_SMTP_USERNAME_DESC'); ?></label>
				<input id="outlook_username"
					   class="cell"
					   type="text"
					   name="config[outlook_username]"
					   value="<?php echo acym_escape($username); ?>">
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="outlook_tenant" class="cell"><?php echo acym_externalLink('ACYM_TENANT', $setupDocumentation, false); ?></label>
                <?php
                $value = $this->config->get('outlook_tenant');
                echo acym_select(
                    [
                        'common' => 'ACYM_ANY_ACCOUNT_TYPE',
                        'consumers' => 'ACYM_MICROSOFT_ACCOUNTS',
                        'organizations' => 'ACYM_ORGANIZATIONS',
                    ],
                    'config[outlook_tenant]',
                    empty($value) ? 'consumers' : $value,
                    [
                        'class' => 'acym__select',
                    ],
                    '',
                    '',
                    '',
                    true
                );
                ?>
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="outlook_client_id" class="cell"><?php echo acym_externalLink('ACYM_SMTP_CLIENT_ID', $setupDocumentation, false); ?></label>
				<input id="outlook_client_id"
					   class="cell"
					   type="text"
					   name="config[outlook_client_id]"
					   value="<?php echo acym_escape($this->config->get('outlook_client_id')); ?>">
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="outlook_client_secret" class="cell"><?php echo acym_externalLink('ACYM_SMTP_CLIENT_SECRET', $setupDocumentation, false); ?></label>
				<input id="outlook_client_secret"
					   class="cell"
					   type="text"
					   name="config[outlook_client_secret]"
					   value="<?php echo acym_escape($this->config->get('outlook_client_secret')); ?>">
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="outlook_redirect_url" class="cell"><?php echo acym_translation('ACYM_SMTP_REDIRECT_URL'); ?></label>
				<input id="outlook_redirect_url"
					   class="cell"
					   type="text"
					   name="config[outlook_redirect_url]"
					   value="<?php echo acym_escape($redirectUrl); ?>">
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
                <?php if ($mustAuthenticate) { ?>
					<button acym-data-before="jQuery.acymConfigSave();"
							data-task="loginForOAuth2Smtp"
							class="button acy_button_submit margin-right-1">
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

            if (empty($wpMailSmtpSetting['outlook'])) {
                return;
            }

            $data['outlook_client_id'] = $wpMailSmtpSetting['outlook']['client_id'] ?? '';
            $data['outlook_client_secret'] = $wpMailSmtpSetting['outlook']['client_secret'] ?? '';
        }
        //__END__wordpress_
    }

    /**
     * https://learn.microsoft.com/en-us/entra/identity-platform/v2-oauth2-auth-code-flow#request-an-authorization-code
     */
    public function onAcymOauthAuthenticate(string &$consentUrl, bool $isSmtp): void
    {
        if ($isSmtp) {
            if ($this->config->get('mailer_method') !== self::SENDING_METHOD_ID) {
                return;
            }

            $clientId = $this->config->get('outlook_client_id');
            $clientSecret = $this->config->get('outlook_client_secret');
            $tenant = $this->config->get('outlook_tenant');
            $redirectUrl = $this->config->get('outlook_redirect_url');
            $scope = self::SCOPE_SMTP;
        } else {
            if ($this->config->get('bounce_server') !== 'outlook.office365.com') {
                return;
            }

            $clientId = $this->config->get('bounce_client_id');
            $clientSecret = $this->config->get('bounce_client_secret');
            $tenant = $this->config->get('bounce_tenant');
            $redirectUrl = acym_baseURI();
            $scope = self::SCOPE_IMAP;
        }

        if (empty($clientId) || empty($clientSecret) || empty($redirectUrl)) {
            acym_enqueueMessage(acym_translation('ACYM_PLEASE_FILL_CLIENT_ID_SECRET'), 'error', false);

            return;
        }

        $consentUrl = 'https://login.microsoftonline.com/'.$tenant.'/oauth2/v2.0/authorize';
        $consentUrl .= '?response_mode=query';
        $consentUrl .= '&client_id='.urlencode($clientId);
        $consentUrl .= '&prompt=consent';
        $consentUrl .= '&response_type=code';
        $consentUrl .= '&redirect_uri='.urlencode($redirectUrl);
        $consentUrl .= '&scope='.urlencode($scope);
        $consentUrl .= '&state=acymailing'.($isSmtp ? 'smtp' : 'bounce');
    }

    /**
     * https://learn.microsoft.com/en-us/entra/identity-platform/v2-oauth2-auth-code-flow#redeem-a-code-for-an-access-token
     */
    public function onAcymOauthCredentialsCreation(bool $isSmtp, string $code): void
    {
        if ($isSmtp) {
            if ($this->config->get('mailer_method') !== self::SENDING_METHOD_ID) {
                return;
            }

            $clientId = trim($this->config->get('outlook_client_id'));
            $clientSecret = trim($this->config->get('outlook_client_secret'));
            $tenant = trim($this->config->get('outlook_tenant', 'consumers'));
            $redirectUrl = trim($this->config->get('outlook_redirect_url', acym_baseURI()));
            $scope = self::SCOPE_SMTP;
        } else {
            if ($this->config->get('bounce_server') !== 'outlook.office365.com') {
                return;
            }

            $clientId = trim($this->config->get('bounce_client_id'));
            $clientSecret = trim($this->config->get('bounce_client_secret'));
            $tenant = trim($this->config->get('bounce_tenant', 'consumers'));
            $redirectUrl = acym_baseURI();
            $scope = self::SCOPE_IMAP;
        }

        if (empty($clientId) || empty($clientSecret)) {
            acym_enqueueMessage(acym_translation('ACYM_PLEASE_FILL_CLIENT_ID_SECRET'), 'error', false);

            return;
        }

        $response = acym_makeCurlCall(
            'https://login.microsoftonline.com/'.$tenant.'/oauth2/v2.0/token',
            [
                'method' => 'POST',
                'data' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri' => $redirectUrl,
                    'scope' => $scope,
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
                'outlook_access_token' => $response['access_token'],
                'outlook_access_token_expiration' => $expiringTime,
                'outlook_refresh_token' => $response['refresh_token'],
                'outlook_refresh_token_expiration' => $refreshExpiringTime,
            ];
        } else {
            $newConfig = [
                'bounce_access_token' => $response['access_token'],
                'bounce_access_token_expiration' => $expiringTime,
                'bounce_refresh_token' => $response['refresh_token'],
                'bounce_refresh_token_expiration' => $refreshExpiringTime,
            ];
        }

        $this->config->save($newConfig);
        acym_config(true);
    }

    public function onAcymOauthRevoke(): void
    {
        if ($this->config->get('mailer_method') !== self::SENDING_METHOD_ID) {
            return;
        }

        $this->config->save(
            [
                'outlook_refresh_token' => '',
                'outlook_refresh_token_expiration' => '',
            ]
        );
    }
}
