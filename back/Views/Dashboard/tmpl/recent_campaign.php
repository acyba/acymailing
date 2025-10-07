<?php
$clickRate = $data['stats']['ACYM_CLICK_RATE']['value'];
$openRate = $data['stats']['ACYM_OPEN_RATE']['value'];
?>

<div class="acym__content cell">
	<div class="cell acym__title acym__dashboard__title"><?php echo acym_translation('ACYM_RECENT_CAMPAIGNS'); ?></div>
	<span class="separator"></span>
    <?php if ($data['recent_campaigns']) { ?>
		<div class="grid-x acym__listing cell">
			<div class="grid-x cell acym__listing__header">
				<div class="grid-x medium-auto small-12">
					<div class="cell small-6 acym__listing__header__title">
                        <?php echo acym_translation('ACYM_CAMPAIGN'); ?>
					</div>
					<div class="cell small-2 acym__listing__header__title">
                        <?php echo acym_translation('ACYM_TOTAL_SUBSCRIBERS_COLUMN_STAT'); ?>
					</div>
					<div class="cell small-2 acym__listing__header__title">
                        <?php echo acym_translation('ACYM_OPEN').' / '.acym_translation('ACYM_AVERAGE_COMPARE'); ?>
					</div>
					<div class="cell small-2 acym__listing__header__title">
                        <?php echo acym_translation('ACYM_CLICK').' / '.acym_translation('ACYM_AVERAGE_COMPARE'); ?>
					</div>
				</div>
			</div>

            <?php
            foreach ($data['recent_campaigns'] as $campaign) { ?>
				<div class="grid-x cell align-middle acym__listing__row">
					<div class="cell small-6">
                        <?php echo $campaign->name; ?><br>
                        <?php echo acym_date($campaign->sending_date, acym_getDateTimeFormat()); ?>
					</div>
					<div class="cell small-2 large">
                        <?php echo $campaign->subscribers; ?>
					</div>
					<div class="cell small-2 ">
                        <?php echo $campaign->open; ?>%
						<i class="acymicon-<?php echo ($campaign->open > $openRate) ? 'arrow-up-thin acym__color__green' : 'arrow-down-thin acym__color__red'; ?>"></i>
					</div>
					<div class="cell small-2 ">
                        <?php echo $campaign->click; ?>%
						<i class="acymicon-<?php echo ($campaign->open > $openRate) ? 'arrow-up-thin acym__color__green' : 'arrow-down-thin acym__color__red'; ?>"></i>
					</div>
				</div>
            <?php } ?>
		</div>
    <?php } else { ?>
		<div class="cell grid-x">
			<div class="cell text-center acym__dashboard__empty">
                <?php echo acym_translation('ACYM_NO_RECENT_CAMPAIGNS'); ?>
			</div>
		</div>
    <?php } ?>
</div>
