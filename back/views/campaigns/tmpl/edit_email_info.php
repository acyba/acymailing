<div class="cell large-6">
	<label>
        <?php echo acym_translation('ACYM_CAMPAIGN_NAME'); ?>
		<input name="mail[name]" type="text" value="<?php echo acym_escape($data['mailInformation']->name); ?>">
	</label>
</div>
<div class="cell large-6">
	<label>
        <?php
        echo acym_translation('ACYM_TAGS');
        echo acym_selectMultiple(
            $data['allTags'],
            'template_tags',
            empty($data['mailInformation']->tags) ? [] : $data['mailInformation']->tags,
            [
                'id' => 'acym__tags__field',
                'placeholder' => acym_translation('ACYM_ADD_TAGS'),
            ],
            'name',
            'name'
        );
        ?>
	</label>
</div>
<div class="cell large-6">
	<label>
        <?php echo acym_translation('ACYM_EMAIL_SUBJECT'); ?>
		<div class="input-group margin-bottom-0">
			<input id="acym_subject_field" name="mail[subject]" type="text" class="acy_required_field" value="<?php echo acym_escape($data['mailInformation']->subject); ?>" required>
            <?php if ($data['editor']->editor == 'acyEditor') { ?>
				<button class="button" id="dtext_subject_button"><i class="mce-ico mce-i-codesample"></i></button>
            <?php } ?>
		</div>
	</label>
</div>
<div class="cell large-6">
	<label>
        <?php
        echo acym_translation('ACYM_EMAIL_PREHEADER');
        echo acym_info('ACYM_EMAIL_PREHEADER_DESC');
        ?>
		<input id="acym_preheader_field" name="mail[preheader]" type="text" maxlength="255" value="<?php echo acym_escape($data['mailInformation']->preheader); ?>">
	</label>
</div>
