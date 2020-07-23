<div class="cell grid-x acym__stats__empty acym__content">
	<input type="hidden" name="time_linechart" id="acym__time__linechart__input">
	<h2 class="acym__listing__empty__title text-center cell">
        <?php echo acym_translation('ACYM_DONT_HAVE_STATS_CAMPAIGN'); ?>
		<a href="<?php echo acym_completeLink('campaigns&task=edit&step=chooseTemplate'); ?>"><?php echo acym_translation('ACYM_CREATE_ONE'); ?></a>
	</h2>

	<h2 class="acym__listing__empty__subtitle text-center cell"><?php echo acym_translation('ACYM_LOOK_AT_THESE_AMAZING_DONUTS'); ?></h2>
    <?php echo $data['example_round_chart']; ?>

	<h2 class="acym__listing__empty__subtitle text-center cell"><?php echo acym_translation('ACYM_OR_THIS_AWESOME_CHART_LINE'); ?></h2>
    <?php echo $data['example_line_chart']; ?>
</div>
