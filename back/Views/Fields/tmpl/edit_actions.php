<?php
// context verification
?>
<div class="cell grid-x text-right grid-margin-x margin-left-0 margin-right-0 margin-y margin-bottom-0">
	<h5 class="cell medium-auto medium-text-left text-center hide-for-small-only hide-for-medium-only acym__title">
        <?php echo acym_escapeHtml(acym_translation('ACYM_CUSTOM_FIELD')); ?>
	</h5>
	<div class="cell auto hide-for-small-only hide-for-medium-only"></div>
    <?php acym_cancelButton(); ?>
	<button data-task="apply" <?php echo empty($data['translation_languages']) ? '' : 'data-before-action="languageField"'; ?>
	        class="cell button button-secondary medium-6 large-shrink acy_button_submit">
        <?php echo acym_escapeHtml(acym_translation('ACYM_SAVE')); ?>
	</button>
	<button data-task="save" <?php echo empty($data['translation_languages']) ? '' : 'data-before-action="languageField"'; ?>
	        class="cell button medium-6 large-shrink margin-right-0 acy_button_submit">
        <?php echo acym_escapeHtml(acym_translation('ACYM_SAVE_EXIT')); ?>
	</button>
</div>
