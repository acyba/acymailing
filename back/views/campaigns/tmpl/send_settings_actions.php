<?php
$beforeSave = '';
if (!empty($data['translation_languages'])) {
    $beforeSave = 'acym-data-before="acym_helperSelectionMultilingual.changeLanguage_campaign(acym_helperSelectionMultilingual.mainLanguage)"';
}
?>
<div class="cell grid-x margin-top-1">
	<div class="cell medium-shrink medium-margin-bottom-0 margin-bottom-1">
        <?php echo acym_backToListing(); ?>
	</div>
	<div class="cell medium-auto grid-x text-right">
		<div class="cell medium-auto"></div>
        <?php if ($data['from'] == 'create') { ?>
			<button <?php echo $beforeSave; ?>
					data-task="save"
					data-step="tests"
					type="submit"
					class="cell medium-shrink button margin-bottom-0 acy_button_submit">
                <?php echo strtoupper(acym_translation('ACYM_SAVE_CONTINUE')); ?><i class="acymicon-chevron-right"></i>
			</button>
        <?php } else { ?>
			<button <?php echo $beforeSave; ?>
					data-task="save"
					data-step="listing"
					type="submit"
					class="cell button-secondary medium-shrink button medium-margin-bottom-0 margin-right-1 acy_button_submit">
                <?php echo acym_translation('ACYM_SAVE_EXIT'); ?>
			</button>
			<button <?php echo $beforeSave; ?>
					data-task="save"
					data-step="tests"
					type="submit"
					class="cell medium-shrink button margin-bottom-0 acy_button_submit">
                <?php echo acym_translation('ACYM_SAVE_CONTINUE'); ?><i class="acymicon-chevron-right"></i>
			</button>
        <?php } ?>
	</div>
</div>
