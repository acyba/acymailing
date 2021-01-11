<?php

use AcyMailing\Libraries\acymPlugin;

class plgAcymSendmail extends acymPlugin
{
    const SENDING_METHOD_ID = 'sendmail';
    const SENDING_METHOD_NAME = 'SendMail';

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = self::SENDING_METHOD_NAME;
    }

    public function onAcymGetSendingMethods(&$data, $isMailer = false)
    {
        $data['sendingMethods'][self::SENDING_METHOD_ID] = [
            'name' => $this->pluginDescription->name,
            'image' => ACYM_IMAGES.'mailers/phpmail.svg',
            'image_class' => 'acym__selection__card__image__smaller',
        ];
    }

    public function onAcymGetSendingMethodsHtmlSetting(&$data)
    {
        ob_start();
        ?>
		<div id="<?php echo self::SENDING_METHOD_ID; ?>_settings" class="send_settings grid-x cell">
			<div class="cell grid-x acym_vcenter acym__sending__methods__one__settings">
				<label for="<?php echo self::SENDING_METHOD_ID; ?>_path" class="cell">
                    <?php echo acym_translation('ACYM_SENDMAIL_PATH'); ?>
				</label>
				<input id="<?php echo self::SENDING_METHOD_ID; ?>_path"
					   class="cell"
					   type="text"
					   name="config[<?php echo self::SENDING_METHOD_ID; ?>_path]"
					   value="<?php echo acym_escape($this->config->get(self::SENDING_METHOD_ID.'_path', '/usr/sbin/sendmail')); ?>">
			</div>
		</div>
        <?php
        $data['sendingMethodsHtmlSettings'][self::SENDING_METHOD_ID] = ob_get_clean();
    }
}
