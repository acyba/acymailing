<?php

use AcyMailing\Core\AcymPlugin;

class plgAcymSmtp extends AcymPlugin
{
    const SENDING_METHOD_ID = 'smtp';
    const SENDING_METHOD_NAME = 'SMTP';
    const HOST_AUTH_2 = ['smtp.gmail.com', 'smtp-mail.outlook.com', 'smtp.office365.com'];

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_SMTP_SERVICE');
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        if ($isMailer) return;
        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'icon' => 'acymicon-email',
        ];
    }

    public function onAcymGetSendingMethodsHtmlSetting(&$data)
    {
        $smtpRedirectUrl = trim($this->config->get('smtp_redirectUrl'));
        $redirectUrl = empty($smtpRedirectUrl) ? acym_baseURI() : $smtpRedirectUrl;

        $isWalkTrough = $this->config->get('walk_through', 0);
        $loginAttribute = !$isWalkTrough ? 'acym-data-before="jQuery.acymConfigSave();"' : '';

        $link = ACYM_DOCUMENTATION.'setup/configuration/mail-configuration/set-up-oauth-2.0';

        ob_start();
        ?>
		<div class="send_settings grid-x cell large-6 xlarge-5 xxlarge-4 margin-auto" id="<?php echo self::SENDING_METHOD_ID; ?>_settings">
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="smtp_host" class="cell"><?php echo acym_translation('ACYM_SMTP_SERVER'); ?></label>
				<input id="smtp_host" class="cell" type="text" name="config[smtp_host]" value="<?php echo acym_escape($this->config->get('smtp_host')); ?>">
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="smtp_port" class="cell"><?php echo acym_translation('ACYM_SMTP_PORT').acym_info('ACYM_SMTP_PORT_DESC'); ?></label>
				<input
						id="smtp_port"
						class="cell medium-6"
						type="number"
						name="config[smtp_port]" value="<?php echo acym_escape($this->config->get('smtp_port')); ?>"
						placeholder="465, 587, 2525, 25">
			</div>
			<div id="available_ports" class="cell acym__sending__methods__one__settings">
				<a href="#" id="available_ports_check"><?php echo acym_translation('ACYM_SMTP_AVAILABLE_PORTS'); ?></a>
                <?php echo $this->getCopySettingsButton($data, self::SENDING_METHOD_ID, 'wp_mail_smtp'); ?>
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="smtp_secured" class="cell"><?php echo acym_translation('ACYM_SMTP_SECURE').acym_info('ACYM_SMTP_SECURE_DESC'); ?></label>
				<div class="cell medium-6">
                    <?php
                    echo acym_select(
                        [
                            '' => '- - -',
                            'ssl' => 'SSL',
                            'tls' => 'TLS',
                        ],
                        'config[smtp_secured]',
                        $this->config->get('smtp_secured', 'ssl'),
                        [
                            'class' => 'acym__select',
                            'acym-data-infinite' => '',
                        ],
                        '',
                        '',
                        'smtp_secured'
                    );
                    ?>
				</div>
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
                <?php
                echo acym_switch(
                    'config[smtp_keepalive]',
                    $this->config->get('smtp_keepalive'),
                    acym_translation('ACYM_SMTP_ALIVE').acym_info('ACYM_SMTP_ALIVE_DESC'),
                    [],
                    'medium-5 small-9'
                );
                ?>
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
                <?php
                echo acym_switch(
                    'config[smtp_auth]',
                    $this->config->get('smtp_auth', 1),
                    acym_translation('ACYM_SMTP_AUTHENTICATION').acym_info('ACYM_SMTP_AUTHENTICATION_DESC'),
                    [],
                    'medium-5 small-9'
                );
                ?>
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="smtp_method" class="cell"><?php echo acym_translation('ACYM_AUTHENTICATION_METHOD').acym_info('ACYM_AUTHENTICATION_METHOD_DESC'); ?></label>
				<div class="cell medium-6">
                    <?php
                    echo acym_select(
                        [
                            '' => acym_translation('ACYM_AUTOMATIC'),
                            'CRAM-MD5' => 'CRAM-MD5',
                            'LOGIN' => 'LOGIN',
                            'PLAIN' => 'PLAIN',
                            'XOAUTH2' => 'XOAUTH2',
                        ],
                        'config[smtp_method]',
                        $this->config->get('smtp_method', ''),
                        [
                            'class' => 'acym__select',
                            'acym-data-infinite' => '',
                        ],
                        '',
                        '',
                        'smtp_method'
                    );
                    ?>
				</div>
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings" id="acym__sending__methods__one__settings__type">
				<label for="smtp_type" class="cell"><?php echo acym_translation('ACYM_CONNECTION_TYPE').acym_info('ACYM_CONNECTION_TYPE_DESC'); ?></label>
				<div class="cell medium-6">
                    <?php
                    echo acym_select(
                        [
                            'oauth' => 'ACYM_WITH_OAUTH',
                            'password' => 'ACYM_WITH_PASSWORD',
                        ],
                        'config[smtp_type]',
                        $this->config->get('smtp_type', 'oauth'),
                        [
                            'class' => 'acym__select',
                            'acym-data-infinite' => '',
                        ],
                        '',
                        '',
                        'smtp_type',
                        true
                    );
                    ?>
				</div>
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="smtp_username" class="cell"><?php echo acym_translation('ACYM_SMTP_USERNAME').acym_info('ACYM_SMTP_USERNAME_DESC'); ?></label>
				<input id="smtp_username"
					   class="cell"
					   type="text"
					   name="config[smtp_username]"
					   value="<?php echo acym_escape($this->config->get('smtp_username')); ?>">
			</div>
			<div class="acym__default_auth_sending_params cell">
				<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings" id="oauthParams">
					<label for="smtp_password" class="cell"><?php echo acym_translation('ACYM_SMTP_PASSWORD').acym_info('ACYM_SMTP_PASSWORD_DESC'); ?></label>
					<input id="smtp_password"
						   class="cell"
						   type="text"
						   name="config[smtp_password]"
						   value="<?php echo str_repeat('*', strlen($this->config->get('smtp_password'))); ?>">
				</div>
			</div>
			<div class="acym__oauth2_sending_params cell">
				<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
					<label for="smtp_clientId" class="cell"><?php echo acym_externalLink('ACYM_SMTP_CLIENT_ID', $link, false); ?></label>
					<input id="smtp_clientId" class="cell" type="text" name="config[smtp_clientId]" value="<?php echo acym_escape($this->config->get('smtp_clientId')); ?>">
				</div>
				<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
					<label for="smtp_secret" class="cell"><?php echo acym_externalLink('ACYM_SMTP_CLIENT_SECRET', $link, false); ?></label>
					<input id="smtp_secret" class="cell" type="text" name="config[smtp_secret]" value="<?php echo acym_escape($this->config->get('smtp_secret')); ?>">
				</div>
				<div id="smtp_tenant_container" class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
					<label for="smtp_tenant" class="cell"><?php echo acym_externalLink('ACYM_TENANT', $link, false); ?></label>
                    <?php
                    $valuesArray = [
                        'consumers' => 'ACYM_MICROSOFT_ACCOUNTS',
                        'common' => 'ACYM_ANY_ACCOUNT_TYPE',
                        'organizations' => 'ACYM_ORGANIZATIONS',
                    ];
                    $value = $this->config->get('smtp_tenant');
                    echo acym_select(
                        $valuesArray,
                        'config[smtp_tenant]',
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
					<label for="smtp_redirectUrl" class="cell"><?php echo acym_translation('ACYM_SMTP_REDIRECT_URL'); ?></label>
					<input id="smtp_redirectUrl" class="cell" type="text" name="config[smtp_redirectUrl]" value="<?php echo acym_escape($redirectUrl); ?>">
				</div>
				<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
					<button <?php echo $loginAttribute; ?>
							data-task="loginForOAuth2Smtp"
							class="button acy_button_submit button-secondary margin-bottom-1"
							id="smtp_account_login">
                        <?php echo acym_translation('ACYM_LOGIN'); ?>
					</button>
				</div>
			</div>
		</div>
        <?php
        $data['sendingMethodsHtmlSettings'][self::SENDING_METHOD_ID] = ob_get_clean();
    }

    public function onAcymGetSettingsSendingMethodFromPlugin(&$data, $plugin, $method)
    {
        if ($method != self::SENDING_METHOD_ID) return;

        //__START__wordpress_
        if (ACYM_CMS === 'wordpress' && $plugin === 'wp_mail_smtp') {
            $wpMailSmtpSetting = get_option('wp_mail_smtp', '');
            if (empty($wpMailSmtpSetting) || empty($wpMailSmtpSetting['smtp'])) {
                return;
            }

            $data['smtp_host'] = $wpMailSmtpSetting['smtp']['host'];
            $data['smtp_port'] = $wpMailSmtpSetting['smtp']['port'];
            $data['smtp_secured'] = $wpMailSmtpSetting['smtp']['encryption'];
            $data['smtp_keepalive'] = 1;
            $data['smtp_auth'] = $wpMailSmtpSetting['smtp']['auth'] ? 1 : 0;
            $data['smtp_username'] = $wpMailSmtpSetting['smtp']['user'];
            $data['smtp_password'] = WPMailSMTP\Helpers\Crypto::decrypt($wpMailSmtpSetting['smtp']['pass']);
        }
        //__END__wordpress_
    }

    public function onAcymDisplayPage()
    {
        $mailerMethod = trim($this->config->get('mailer_method'));
        if ($mailerMethod != self::SENDING_METHOD_ID) return;

        $clientId = trim($this->config->get('smtp_clientId'));
        $secret = trim($this->config->get('smtp_secret'));
        $host = trim($this->config->get('smtp_host'));
        $connectionType = $this->config->get('smtp_type');

        $requireAuth = false;
        if (in_array($host, self::HOST_AUTH_2) && $connectionType !== 'password') {
            $requireAuth = true;
        }

        if ((empty($clientId) || empty($secret)) && $requireAuth) {
            $link = '<a class="acym_message_link" href="'.acym_completeLink('configuration#oauthParams').'">'.strtolower(acym_translation('ACYM_HERE')).'</a>';
            acym_enqueueMessage(acym_translationSprintf('ACYM_SMTP_OAUTH_WARNING', $link), 'warning');
        }
    }
}
