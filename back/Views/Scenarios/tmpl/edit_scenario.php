<div id="acym__scenario__edit">
	<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" data-abide novalidate>
		<div class="acym__content" id="acym__scenario__edit__container" data-acym-step-ids="<?php echo acym_escape(json_encode($data['allScenarioStepIds'])); ?>">
            <?php
            include acym_getView('scenarios', 'scenario_top_actions');
            include acym_getView('scenarios', 'edit_scenario_flow');
            ?>
			<div class="cell grid-x">
				<div class="auto cell"></div>
				<div class="cell shrink grid-x">
					<button type="button"
							class="margin-right-1 shrink button button-secondary acy_button_submit"
							data-task="saveExit"
							acym-data-before="(() => acym_helperScenario.areGeneralInformationSet())()"
					>
                        <?php echo acym_translation('ACYM_SAVE_EXIT'); ?>
					</button>
					<button
							type="button"
							class="shrink button acy_button_submit" data-task="save"
							acym-data-before="(() => acym_helperScenario.areGeneralInformationSet())()"
					>
                        <?php echo acym_translation('ACYM_SAVE_CONTINUE'); ?>
					</button>
				</div>
			</div>
		</div>
		<input type="hidden" name="scenarioId" value="<?php echo empty($data['scenario']->id) ? '' : $data['scenario']->id; ?>">
        <?php acym_formOptions(); ?>
	</form>
</div>
