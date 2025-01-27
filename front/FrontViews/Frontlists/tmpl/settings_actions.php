<?php echo acym_cancelButton();
if (!empty($data['listInformation']->id) && !empty($data['subscribersEntitySelect'])) {
    echo $data['subscribersEntitySelect'];
}
?>
<button type="submit" data-task="apply" class="cell acy_button_submit button-secondary button medium-6 large-shrink"><?php echo acym_translation('ACYM_SAVE'); ?></button>
<button type="submit" data-task="save" class="cell acy_button_submit button medium-6 large-shrink margin-right-0"><?php echo acym_translation('ACYM_SAVE_EXIT'); ?></button>
