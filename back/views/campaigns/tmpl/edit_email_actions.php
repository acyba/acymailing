<div class="cell grid-x text-center acym__campaign__email__save-button">
	<div class="cell medium-shrink medium-margin-bottom-0 margin-bottom-1 text-left">
        <?php echo acym_backToListing(); ?>
	</div>
	<div class="cell medium-auto grid-x text-right">
		<div class="cell medium-auto"></div>
        <?php if (!empty($data['campaignID'])) { ?>
			<button <?php echo $data['before-save']; ?> data-task="save"
														data-step="listing"
														type="submit"
														class="cell button-secondary medium-shrink button medium-margin-bottom-0 margin-right-1 acy_button_submit">
                <?php echo acym_translation('ACYM_SAVE_EXIT'); ?>
			</button>
        <?php } ?>
		<button <?php echo $data['before-save']; ?> data-task="save" data-step="recipients" type="submit" class="cell medium-shrink button margin-bottom-0 acy_button_submit">
            <?php echo acym_translation('ACYM_SAVE_CONTINUE'); ?><i class="acymicon-chevron-right"></i>
		</button>
	</div>
</div>
