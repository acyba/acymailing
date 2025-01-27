<div id="acym__scenario__performance__user">
    <?php foreach ($data['historyLines'] as $historyLine) { ?>
		<div class="acym__scenario__performance__user__block">
			<div class="acym__scenario__performance__user__block__title">
				<i class="acymicon-email"></i>
				<p><?php echo $historyLine->type; ?></p>
			</div>
			<p class="acym__scenario__performance__user__block__info">
                <?php echo acym_translationSprintf('ACYM_EXECUTED_ON_X', acym_date($historyLine->date, acym_translation('ACYM_DATE_FORMAT_LC2'))); ?>
			</p>
		</div>
    <?php } ?>
    <?php if (!empty($data['upcomingStep'])) { ?>
		<div class="acym__scenario__performance__user__block acym__scenario__performance__user__block__scheduled">
			<div class="acym__scenario__performance__user__block__title">
				<i class="acymicon-email"></i>
				<p><?php echo $data['upcomingStep']->type; ?></p>
				<div class="acym__scenario__performance__user__block__title__grow"></div>
				<i class="acymicon-hourglass-2"></i>
			</div>
			<p class="acym__scenario__performance__user__block__info">
                <?php echo acym_translationSprintf('ACYM_EXECUTED_ON_X', acym_date($data['upcomingStep']->execution_date, acym_translation('ACYM_DATE_FORMAT_LC2'))); ?>
			</p>
		</div>
    <?php } ?>
	<div id="acym__scenario__performance__user__actions">
		<div id="acym__scenario__performance__user__back">
			<i class="acymicon-chevron-left"></i>
			<p><?php echo acym_translation('ACYM_RETURN_USER_LISTING'); ?></p>
		</div>
	</div>
</div>
