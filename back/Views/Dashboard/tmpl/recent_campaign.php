<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
$anonymousStats = !empty($data['anonymousStats']);

// In anonymous mode opens/clicks are counted globally (totals, not unique subscribers): each campaign shows an average number of opens/clicks per email sent (e.g. 3.25×) instead of a percentage of recipients, so the average we compare it against must be expressed in the same "per sent" unit.
if ($anonymousStats) {
    $sentTotal = $data['mail']->sent ?? 0;
    $openRate = empty($sentTotal) ? 0 : ($data['mail']->openCount ?? 0) / $sentTotal;
    $clickRate = empty($sentTotal) ? 0 : ($data['mail']->clickCount ?? 0) / $sentTotal;
    $rateSuffix = '×';
    $rateTooltipKey = 'ACYM_RATE_AVERAGE_COMPARE_ANONYMOUS_TOOLTIP';
} else {
    $openRate = $data['stats']['ACYM_OPEN_RATE']['value'];
    $clickRate = $data['stats']['ACYM_CLICK_RATE']['value'];
    $rateSuffix = '%';
    $rateTooltipKey = 'ACYM_RATE_AVERAGE_COMPARE_TOOLTIP';
}
?>

<div class="acym__content cell">
	<div class="cell acym__title acym__dashboard__title"><?php echo acym_escapeHtml(acym_translation('ACYM_RECENT_CAMPAIGNS')); ?></div>
	<span class="separator"></span>
    <?php if ($data['recent_campaigns']) { ?>
		<div class="grid-x acym__listing cell">
			<div class="grid-x cell acym__listing__header">
				<div class="grid-x medium-auto small-12">
					<div class="cell small-6 acym__listing__header__title">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_CAMPAIGN')); ?>
					</div>
					<div class="cell small-2 acym__listing__header__title">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_TOTAL_SUBSCRIBERS_COLUMN_STAT')); ?>
					</div>
					<div class="cell small-2 acym__listing__header__title">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_OPEN').' / '.acym_translation('ACYM_AVERAGE_COMPARE'));
                        acym_info(['textShownInTooltip' => $rateTooltipKey]); ?>
					</div>
					<div class="cell small-2 acym__listing__header__title">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_CLICK').' / '.acym_translation('ACYM_AVERAGE_COMPARE'));
                        acym_info(['textShownInTooltip' => $rateTooltipKey]); ?>
					</div>
				</div>
			</div>

            <?php
            foreach ($data['recent_campaigns'] as $campaign) { ?>
				<div class="grid-x cell align-middle acym__listing__row">
					<div class="cell small-6">
                        <?php echo acym_escapeHtml($campaign->name); ?><br>
                        <?php echo acym_escapeHtml(acym_date($campaign->sending_date, acym_getDateTimeFormat())); ?>
					</div>
					<div class="cell small-2 large">
                        <?php echo acym_escapeHtml($campaign->subscribers ?? 0); ?>
					</div>
					<div class="cell small-2 ">
                        <?php echo acym_escapeHtml($campaign->open.$rateSuffix ?? ''); ?>
						<i class="acymicon-<?php echo ($campaign->open > $openRate) ? 'arrow-up-thin acym__color__green' : 'arrow-down-thin acym__color__red'; ?>"></i>
					</div>
					<div class="cell small-2 ">
                        <?php echo acym_escapeHtml($campaign->click.$rateSuffix ?? ''); ?>
						<i class="acymicon-<?php echo ($campaign->click > $clickRate) ? 'arrow-up-thin acym__color__green' : 'arrow-down-thin acym__color__red'; ?>"></i>
					</div>
				</div>
            <?php } ?>
		</div>
    <?php } else { ?>
		<div class="cell grid-x">
			<div class="cell text-center acym__dashboard__empty">
                <?php echo acym_escapeHtml(acym_translation('ACYM_NO_RECENT_CAMPAIGNS')); ?>
			</div>
		</div>
    <?php } ?>
</div>
