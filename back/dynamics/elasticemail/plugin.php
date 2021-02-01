<?php

use AcyMailing\Libraries\acymPlugin;

class plgAcymElasticemail extends acymPlugin
{
    const SENDING_METHOD_ID = 'elasticemail';
    const SENDING_METHOD_NAME = 'Elastic Email';

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = self::SENDING_METHOD_NAME;
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        if ($isMailer) return;
        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/elasticemail.png',
        ];
    }

    public function onAcymGetSendingMethodsHtmlSetting(&$data)
    {
        ob_start();
        ?>
		<div id="<?php echo self::SENDING_METHOD_ID; ?>_settings" class="send_settings grid-x cell">
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="elasticemail_username" class="cell shrink margin-right-1">
                    <?php echo acym_translation('ACYM_SMTP_USERNAME'); ?>
				</label>
                <?php echo $this->getLinks('https://elasticemail.com/account#/joomla-acymailing', 'https://elasticemail.com/email-api-pricing'); ?>
				<input id="elasticemail_username"
					   class="cell"
					   type="text"
					   name="config[elasticemail_username]"
					   value="<?php echo acym_escape($this->config->get('elasticemail_username')); ?>">
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="elasticemail_password" class="cell">
                    <?php echo acym_translation('ACYM_API_KEY'); ?>
				</label>
				<input id="elasticemail_password"
					   class="cell"
					   type="text"
					   name="config[elasticemail_password]"
					   value="<?php echo str_repeat('*', strlen($this->config->get('elasticemail_password'))); ?>">
			</div>
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="elasticemail_password" class="cell large-2 medium-3">
                    <?php echo acym_translation('ACYM_SMTP_PORT'); ?>
				</label>
				<div class="cell large-10 medium-9">
                    <?php
                    $sendingMethods = [
                        '25' => '25',
                        '2525' => '2525',
                        'rest' => acym_translation('ACYM_REST_API'),
                    ];
                    echo acym_radio($sendingMethods, 'config[elasticemail_port]', $this->config->get('elasticemail_port', 'rest'), [], ['containerClass' => 'text-left']);
                    ?>
				</div>
			</div>
		</div>
        <?php
        $data['sendingMethodsHtmlSettings'][self::SENDING_METHOD_ID] = ob_get_clean();
    }
}
