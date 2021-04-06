<div class="acym__content acym__stats grid-x cell">
	<input type="hidden" name="time_linechart" id="acym__time__linechart__input">
	<div class="cell acym__stats__campaign-choose  margin-bottom-1 large-3 medium-4 small-12">
        <?php if ($data['page_title']) { ?>
			<h2 class="acym__title"><?php echo acym_translation('ACYM_GLOBAL_STATISTICS'); ?></h2>
        <?php } ?>
	</div>
	<div class="large-9 medium-8 small-12 margin-bottom-1 cell acym__stats__export grid-x align-right grid-margin-x">
        <?php if (!$data['page_title']) { ?>
			<div class="cell small-5 medium-4 large-3 xlarge-2">
                <?php
                echo acym_select(
                    [
                        'charts' => acym_translation('ACYM_CHARTS'),
                        'formatted' => acym_translation('ACYM_FORMATTED_DATA'),
                        'full' => acym_translation('ACYM_FULL_DATA'),
                    ],
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
    <?php if (!empty($data['selectedMailid']) && !empty($data['lists'])) { ?>
		<div class="cell grid-x margin-bottom-1">
			<h2 class="cell shrink acym__title acym__title__secondary"><?php echo acym_translation('ACYM_RECEIVER_LISTS'); ?></h2>
			<div class="cell grid-x auto margin-left-1">
                <?php
                foreach ($data['lists'] as $list) {
                    echo acym_tooltip('<i style="color: '.$list->color.'" class="acym_subscription acymicon-circle"></i>', $list->name);
                }
                ?>
			</div>
			<h2 class="cell acym__title acym__title__secondary">
                <?php echo acym_translationSprintf('ACYM_NUMBER_OF_RECEIVERS_X', $data['mail']->sent); ?>
			</h2>
		</div>
    <?php } ?>
    <?php if (empty($data['sentMails'])) { ?>
		<h2 class="acym__listing__empty__title text-center cell margin-bottom-1">
            <?php echo acym_translation('ACYM_DONT_HAVE_STATS_CAMPAIGN'); ?>
			<a href="<?php echo acym_completeLink('campaigns&task=edit&step=chooseTemplate'); ?>"><?php echo acym_translation('ACYM_CREATE_ONE'); ?></a>
		</h2>
    <?php } ?>
	<div class="cell grid-x grid-margin-y" id="acym__stats__export__global__charts__scope">
		<div class="cell grid-x acym__content acym__stats__donut__chart align-center">
			<h2 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_EMAIL_STATISTICS'); ?></h2>
            <?php if (empty($data['sentMails'])) { ?>
				<h4 class="cell acym__subtitle__stats text-center"><b><?php echo acym_translation('ACYM_LOOK_AT_THESE_AMAZING_DONUTS'); ?></b></h4>
            <?php } ?>
			<div class="acym__stats__donut__one-chart cell large-2 medium-4 small-12">
                <?php
                echo acym_roundChart(
                    '',
                    $data['mail']->pourcentageSent,
                    'delivery',
                    '',
                    acym_tooltip(acym_translation('ACYM_SUCCESSFULLY_SENT'), $data['mail']->allSent)
                ); ?>
			</div>
			<div class="acym__stats__donut__one-chart cell large-2 medium-4 small-12">
                <?php
                echo acym_roundChart('', $data['mail']->pourcentageOpen, 'open', '', acym_tooltip(acym_translation('ACYM_OPEN_RATE'), $data['mail']->allOpen)); ?>
			</div>
			<div class="acym__stats__donut__one-chart cell large-2 medium-4 small-12">
                <?php
                echo acym_roundChart('', $data['mail']->pourcentageClick, 'click', '', acym_tooltip(acym_translation('ACYM_CLICK_RATE'), $data['mail']->allClick)); ?>
			</div>
			<div class="acym__stats__donut__one-chart cell large-2 medium-4 small-12">
                <?php
                echo acym_roundChart(
                    '',
                    $data['mail']->pourcentageBounce,
                    'bounce',
                    '',
                    acym_tooltip(acym_translation('ACYM_BOUNCE_RATE'), $data['mail']->allBounce)
                ); ?>
			</div>
            <?php if (!empty($data['selectedMailid'])) { ?>
				<div class="acym__stats__donut__one-chart cell large-2 medium-4 small-12">
                    <?php
                    echo acym_roundChart(
                        '',
                        $data['mail']->pourcentageUnsub,
                        'unsubscribe',
                        '',
                        acym_tooltip(acym_translation('ACYM_UNSUBSCRIBE_RATE'), $data['mail']->allUnsub)
                    ); ?>
				</div>
            <?php } ?>
		</div>
		<div class="cell grid-x acym__content acym__stats__pie__chart">
			<h2 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_OPENING_PLATFORMS') ?></h2>
            <?php if (empty($data['devices'])) { ?>
				<h4 class="cell acym__subtitle__stats text-center"><b><?php echo acym_translation('ACYM_EMAIL_NOT_OPEN_EXAMPLE_STATS') ?></b></h4>
				<div class="cell large-3 hide-for-small-only hide-for-medium-only"></div>
				<div class="acym__stats__pie__one-chart cell large-3 medium-6">
                    <?php echo $data['example_devices_chart']; ?>
				</div>
				<div class="acym__stats__pie__one-chart cell large-3 medium-6">
                    <?php echo $data['example_source_chart']; ?>
				</div>
				<div class="cell large-3 hide-for-small-only hide-for-medium-only"></div>
            <?php } else { ?>
				<div class="cell large-3 hide-for-small-only hide-for-medium-only"></div>
				<div class="acym__stats__pie__one-chart cell large-3 medium-6">
                    <?php echo acym_pieChart('', $data['devices'], 'devices', acym_translation('ACYM_DEVICES')); ?>
				</div>
				<div class="acym__stats__pie__one-chart cell large-3 medium-6">
                    <?php echo acym_pieChart('', $data['openedWith'], 'opened_with', acym_translation('ACYM_OPENED_WITH')); ?>
				</div>
				<div class="cell large-3 hide-for-small-only hide-for-medium-only"></div>
            <?php } ?>
		</div>
		<div class="cell acym__content grid-x">
			<h2 class="cell shrink acym__title acym__title__secondary">
                <?php echo acym_translation('ACYM_OPEN_TIME_CHART').acym_info('ACYM_OPEN_TIME_CHART_DESC'); ?>
			</h2>
            <?php if ($data['empty_open']) { ?>
				<h4 class="cell acym__subtitle__stats text-center"><b><?php echo acym_translation('ACYM_EMAIL_NOT_OPEN_EXAMPLE_STATS') ?></b></h4>
            <?php } ?>
			<div class="cell grid-x" id="acym__stats__global__open-time">
				<input type="hidden" id="acym__stats__global__open-time__data" value="<?php echo acym_escape(json_encode($data['openTime'])); ?>">
				<canvas id="chartjs-0" height="400" width="400"></canvas>
			</div>
		</div>
		<div class="cell grid-x acym__content acym__stats__chart__line">
			<h2 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_OPEN_CLICK_RATE'); ?></h2>
            <?php if (!$data['mail']->hasStats) { ?>
				<h4 class="acym__subtitle__stats cell text-center">
					<b><?php echo acym_translation('ACYM_YOU_DONT_HAVE_ANY_OPEN_CLIC_DATA_YET'); ?></b>
					<br>
                    <?php echo acym_translation('ACYM_HERE_AN_EXEMPLE_OF_WHAT_YOU_CAN_GET'); ?>
				</h4>
                <?php
                echo $data['example_line_chart'];
            } else {
                if ($data['show_date_filters']) {
                    ?>
					<div class="cell grid-x margin-bottom-1">
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
					</div>
                <?php } ?>
				<div id="acym__stats__chart__line__canvas" class="cell">
                    <?php echo acym_lineChart('', $data['mail']->month, $data['mail']->day, $data['mail']->hour); ?>
				</div>
            <?php } ?>
		</div>
	</div>
</div>
