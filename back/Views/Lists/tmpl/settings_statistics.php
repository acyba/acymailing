<?php
// context verification
?>
	<h5 class="cell acym__title acym__title__secondary"><?php echo acym_escapeHtml(acym_translation('ACYM_STATISTICS')); ?></h5>
	<div class="cell grid-x">
        <?php
        acym_displayRoundChart($data['listStats']['deliveryRate'], 'delivery', 'cell large-auto medium-6 small-12', '<label>'.acym_translation('ACYM_DELIVERY_RATE').'</label>');
        acym_displayRoundChart($data['listStats']['openRate'], 'open', 'cell large-auto medium-6 small-12', '<label>'.acym_translation('ACYM_OPEN_RATE').'</label>');
        acym_displayRoundChart($data['listStats']['clickRate'], 'click', 'cell large-auto medium-6 small-12', '<label>'.acym_translation('ACYM_CLICK_RATE').'</label>');
        acym_displayRoundChart($data['listStats']['failRate'], 'fail', 'cell large-auto medium-6 small-12', '<label>'.acym_translation('ACYM_FAIL_RATE').'</label>');
        acym_displayRoundChart($data['listStats']['bounceRate'], '', 'cell large-auto medium-6 small-12', '<label>'.acym_translation('ACYM_BOUNCE_RATE').'</label>');
        ?>
	</div>
<?php if (!empty($data['evol'])) { ?>
	<h4 class="cell text-center margin-top-2 "><?php echo acym_escapeHtml(acym_translation('ACYM_SUBSCRIBERS_EVOLTUION_LAST_YEAR')); ?></h4>
	<div class="cell grid-x" id="acym__list__settings__stats__evol">
		<input type="hidden" id="acym__list__settings__stats-evol__data" value="<?php echo acym_escape(json_encode($data['evol'])); ?>">
		<canvas id="chartjs-evol" height="400"></canvas>
	</div>
<?php }
