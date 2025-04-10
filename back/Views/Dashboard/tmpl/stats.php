<?php
foreach ($data['stats'] as $label => $stat) {
    $evolution = $stat['evolution'];
    $evolutionText = ($evolution !== 0 && !is_null($evolution)) ? (($evolution > 0 ? '+' : '').$evolution.' %') : '';
    $colorClass = $evolution > 0 ? 'acym__color__green' : ($evolution < 0 ? 'acym__color__red' : '');

    if ($stat['value'] == 0 && $evolution == 0 && $label === 'ACYM_BOUNCE_RATE') {
        continue;
    }
    ?>
	<div class="acym__content acym__dashboard__stats__container cell text-center margin-bottom-2">
		<div class="grid-x align-middle grid-container">
			<div class="cell shrink acym__dashboard__stats__text__container">
				<div class="acym__title acym__dashboard__stats__title"><?php echo acym_translation($label); ?></div>
				<span class="acym__dashboard__number"><?php echo $stat['value']; ?></span>
				<span class="acym__dashboard__percentage <?php echo $colorClass; ?>"><?php echo $evolutionText; ?></span>
			</div>
			<div class="cell shrink">
                <?php echo acym_tooltip(
                    [
                        'hoveredText' => '<i class="'.$stat['icon'].' acym__dashboard__stats__icon"></i>',
                        'textShownInTooltip' => acym_translation('ACYM_DASHBOARD_STATS_TOOLTIP'),
                    ]
                ); ?>
			</div>
		</div>
	</div>
<?php } ?>
