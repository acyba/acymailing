<?php
// context verification
?>
<div class="cell grid-x text-right grid-margin-x margin-bottom-0 margin-y">
	<h5 class="cell medium-auto medium-text-left text-center hide-for-small-only hide-for-medium-only acym__title"><?php echo acym_escapeHtml(
            acym_translation('ACYM_SUBSCRIBER')
        ); ?></h5>
    <?php
    acym_cancelButton();
    if (!empty($data['entityselectContent'])) {
        acym_modal(
            acym_translation('ACYM_MANAGE_SUBSCRIPTION'),
            $data['entityselectContent'],
            null,
            [],
            ['class' => 'cell medium-6 large-shrink button button-secondary']
        );
    }
    ?>
	<button type="submit" data-task="apply" class="cell medium-6 large-shrink acy_button_submit button-secondary button"><?php echo acym_escapeHtml(
            acym_translation('ACYM_SAVE')
        ); ?></button>
	<button type="submit" data-task="save" class="cell medium-6 large-shrink acy_button_submit button"><?php echo acym_escapeHtml(acym_translation('ACYM_SAVE_EXIT')); ?></button>
</div>
