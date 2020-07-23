<div class="grid-x grid-margin-x">
	<div class="large-5 medium-8 cell">
        <?php echo acym_filterSearch($data['search'], 'lists_search', 'ACYM_SEARCH'); ?>
	</div>
	<div class="large-auto medium-4 cell">
	</div>
	<div class="medium-shrink cell">
		<button data-task="settings" class="button acy_button_submit"><?php echo acym_translation('ACYM_CREATE_NEW_LIST'); ?></button>
	</div>
</div>