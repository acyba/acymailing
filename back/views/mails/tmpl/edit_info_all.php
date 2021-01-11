<?php if ($data['mail']->type != $data['mailClass']::TYPE_OVERRIDE) { ?>
	<div class="cell medium-6">
		<label>
            <?php echo acym_translation('ACYM_NAME'); ?>
			<input name="mail[name]" type="text" class="acy_required_field" value="<?php echo acym_escape($data['mail']->name); ?>" required>
		</label>
	</div>
<?php } ?>
<div class="cell medium-6 <?php echo $data['mail']->type == $data['mailClass']::TYPE_OVERRIDE ? '' : 'medium-6'; ?>">
	<label>
        <?php echo acym_translation('ACYM_EMAIL_SUBJECT'); ?>
		<input name="mail[subject]" type="text" value="<?php echo acym_escape($data['mail']->subject); ?>" <?php echo in_array(
            $data['mail']->type,
            [$data['mailClass']::TYPE_WELCOME, $data['mailClass']::TYPE_UNSUBSCRIBE, $data['mailClass']::TYPE_AUTOMATION]
        ) ? 'required' : ''; ?>>
	</label>
</div>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php
        echo acym_translation('ACYM_FROM_NAME');
        $fromName = acym_escape(empty($data['mail']->from_name) ? '' : $data['mail']->from_name);
        ?>
		<input name="mail[from_name]" type="text" placeholder="<?php echo acym_escape($this->config->get('from_name')); ?>" value="<?php echo $fromName; ?>">
	</label>
</div>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php
        echo acym_translation('ACYM_FROM_EMAIL');
        $fromEmail = acym_escape(empty($data['mail']->from_email) ? '' : $data['mail']->from_email);
        ?>
		<input name="mail[from_email]" type="text" placeholder="<?php echo acym_escape($this->config->get('from_email')); ?>" value="<?php echo $fromEmail; ?>">
	</label>
</div>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php
        echo acym_translation('ACYM_REPLYTO_NAME');
        $replyToNameValue = acym_escape(empty($data['mail']->reply_to_name) ? '' : $data['mail']->reply_to_name);
        ?>
		<input name="mail[reply_to_name]" type="text" placeholder="<?php echo acym_escape($this->config->get('replyto_name')); ?>" value="<?php echo $replyToNameValue ?>">
	</label>
</div>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php
        echo acym_translation('ACYM_REPLYTO_EMAIL');
        $replyToEmailValue = acym_escape(empty($data['mail']->reply_to_email) ? '' : $data['mail']->reply_to_email);
        ?>
		<input name="mail[reply_to_email]" type="text" placeholder="<?php echo acym_escape($this->config->get('replyto_email')); ?>" value="<?php echo $replyToEmailValue; ?>">
	</label>
</div>

<?php if (!empty($data['langChoice'])) { ?>
	<div class="cell large-6 xlarge-3">
		<label class="cell">
            <?php
            echo acym_translation('ACYM_EMAIL_LANGUAGE');
            echo acym_info('ACYM_EMAIL_LANGUAGE_DESC');
            echo $data['langChoice'];
            ?>
		</label>
	</div>
<?php } ?>
