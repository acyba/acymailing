<?php

use AcyMailing\Libraries\acymPlugin;

class plgAcymBrevo extends acymPlugin
{
    const SENDING_METHOD_ID = 'brevo-smtp';
    const SENDING_METHOD_NAME = 'Brevo';
    const SENDING_METHOD_HOST = 'smtp-relay.brevo.com';

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = self::SENDING_METHOD_NAME;
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/brevo.png',
            'image_class' => '',
        ];
    }

    public function onAcymGetSendingMethodsHtmlSetting(&$data)
    {
        ob_start();
        ?>
		<div class="send_settings cell grid-x acym_vcenter" id="<?php echo self::SENDING_METHOD_ID; ?>_settings">
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
					<label for="brevo_identifier" class="cell shrink margin-right-1">
                        <?php echo acym_translation('ACYM_BREVO_LOGIN'); ?>
					</label>
                    <?php echo $this->getLinks(
                        'https://get.brevo.com/hbvmwg6onvve'
                    ); ?>
					<div class="margin-left-1 cell grid-x acym-grid-margin-x shrink acym_vcenter">
						<p class="cell shrink"><?php echo acym_translation('ACYM_ALREADY_HAVE_AN_ACCOUNT'); ?></p>
						<a target="_blank" class="cell shrink" href="https://app.brevo.com/settings/keys/smtp"><?php echo strtolower(acym_translation('ACYM_HERE')); ?></a>
					</div>
					<input id="brevo_identifier"
						   class="cell"
						   type="text"
						   name="config[brevo_identifier]"
						   value="<?php echo acym_escape($this->config->get('brevo_identifier', '')); ?>">
				</div>
				<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
					<label for="brevo_smtp_key" class="cell"><?php echo acym_translation('ACYM_BREVO_SMTP_KEY'); ?></label>
					<input id="brevo_smtp_key"
						   class="cell"
						   type="text"
						   name="config[brevo_smtp_key]"
						   value="<?php echo acym_escape($this->config->get('brevo_smtp_key')); ?>">
				</div>
			</div>
		</div>
        <?php
        $data['sendingMethodsHtmlSettings'][self::SENDING_METHOD_ID] = ob_get_clean();
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

        $credentials = [
            self::SENDING_METHOD_ID.'_host' => self::SENDING_METHOD_HOST,
            self::SENDING_METHOD_ID.'_username' => $sendingMethodListParams['brevo_identifier'] ?? $this->config->get('brevo_identifier', ''),
            self::SENDING_METHOD_ID.'_password' => $sendingMethodListParams['brevo_smtp_key'] ?? $this->config->get('brevo_smtp_key', ''),
        ];
    }
}
