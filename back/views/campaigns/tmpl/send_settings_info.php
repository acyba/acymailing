<h5 class="cell acym__campaign__sendsettings__title-settings"><?php echo acym_translation('ACYM_SENDER_INFORMATION'); ?></h5>
<div class="cell grid-x align-center">
	<div class="cell grid-x medium-11 grid-margin-x">
		<div class="cell medium-5">
			<label for="acym__campaign__sendsettings__from-name" class="cell acym__campaign__sendsettings__label-settings"><?php echo acym_translation('ACYM_FROM_NAME'); ?></label>
			<input type="text" id="acym__campaign__sendsettings__from-name" class="cell acym__light__input" value="<?php echo acym_escape($data['senderInformations']->from_name); ?>" name="senderInformation[from_name]" placeholder="<?php echo acym_escape($data['senderInformations']->from_name) == '' ? empty($data['config_values']->from_name) ? 'Default Value' : 'Default : '.acym_escape($data['config_values']->from_name) : ''; ?>">
		</div>
		<div class="cell medium-1"></div>
		<div class="cell medium-5">
			<label for="acym__campaign__sendsettings__from-email" class="cell acym__campaign__sendsettings__label-settings"><?php echo acym_translation('ACYM_FROM_EMAIL'); ?></label>
			<input type="email" id="acym__campaign__sendsettings__from-email" class="cell acym__light__input" value="<?php echo acym_escape($data['senderInformations']->from_email); ?>" name="senderInformation[from_email]" placeholder="<?php echo acym_escape($data['senderInformations']->from_email == '' ? empty($data['config_values']->from_email) ? 'Default Value' : 'Default : '.acym_escape($data['config_values']->from_email) : ''); ?>">
		</div>

		<div class="cell medium-5">
			<label for="acym__campaign__sendsettings__reply-name" class="cell acym__campaign__sendsettings__label-settings"><?php echo acym_translation('ACYM_REPLYTO_NAME'); ?></label>
			<input type="text" id="acym__campaign__sendsettings__reply-name" class="cell acym__light__input" value="<?php echo acym_escape($data['senderInformations']->reply_to_name); ?>" name="senderInformation[reply_to_name]" placeholder="<?php echo acym_escape($data['senderInformations']->reply_to_name == '' ? empty($data['config_values']->reply_to_name) ? 'Default Value' : 'Default : '.acym_escape($data['config_values']->reply_to_name) : ''); ?>">
		</div>
		<div class="cell medium-1"></div>
		<div class="cell medium-5">
			<label for="acym__campaign__sendsettings__reply-email" class="cell acym__campaign__sendsettings__label-settings"><?php echo acym_translation('ACYM_REPLYTO_EMAIL'); ?></label>
			<input type="email" id="acym__campaign__sendsettings__reply-email" class="cell acym__light__input" value="<?php echo acym_escape($data['senderInformations']->reply_to_email); ?>" name="senderInformation[reply_to_email]" placeholder="<?php echo acym_escape($data['senderInformations']->reply_to_email == '' ? empty($data['config_values']->reply_to_email) ? 'Default Value' : 'Default : '.acym_escape($data['config_values']->reply_to_email) : ''); ?>">
		</div>

		<div class="cell medium-5 grid-x acym__campaign__sendsettings__bcc">
			<label for="acym__campaign__sendsettings__bcc--input" class="cell acym__campaign__sendsettings__label-settings"><?php echo acym_translation('ACYM_BCC').' '.acym_info('ACYM_BCC_DESC'); ?></label>
			<input type="text" class="cell acym__light__input" id="acym__campaign__sendsettings__bcc--input" name="senderInformation[bcc]" placeholder="<?php echo acym_translation('ACYM_TEST_ADDRESS'); ?>" value="<?php echo acym_escape($data['currentCampaign']->bcc); ?>">
		</div>
	</div>
</div>