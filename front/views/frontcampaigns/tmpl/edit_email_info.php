<div class="cell large-6">
	<label>
        <?php echo acym_translation('ACYM_CAMPAIGN_NAME'); ?>
		<input name="mail[name]" type="text" value="<?php echo acym_escape($data['mailInformation']->name); ?>">
	</label>
</div>
<?php
if (empty($data['multilingual'])) {
    $preheaderSize = '';
    include acym_getView('campaigns', 'edit_email_info_content', true);
}
