<div class="cell medium-6">
	<label>
        <?php echo acym_translation('ACYM_NAME'); ?>
		<input name="mail[name]" type="text" class="acy_required_field" value="<?php echo acym_escape($data['mail']->name); ?>" required>
	</label>
</div>
<div class="cell medium-6">
	<label>
        <?php echo acym_translation('ACYM_EMAIL_SUBJECT'); ?>
		<input name="mail[subject]" type="text" value="<?php echo acym_escape($data['mail']->subject); ?>" <?php echo in_array($data['mail']->type, ['welcome', 'unsubscribe', 'automation']) ? 'required' : ''; ?>>
	</label>
</div>
<div class="cell"></div>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php echo acym_translation('ACYM_FROM_NAME'); ?>
		<input name="mail[from_name]" type="text" value="<?php echo acym_escape(empty($data['mail']->from_name) ? $this->config->get('from_name') : $data['mail']->from_name); ?>">
	</label>
</div>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php echo acym_translation('ACYM_FROM_EMAIL'); ?>
		<input name="mail[from_email]" type="text" value="<?php echo acym_escape(empty($data['mail']->from_email) ? $this->config->get('from_email') : $data['mail']->from_email); ?>">
	</label>
</div>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php echo acym_translation('ACYM_REPLYTO_NAME'); ?>
		<input name="mail[reply_to_name]" type="text" value="<?php echo acym_escape(empty($data['mail']->reply_to_name) ? $this->config->get('replyto_name') : $data['mail']->reply_to_name); ?>">
	</label>
</div>
<div class="cell xlarge-3 medium-6">
	<label>
        <?php echo acym_translation('ACYM_REPLYTO_EMAIL'); ?>
		<input name="mail[reply_to_email]" type="text" value="<?php echo acym_escape(empty($data['mail']->reply_to_email) ? $this->config->get('replyto_email') : $data['mail']->reply_to_email); ?>">
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
