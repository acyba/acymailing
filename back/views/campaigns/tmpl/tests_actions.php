<div class="cell grid-x text-center acym__campaign__recipients__save-button cell">
	<div class="cell medium-shrink medium-margin-bottom-0 margin-bottom-1 text-left">
        <?php
        echo acym_backToListing(
            in_array($data['currentCampaign']->sending_type, ['birthday', 'woocommerce_cart'])
                ? 'campaigns&task=specificListing&type='.$data['currentCampaign']->sending_type : null
        );
        ?>
	</div>
	<div class="cell medium-auto grid-x text-right">
		<div class="cell medium-auto"></div>
		<button data-task="save"
				data-step="listing"
				type="submit"
				class="cell button-secondary medium-shrink button medium-margin-bottom-0 margin-right-1 acy_button_submit">
            <?php echo acym_translation('ACYM_SAVE_EXIT'); ?>
		</button>
		<button data-task="save" data-step="summary" type="submit" class="cell medium-shrink button margin-bottom-0 acy_button_submit">
            <?php echo acym_translation('ACYM_SAVE_CONTINUE'); ?><i class="acymicon-chevron-right"></i>
		</button>
	</div>
</div>
