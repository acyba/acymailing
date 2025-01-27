<div class="grid-x">
	<div class="intext_select_automation cell">
		<label class="cell acym_vcenter">
            <?php echo acym_translation('ACYM_ACTION_ON_GROUPS_FROM'); ?>
		</label>
        <?php
        echo acym_select(
            $groups,
            'acym_action[actions][__and__][acy_group_action][remove]',
            'none',
            ['class' => 'acym__select']
        );
        ?>
	</div>
	<div class="intext_select_automation cell">
		<label class="cell shrink margin-left-1 margin-right-1">
            <?php echo acym_translation('ACYM_ACTION_ON_GROUPS_TO'); ?>
		</label>
        <?php
        echo acym_select(
            $groups,
            'acym_action[actions][__and__][acy_group_action][add]',
            'none',
            [
                'class' => 'acym__select',
                'data-toggle-select' => '{"none":"#warning_delete_group"}',
            ]
        );
        ?>
	</div>
	<div id="warning_delete_group" class="cell">
		<label class="cell shrink acym__color__red">
            <?php echo acym_translation('ACYM_ACTION_ON_GROUPS_FROM_WARNING'); ?>
		</label>
	</div>
</div>
