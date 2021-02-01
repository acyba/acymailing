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

<?php
if (empty($data['multilingual'])) {
    $preheaderSize = 'large-6';
    include acym_getView('campaigns', 'edit_email_info_content');
} ?>

<div class="cell grid-x">
    <?php
    echo acym_switch('visible', $data['mailInformation']->visible, acym_translation('ACYM_VISIBLE').acym_info('ACYM_VISIBLE_CAMPAIGN_DESC'), [], 'shrink')
    ?>
</div>
