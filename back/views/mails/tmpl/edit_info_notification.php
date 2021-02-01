<input type="hidden" name="notification" value="<?php echo acym_escape($data['mail']->name); ?>" />

<?php if (empty($data['multilingual'])) { ?>
	<div class="cell<?php if (!empty($data['langChoice'])) echo ' large-7 xlarge-8'; ?>">
		<label>
            <?php echo acym_translation('ACYM_EMAIL_SUBJECT'); ?>
			<input name="mail[subject]" type="text" value="<?php echo acym_escape($data['mail']->subject); ?>" <?php echo in_array(
                $data['mail']->type,
                [$data['mailClass']::TYPE_WELCOME, $data['mailClass']::TYPE_UNSUBSCRIBE, $data['mailClass']::TYPE_AUTOMATION]
            ) ? 'required' : ''; ?>>
		</label>
	</div>
<?php } ?>
<?php if (!empty($data['langChoice'])) { ?>
	<div class="cell large-5 xlarge-4">
		<label class="cell">
            <?php
            echo acym_translation('ACYM_EMAIL_LANGUAGE');
            echo acym_info('ACYM_EMAIL_LANGUAGE_DESC');
            echo $data['langChoice'];
            ?>
		</label>
	</div>
<?php } ?>
