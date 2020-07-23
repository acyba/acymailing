<div class="cell grid-x text-right grid-margin-x align-right">
    <?php
    echo acym_cancelButton();
    if (!empty($data['entityselect'])) echo $data['entityselect'];
    ?>
	<button type="submit" data-task="apply" class="cell medium-6 large-shrink acy_button_submit button-secondary button"><?php echo acym_translation('ACYM_SAVE'); ?></button>
	<button type="submit" data-task="save" class="cell medium-6 large-shrink acy_button_submit button"><?php echo acym_translation('ACYM_SAVE_EXIT'); ?></button>
</div>
