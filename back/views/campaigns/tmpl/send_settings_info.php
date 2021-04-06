<h5 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_SENDER_INFORMATION'); ?></h5>
<div class="cell grid-x align-center">
    <?php
    if (!empty($data['translation_languages'])) {
        echo acym_displayLanguageRadio(
            $data['translation_languages'],
            'senderInformation[translation]',
            $data['currentCampaign']->translation,
            acym_translation('ACYM_LANGUAGE_SENDER_INFORMATION_DESC'),
            $this->config->get('sender_info_translation', '')
        );
    }
    ?>
	<div class="cell grid-x medium-11 grid-margin-x margin-y">
		<div class="cell medium-5">
			<label for="acym__campaign__sendsettings__from-name" class="cell acym__campaign__sendsettings__label-settings">
                <?php
                echo acym_translation('ACYM_FROM_NAME');
                if (empty($data['config_values']->from_name)) {
                    $placeholder = acym_translation('ACYM_DEFAULT_VALUE');
                } else {
                    $placeholder = acym_translation('ACYM_DEFAULT').' : '.acym_escape($data['config_values']->from_name);
                }
                ?>
			</label>
			<input type="text"
				   id="acym__campaign__sendsettings__from-name"
				   class="cell"
				   maxlength="100"
				   value="<?php echo acym_escape($data['senderInformations']->from_name); ?>"
				   name="senderInformation[from_name]"
				   placeholder="<?php echo acym_escape($placeholder); ?>">
		</div>
		<div class="cell medium-1"></div>
		<div class="cell medium-5">
			<label for="acym__campaign__sendsettings__from-email" class="cell acym__campaign__sendsettings__label-settings">
                <?php
                echo acym_translation('ACYM_FROM_EMAIL');
                if (empty($data['config_values']->from_email)) {
                    $placeholder = acym_translation('ACYM_DEFAULT_VALUE');
                } else {
                    $placeholder = acym_translation('ACYM_DEFAULT').' : '.acym_escape($data['config_values']->from_email);
                }
                ?>
			</label>
			<input type="email"
				   id="acym__campaign__sendsettings__from-email"
				   class="cell"
				   maxlength="100"
				   value="<?php echo acym_escape($data['senderInformations']->from_email); ?>"
				   name="senderInformation[from_email]"
				   placeholder="<?php echo acym_escape($placeholder); ?>">
		</div>

		<div class="cell medium-5">
			<label for="acym__campaign__sendsettings__reply-name" class="cell acym__campaign__sendsettings__label-settings">
                <?php
                echo acym_translation('ACYM_REPLYTO_NAME');
                if (empty($data['config_values']->reply_to_name)) {
                    $placeholder = acym_translation('ACYM_DEFAULT_VALUE');
                } else {
                    $placeholder = acym_translation('ACYM_DEFAULT').' : '.acym_escape($data['config_values']->reply_to_name);
                }
                ?>
			</label>
			<input type="text"
				   id="acym__campaign__sendsettings__reply-name"
				   class="cell"
				   maxlength="100"
				   value="<?php echo acym_escape($data['senderInformations']->reply_to_name); ?>"
				   name="senderInformation[reply_to_name]"
				   placeholder="<?php echo acym_escape($placeholder); ?>">
		</div>
		<div class="cell medium-1"></div>
		<div class="cell medium-5">
			<label for="acym__campaign__sendsettings__reply-email" class="cell acym__campaign__sendsettings__label-settings">
                <?php
                echo acym_translation('ACYM_REPLYTO_EMAIL');
                if (empty($data['config_values']->reply_to_email)) {
                    $placeholder = acym_translation('ACYM_DEFAULT_VALUE');
                } else {
                    $placeholder = acym_translation('ACYM_DEFAULT').' : '.acym_escape($data['config_values']->reply_to_email);
                }
                ?>
			</label>
			<input type="email"
				   id="acym__campaign__sendsettings__reply-email"
				   class="cell"
				   maxlength="100"
				   value="<?php echo acym_escape($data['senderInformations']->reply_to_email); ?>"
				   name="senderInformation[reply_to_email]"
				   placeholder="<?php echo acym_escape($placeholder); ?>">
		</div>

		<div class="cell medium-5 grid-x acym__campaign__sendsettings__bcc">
			<label for="acym__campaign__sendsettings__bcc--input" class="cell acym__campaign__sendsettings__label-settings">
                <?php echo acym_translation('ACYM_BCC').' '.acym_info('ACYM_BCC_DESC'); ?>
			</label>
			<input type="text"
				   class="cell"
				   id="acym__campaign__sendsettings__bcc--input"
				   name="senderInformation[bcc]"
				   placeholder="<?php echo acym_escape(acym_translation('ACYM_TEST_ADDRESS')); ?>"
				   value="<?php echo acym_escape($data['currentCampaign']->bcc); ?>">
		</div>
	</div>
</div>
