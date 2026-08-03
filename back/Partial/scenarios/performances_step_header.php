<?php
// context verification
?>
<div id="acym__scenario__performance__step__header">
	<div id="acym__scenario__performance__step__header__info">
		<p id="acym__scenario__performance__step__header__info__title">
            <?php echo acym_escapeHtml($data['title']); ?>
		</p>
		<p id="acym__scenario__performance__step__header__info__number">
            <?php echo empty($data['numberOfTrigger']) ? '-' : acym_escapeHtml($data['numberOfTrigger']); ?>
		</p>
	</div>
	<i class="acymicon-user"></i>
</div>
