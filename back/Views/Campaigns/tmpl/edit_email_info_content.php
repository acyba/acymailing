<div class="cell large-6">
	<label>
        <?php echo acym_translation('ACYM_EMAIL_SUBJECT'); ?>
		<div class="margin-bottom-0 grid-x">
			<input id="acym_subject_field"
				   name="mail[subject]"
				   type="text"
				   class="cell auto acy_required_field"
				   value="<?php echo acym_escape($data['mailInformation']->subject); ?>"
				   required>
		</div>
	</label>
</div>
<div class="cell <?php echo $preheaderSize; ?>">
	<label>
        <?php
        echo acym_translation('ACYM_EMAIL_PREHEADER');
        echo acym_info('ACYM_EMAIL_PREHEADER_DESC');
        ?>
		<input id="acym_preheader_field" name="mail[preheader]" type="text" maxlength="255" value="<?php echo acym_escape($data['mailInformation']->preheader); ?>">
	</label>
</div>
