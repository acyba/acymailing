<div id="acym__scenario__performances">
	<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" data-abide novalidate>
		<div class="acym__content" id="acym__scenario__performances__container">
            <?php include acym_getView('scenarios', 'scenario_top_actions'); ?>
            <?php
            if (empty($data['numberOfTrigger'])) { ?>
				<h1 class="cell acym__listing__empty__title text-center margin-top-1"><?php echo acym_translation('ACYM_SCENARIO_NOT_TRIGGERED_YET'); ?></h1>
            <?php } else { ?>
				<div id="acym__scenario__performances__sankey" class="margin-top-1"></div>
				<input type="hidden" id="acym__scenario__performances__chart-nodes" value="<?php echo acym_escape(json_encode($data['chartNodes'])); ?>">
				<input type="hidden" id="acym__scenario__performances__chart-data" value="<?php echo acym_escape(json_encode($data['chartData'])); ?>">
            <?php } ?>
		</div>
        <?php include acym_getView('scenarios', 'right_panel'); ?>
		<input type="hidden" name="scenarioId" value="<?php echo empty($data['scenario']->id) ? '' : $data['scenario']->id; ?>">
        <?php acym_formOptions(); ?>
	</form>
</div>

