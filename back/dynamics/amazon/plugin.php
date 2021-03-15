<?php

use AcyMailing\Libraries\acymPlugin;

class plgAcymAmazon extends acymPlugin
{
    const SENDING_METHOD_ID = 'amazon';
    const SENDING_METHOD_NAME = 'Amazon SES';
    const SENDING_METHOD_HOST = 'email-smtp.us-east-2.amazonaws.com';

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = self::SENDING_METHOD_NAME;
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/amazon.png',
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
					<label for="amazon_host" class="cell shrink">
                        <?php echo acym_translation('ACYM_AMAZON_SES_SERVER').acym_info(acym_translation('ACYM_AMAZON_SES_SERVER_DESC')); ?>
					</label>
                    <?php echo $this->getLinks(
                        'https://signin.aws.amazon.com/signin?redirect_uri=https%3A%2F%2Fportal.aws.amazon.com%2Fbilling%2Fsignup%2Fresume&client_id=signup',
                        'https://aws.amazon.com/en/ses/pricing/'
                    ); ?>
					<input id="amazon_host"
						   class="cell"
						   type="text"
						   name="config[amazon_host]"
						   value="<?php echo acym_escape($this->config->get('amazon_host', self::SENDING_METHOD_HOST)); ?>">
				</div>
				<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
					<label for="amazon_username" class="cell"><?php echo acym_translation('ACYM_AMAZON_SES_USERNAME'); ?></label>
					<input id="amazon_username"
						   class="cell"
						   type="text"
						   name="config[amazon_username]"
						   value="<?php echo acym_escape($this->config->get('amazon_username')); ?>">
				</div>
				<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
					<label for="amazon_password" class="cell"><?php echo acym_translation('ACYM_AMAZON_SES_PASSWORD'); ?></label>
					<input id="amazon_password"
						   class="cell"
						   type="text"
						   name="config[amazon_password]"
						   value="<?php echo str_repeat('*', strlen($this->config->get('amazon_password'))); ?>">
				</div>
			</div>
		</div>
        <?php
        $data['sendingMethodsHtmlSettings'][self::SENDING_METHOD_ID] = ob_get_clean();
    }

    public function onAcymGetCredentialsSendingMethod(&$credentials, $sendingMethod)
    {
        if ($sendingMethod != self::SENDING_METHOD_ID) return;

        $credentials = [
            self::SENDING_METHOD_ID.'_host' => $this->config->get(self::SENDING_METHOD_ID.'_host', ''),
            self::SENDING_METHOD_ID.'_username' => $this->config->get(self::SENDING_METHOD_ID.'_username', ''),
            self::SENDING_METHOD_ID.'_password' => $this->config->get(self::SENDING_METHOD_ID.'_password', ''),
        ];
    }
}
