<div class="acym__content acym__stats grid-x cell">
	<input type="hidden" name="time_linechart" id="acym__time__linechart__input">
	<div class="cell acym__stats__campaign-choose  margin-bottom-1 large-3 medium-4 small-12">
        <?php if ($data['page_title']) { ?>
			<h2 class="acym__stats__all__campaigns__dashboard"><?php echo acym_translation('ACYM_GLOBAL_STATISTICS'); ?></h2>
        <?php } ?>
	</div>
	<div class="large-9 medium-8 small-12 margin-bottom-1 cell acym__stats__export grid-x align-right grid-margin-x">
        <?php if (!$data['page_title']) { ?>
			<div class="cell shrink">
                <?php
                echo acym_select(
                    ['charts' => acym_translation('ACYM_CHARTS'), 'formatted' => acym_translation('ACYM_FORMATTED_DATA'), 'full' => acym_translation('ACYM_FULL_DATA')],
                    'export_type',
                    'charts',
                    'class="acym__select"'
                );
                ?>
			</div>
			<button type="button"
					class="cell shrink button primary acym__stats__export__button acym__stats__export__global__charts"
					data-task="exportGlobal"><?php echo acym_translation('ACYM_EXPORT'); ?></button>
        <?php } ?>
	</div>

	<div class="cell grid-x" id="acym__stats__export__global__charts__scope">
		<div class="cell grid-x acym__stats__donut__chart">
			<div class="acym__stats__donut__one-chart cell large-2 medium-4 small-12">
                <?php
                echo acym_round_chart('', $data['mail']->pourcentageSent, 'delivery', '', acym_tooltip(acym_translation('ACYM_SUCCESSFULLY_SENT'), $data['mail']->allSent)); ?>
			</div>
			<div class="acym__stats__donut__one-chart cell large-2 medium-4 small-12">
                <?php
                echo acym_round_chart('', $data['mail']->pourcentageOpen, 'open', '', acym_tooltip(acym_translation('ACYM_OPEN_RATE'), $data['mail']->allOpen)); ?>
			</div>
			<div class="acym__stats__donut__one-chart cell large-2 medium-4 small-12">
                <?php
                echo acym_round_chart('', $data['mail']->pourcentageClick, 'click', '', acym_tooltip(acym_translation('ACYM_CLICK_RATE'), $data['mail']->allClick)); ?>
			</div>
			<div class="acym__stats__donut__one-chart cell large-2 medium-4 small-12">
                <?php
                echo acym_round_chart('', $data['mail']->pourcentageBounce, 'bounce', '', acym_tooltip(acym_translation('ACYM_BOUNCE_RATE'), $data['mail']->allBounce)); ?>
			</div>
		</div>

		<div class="cell grid-x acym__stats__chart__line">
            <?php if (!$data['mail']->hasStats) { ?>
				<h2 class="acym__stats__empty__title__chart__line cell text-center"><?php echo acym_translation('ACYM_YOU_DONT_HAVE_ANY_DATA_ON_THIS_CAMPAIGN'); ?></h2>
				<h2 class="acym__stats__empty__title__chart__line cell text-center"><?php echo acym_translation('ACYM_HERE_AN_EXEMPLE_OF_WHAT_YOU_CAN_GET'); ?></h2>
                <?php
                echo $data['example_line_chart'];
            } else {
                if ($data['show_date_filters']) {
                    ?>
					<div class="cell large-auto "></div>
					<label class="cell grid-x large-3 medium-6 small-12 acym__stats__chart__date ">
						<p class="cell shrink"><?php echo acym_translation('ACYM_START'); ?></p>
						<input
								class="acy_date_picker auto cell text-center acym__stats__chart__line__input__date"
								id="chart__line__start"
								type="text"
								data-acym-translate="0"
								data-start="<?php echo acym_escape($data['mail']->startEndDateHour['start']); ?>">
					</label>
					<label class="cell grid-x large-3 medium-6 small-12 acym__stats__chart__date ">
						<p class="cell shrink"><?php echo acym_translation('ACYM_END'); ?></p>
						<input
								class="acy_date_picker auto cell text-center acym__stats__chart__line__input__date"
								id="chart__line__end"
								type="text"
								data-acym-translate="0"
								data-end="<?php echo acym_escape($data['mail']->startEndDateHour['end']); ?>">
					</label>
                <?php } ?>
				<div id="acym__stats__chart__line__canvas" class="cell">
                    <?php echo acym_line_chart('', $data['mail']->month, $data['mail']->day, $data['mail']->hour); ?>
				</div>
            <?php } ?>
		</div>
	</div>
</div>
