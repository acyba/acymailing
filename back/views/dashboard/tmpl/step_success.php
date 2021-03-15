<h2 class="cell acym__title text-center margin-bottom-2"><?php echo acym_translation('ACYM_WALKTHROUGH_SUCCESS'); ?></h2>

<div class="cell margin-top-2 margin-bottom-2">
	<img src="<?php echo ACYM_IMAGES.'happy_man_1.png'; ?>" alt="happy man" id="acym__walkthrough__success__img">
</div>

<div class="cell margin-top-2 margin-bottom-3">
	<p class="acym__walkthrough__text">
        <?php echo acym_translation('ACYM_WALK_SUCCESS_1'); ?><br />
        <?php echo acym_translation('ACYM_WALK_SUCCESS_2'); ?><br />
	</p>
</div>

<div class="cell margin-top-3">
	<button type="button" class="acy_button_submit button" data-task="saveStepSupportSubForm"><?php echo acym_translation('ACYM_CREATE_FORM'); ?></button>
	<button type="button" class="acy_button_submit button button-secondary" data-task="saveStepSupportImport"><?php echo acym_translation('ACYM_IMPORT_SUBSCRIBERS'); ?></button>
</div>
