<?php

use AcyMailing\Libraries\acymPlugin;

class plgAcymSmtp extends acymPlugin
{
    const SENDING_METHOD_ID = 'smtp';
    const SENDING_METHOD_NAME = 'SMTP';

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
        ob_start();
        ?>
		<div id="<?php echo self::SENDING_METHOD_ID; ?>_settings" class="send_settings grid-x cell">
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="smtp_host" class="cell"><?php echo acym_translation('ACYM_SMTP_SERVER'); ?></label>
				<input id="smtp_host" class="cell" type="text" name="config[smtp_host]" value="<?php echo acym_escape($this->config->get('smtp_host')); ?>">
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="smtp_port" class="cell"><?php echo acym_translation('ACYM_SMTP_PORT').acym_info('ACYM_SMTP_PORT_DESC'); ?></label>
				<input
						id="smtp_port"
						class="cell"
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
				<div class="cell medium-2">
                    <?php
                    $secureMethods = [
                        '' => '- - -',
                        'ssl' => 'SSL',
                        'tls' => 'TLS',
                    ];
                    echo acym_select(
                        $secureMethods,
                        'config[smtp_secured]',
                        $this->config->get('smtp_secured', ''),
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
                    'large-2 medium-3 small-9'
                );
                ?>
			</div>

			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
                <?php
                echo acym_switch(
                    'config[smtp_auth]',
                    $this->config->get('smtp_auth'),
                    acym_translation('ACYM_SMTP_AUTHENTICATION').acym_info('ACYM_SMTP_AUTHENTICATION_DESC'),
                    [],
                    'large-2 medium-3 small-9'
                );
                ?>
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="smtp_username" class="cell"><?php echo acym_translation('ACYM_SMTP_USERNAME').acym_info('ACYM_SMTP_USERNAME_DESC'); ?></label>
				<input id="smtp_username"
					   class="cell"
					   type="text"
					   name="config[smtp_username]"
					   value="<?php echo acym_escape($this->config->get('smtp_username')); ?>">
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="smtp_password" class="cell"><?php echo acym_translation('ACYM_SMTP_PASSWORD').acym_info('ACYM_SMTP_PASSWORD_DESC'); ?></label>
				<input id="smtp_password"
					   class="cell"
					   type="text"
					   name="config[smtp_password]"
					   value="<?php echo str_repeat('*', strlen($this->config->get('smtp_password'))); ?>">
			</div>
		</div>
        <?php
        $data['sendingMethodsHtmlSettings'][self::SENDING_METHOD_ID] = ob_get_clean();
    }


    public function onAcymGetSettingsSendingMethodFromPlugin(&$data, $plugin, $method)
    {
        if ($method != self::SENDING_METHOD_ID) return;

        //__START__wordpress_
        if (ACYM_CMS == 'wordpress' && $plugin == 'wp_mail_smtp') {
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
}
