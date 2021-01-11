<?php if (!empty($data['status_toolbar'])) echo '<input type="hidden" id="acym__toolbar__statuses-value" value="'.acym_escape(json_encode($data['status_toolbar'])).'">' ?>
<div class="grid-x acym__toolbar acym__content">
	<div class="cell">
		<div class="grid-x grid-margin-x margin-y">
            <?php if (!empty($data['leftPart'])) { ?>
				<div class="large-3 medium-6 small-12 cell">
                    <?php echo $data['leftPart']; ?>
				</div>
                <?php if (!empty($data['moreOptionsPart'])) { ?>
					<button id="acym__toolbar__button-more-filters" type="button" class="medium-6 button button-secondary cell large-shrink">
						<i class="acymicon-filter"></i>
                        <?php echo acym_translation('ACYM_SHOW_FILTERS'); ?>
					</button>
                <?php } ?>
            <?php } ?>
			<div class="cell large-3 xlarge-auto show-for-large"></div>
            <?php echo $data['rightPart']; ?>
		</div>
	</div>
</div>
<div class="grid-x acym__toolbar__more-filters acym__content" style="display: none;">
	<div class="cell">
		<div class="grid-x grid-margin-x grid-margin-y margin-bottom-1">
            <?php foreach ($data['moreOptionsPart'] as $option) { ?>
				<div class="cell medium-3 margin-left-1">
                    <?php echo $option; ?>
				</div>
            <?php } ?>
		</div>
	</div>
	<div class="cell">
		<div class="grid-x grid-margin-x grid-margin-y align-right">
            <?php if (!empty($data['cleartask'])) { ?>
				<input type="hidden" name="cleartask" value="<?php echo acym_escape($data['cleartask']); ?>" />
            <?php } ?>
			<button data-task="clearFilters"
					type="button"
					class="cell medium-shrink acy_button_submit button button-secondary"><?php echo acym_translation('ACYM_CLEAR_FILTERS'); ?></button>
			<button id="acym__toolbar__more-filters-apply" type="button" class="cell medium-shrink button">
                <?php echo acym_translation('ACYM_APPLY_FILTERS'); ?>
			</button>
		</div>
	</div>
</div>

