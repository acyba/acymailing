<?php
// context verification
?>
<div class="cell grid-x margin-top-1">
	<div class="cell medium-shrink medium-margin-bottom-0 margin-bottom-1">
        <?php
        acym_backToListing(
            in_array($data['currentCampaign']->sending_type, ['birthday', 'woocommerce_cart'])
                ? 'campaigns&task=specificListing&type='.$data['currentCampaign']->sending_type : null
        );
        ?>
	</div>
	<div class="cell medium-auto grid-x text-right">
		<div class="cell medium-auto"></div>
        <?php if ($data['from'] === 'create') { ?>
			<button <?php echo empty($data['translation_languages']) ? '' : 'data-before-action="languageCampaign"'; ?>
					data-task="save"
					data-step="tests"
					type="submit"
					class="cell medium-shrink button margin-bottom-0 acy_button_submit">
                <?php echo acym_escapeHtml(strtoupper(acym_translation('ACYM_SAVE_CONTINUE'))); ?><i class="acymicon-chevron-right"></i>
			</button>
        <?php } else { ?>
			<button <?php echo empty($data['translation_languages']) ? '' : 'data-before-action="languageCampaign"'; ?>
					data-task="save"
					data-step="listing"
					type="submit"
					class="cell button-secondary medium-shrink button medium-margin-bottom-0 margin-next-1 acy_button_submit">
                <?php echo acym_escapeHtml(acym_translation('ACYM_SAVE_EXIT')); ?>
			</button>
			<button <?php echo empty($data['translation_languages']) ? '' : 'data-before-action="languageCampaign"'; ?>
					data-task="save"
					data-step="tests"
					type="submit"
					class="cell medium-shrink button margin-bottom-0 acy_button_submit">
                <?php echo acym_escapeHtml(acym_translation('ACYM_SAVE_CONTINUE')); ?><i class="acymicon-chevron-right"></i>
			</button>
        <?php } ?>
	</div>
</div>
