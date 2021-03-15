<h5 class="cell auto medium-text-left text-center hide-for-small-only hide-for-medium-only acym__title"><?php echo acym_translation('ACYM_LIST'); ?></h5>
<?php echo acym_cancelButton();
$beforeSave = '';
if (!empty($data['translation_languages'])) {
    $beforeSave = 'acym-data-before="acym_helperSelectionMultilingual.changeLanguage_list(acym_helperSelectionMultilingual.mainLanguage)"';
}
if (!empty($data['listInformation']->id) && !empty($data['subscribersEntitySelect'])) {
    echo $data['subscribersEntitySelect'];
}
?>
<button <?php echo $beforeSave; ?>
		type="submit"
		data-task="apply"
		class="cell acy_button_submit button-secondary button medium-6 large-shrink">
    <?php echo acym_translation('ACYM_SAVE'); ?>
</button>
<button <?php echo $beforeSave; ?>
		type="submit"
		data-task="save"
		class="cell acy_button_submit button medium-6 large-shrink margin-right-0">
    <?php echo acym_translation('ACYM_SAVE_EXIT'); ?>
</button>
