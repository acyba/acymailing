<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<div id="acym__scenario__edit__content">
	<div id="acym__scenario__edit__content__new">
		<p><?php echo acym_escapeHtml(acym_translation('ACYM_START_NEW_SCENARIO')); ?></p>
		<button id="acym__scenario__edit__content__new__choose__trigger" type="button" class="button"><?php echo acym_escapeHtml(
                acym_translation('ACYM_CHOOSE_TRIGGER')
            ); ?></button>
        <?php acym_modal(
            acym_translation('ACYM_USE_TEMPLATE'),
            $data['modalTemplate'],
            'acym__scenario__choose_template',
            [],
            [
                'class' => 'button',
                'data-reload' => 'true',
                'data-ajax' => 'false',
            ]
        ); ?>
	</div>
	<div id="acym__scenario__edit__content__flow" style="display: none"></div>
    <?php include acym_getView('scenarios', 'right_panel'); ?>
	<div>
		<div id="acym_scenario_triggers">
			<script>
                var acymailingScenarioTriggers = <?php echo json_encode($data['triggers']); ?>;
                var acymailingScenarioConditions = <?php echo json_encode($data['conditions']); ?>;
                var acymailingScenarioActions = <?php echo json_encode($data['actions']); ?>;
			</script>
            <?php
            acym_select($data['triggers'], 'acym_scenario_triggers_input', null, ['class' => 'acym__select'], 'key', 'name', null, false, true);
            foreach ($data['triggers'] as $trigger) {
                echo '<div style="display: none;" data-acym-trigger-option="'.acym_escape($trigger->key).'">'.$trigger->option.'</div>';
            }
            ?>
		</div>
		<div id="acym_scenario_delay">
			<input type="number" name="acym_scenario_delay_number" value="0">
            <?php
            acym_select(
                [
                    '60' => acym_translation('ACYM_MINUTES'),
                    '3600' => acym_translation('ACYM_HOUR'),
                    '86400' => acym_translation('ACYM_DAY'),
                ],
                'acym_scenario_delay_unit',
                60,
                ['class' => 'acym__select'],
                'value',
                'text',
                null,
                false,
                true
            );
            ?>
		</div>
		<div id="acym_scenario_condition">
            <?php
            acym_select($data['conditions'], 'acym_scenario_conditions_input', null, ['class' => 'acym__select'], 'key', 'name', null, false, true);
            foreach ($data['conditions'] as $key => $condition) {
                echo '<div style="display: none;" data-acym-condition-option="'.acym_escape($condition->key).'">'.$condition->option.'</div>';
            }
            ?>
		</div>
		<div id="acym_scenario_action">
            <?php
            acym_select($data['actions'], 'acym_scenario_actions_input', null, ['class' => 'acym__select'], 'key', 'name', null, false, true);
            foreach ($data['actions'] as $key => $action) {
                echo '<div style="display: none;" data-acym-action-option="'.acym_escape($action->key).'">'.$action->option.'</div>';
            }
            ?>
		</div>
		<div id="acym_scenario_settings">
			<div class="acym_scenario_settings__field">
				<p><?php echo acym_escapeHtml(acym_translation('ACYM_NAME')); ?></p>
				<input type="text" name="scenario[name]" value="<?php echo empty($data['scenario']->name) ? '' : acym_escape($data['scenario']->name); ?>">
			</div>
			<div class="acym_scenario_settings__field">
				<p><?php echo acym_escapeHtml(acym_translation('ACYM_ACTIVE')); ?></p>
                <?php acym_switch([
                    'name' => 'scenario[active]',
                    'value' => $data['scenario']->active,
                ]); ?>
			</div>
			<div class="acym_scenario_settings__field">
				<p><?php echo acym_escapeHtml(acym_translation('ACYM_TRIGGER_ONCE'));
                    acym_info(['textShownInTooltip' => 'ACYM_TRIGGER_ONCE_DESC']); ?></p>
                <?php acym_switch([
                    'name' => 'scenario[trigger_once]',
                    'value' => $data['scenario']->trigger_once,
                ]); ?>
			</div>
		</div>
	</div>
	<input type="hidden" id="acym__scenario__edit__value" name="scenario[flow]">
	<input type="hidden" id="acym__scenario__saved__flow" value="<?php echo empty($data['flow']) ? '' : acym_escape(json_encode($data['flow'])); ?>">
	<input type="hidden"
	       id="acym__scenario__preopen__stepid"
	       value="<?php echo empty($data['returnFromMailCreationStepId']) ? '' : acym_escape($data['returnFromMailCreationStepId']); ?>">
</div>
