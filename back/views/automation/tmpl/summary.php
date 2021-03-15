<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" data-abide novalidate>
	<input type="hidden" name="id" value="<?php echo empty($data['id']) ? '' : intval($data['id']); ?>">
	<div class="cell grid-x">
		<div class="cell auto"></div>
		<div class="acym__content grid-x cell xxlarge-7 xlarge-9" id="acym__automation__summary">
            <?php
            echo $data['workflowHelper']->display($this->steps, 'summary');
            if (!empty($data['id'])) {
                ?>
				<div class="acym__automation__summary__info cell grid-x acym__content margin-top-2">
					<h6 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_INFORMATION'); ?></h6>
                    <?php if (!empty($data['automation']->admin)) {
                        $data['automation']->name = acym_translation($data['automation']->name);
                        $data['automation']->description = acym_translation($data['automation']->description);
                    } ?>
					<div class="cell acym__automation__summary__information__one"><span class="acym__automation__summary__information__one__title"><?php echo acym_translation(
                                'ACYM_NAME_SUMMARY'
                            ); ?></span> : <?php echo acym_escape($data['automation']->name); ?></div>
					<div class="cell acym__automation__summary__information__one"><span class="acym__automation__summary__information__one__title"><?php echo acym_translation(
                                'ACYM_DESCRIPTION'
                            ); ?></span> : <?php echo acym_escape($data['automation']->description); ?></div>
				</div>
				<div class="acym__automation__summary__filters cell grid-x margin-top-2 acym__content">
					<h6 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_TRIGGERS'); ?></h6>
					<div class="cell acym__automation__summary__information__one"><span class="acym__automation__summary__information__one__title"><?php echo acym_translation(
                                'ACYM_AUTOMATION_TRIGGER'
                            ); ?></span></div>
					<br />
					<div class="cell acym__automation__summary__information__one">
                        <?php
                        foreach ($data['step']->triggers as $name => $oneTrigger) {
                            if (!is_string($oneTrigger)) {
                                $data['step']->triggers[$name] = acym_translation('ACYM_UNKNOWN');
                            }
                        }

                        echo implode(
                            '<br /><span class="acym__automation__summary__information__one__title">'.acym_translation('ACYM_OR').'</span><br />',
                            $data['step']->triggers
                        );
                        ?>
					</div>
				</div>
				<div class="acym__automation__summary__actions cell grid-x margin-top-2 acym__content">
					<h6 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_CONDITIONS'); ?></h6>
					<div class="cell acym__automation__summary__information__one grid-x">
                        <?php
                        if (!empty($data['condition']->conditions)) {
                            $orNum = 0;
                            $andNum = 0;
                            $typeTrigger = $data['condition']->conditions['type_condition'];
                            unset($data['condition']->conditions['type_condition']);
                            if (empty($data['condition']->conditions)) {
                                echo '<span class="acym__automation__summary__information__one__title">'.acym_translation(
                                        'ACYM_YOU_DID_NOT_SET_CONDITION'
                                    ).'</span></div><div class="acym__automation__summary__information__one">';
                            } else {
                                echo '<span class="acym__automation__summary__information__one__title">'.acym_translationSprintf(
                                        'ACYM_CONDITIONS_APPLY_TO',
                                        acym_translation($typeTrigger == 'classic' ? '' : 'ACYM_ONE_ACYMAILING_SUBSCRIBER_CONDITION')
                                    ).'</span></div><div class="acym__automation__summary__information__one">';
                                foreach ($data['condition']->conditions as $or => $orValues) {
                                    if ($or === 'type_condition') continue;
                                    $andNum = 0;
                                    if ($orNum > 0) echo '<span class="acym__automation__summary__information__one__title">'.acym_translation('ACYM_OR').'</span><br />';
                                    foreach ($orValues as $and => $andValue) {
                                        if ($andNum > 0) echo '<span class="acym__automation__summary__information__one__title">'.acym_translation('ACYM_AND').'</span><br />';
                                        echo $andValue.'<br />';
                                        $andNum++;
                                    }
                                    $orNum++;
                                }
                            }
                        } else {
                            echo '<strong class="acym__color__red cell text-center">'.acym_translation('ACYM_SELECT_CONDITIONS').'</strong>';
                        }
                        ?>
					</div>
				</div>
            <?php } ?>
			<div class="acym__automation__summary__actions cell grid-x margin-top-2 margin-bottom-2 acym__content">
				<h6 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_ACTIONS'); ?></h6>
				<div class="cell acym__automation__summary__information__one grid-x">
                    <?php
                    if (!empty($data['action']->actions)) {
                        echo '<span class="acym__automation__summary__information__one__title">'.acym_translationSprintf(
                                'ACYM_ACTIONS_SUBSCRIBER_WILL',
                                acym_strtolower(acym_translation(empty($data['id']) ? 'ACYM_MASS_ACTION' : 'ACYM_AUTOMATION'))
                            ).'</span></div><div class="acym__automation__summary__information__one">';
                        $andNum = 0;
                        foreach ($data['action']->actions as $and => $andValue) {
                            if ($andNum > 0) echo '<span class="acym__automation__summary__information__one__title">'.acym_translation('ACYM_AND').'</span><br />';
                            echo $andValue.'<br />';
                            $andNum++;
                        }
                    } else {
                        echo '<strong class="acym__color__red cell text-center">'.acym_translation('ACYM_SELECT_ACTIONS').'</strong>';
                    }
                    ?>
				</div>
			</div>
			<div class="acym__automation__summary__actions cell grid-x margin-top-1  margin-bottom-2 acym__content">
				<h6 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_ACTIONS_TARGETS'); ?></h6>
				<div class="cell acym__automation__summary__information__one grid-x">
                    <?php
                    if (!empty($data['action']->filters)) {
                        $orNum = 0;
                        $andNum = 0;
                        echo '<span class="acym__automation__summary__information__one__title">'.acym_translationSprintf(
                                'ACYM_FILTERS_APPLY_TO',
                                acym_strtolower(acym_translation(empty($data['id']) ? 'ACYM_MASS_ACTION' : 'ACYM_AUTOMATION')),
                                acym_translation($data['action']->filters['type_filter'] == 'classic' ? 'ACYM_ALL_ACYMAILING_USERS' : 'ACYM_ONE_ACYMAILING_USER')
                            ).'</span></div><div class="acym__automation__summary__information__one">';
                        foreach ($data['action']->filters as $or => $orValues) {
                            if ($or === 'type_filter') continue;
                            $andNum = 0;
                            if ($orNum > 0) echo '<span class="acym__automation__summary__information__one__title">'.acym_translation('ACYM_OR').'</span><br />';
                            foreach ($orValues as $and => $andValue) {
                                if ($andNum > 0) echo '<span class="acym__automation__summary__information__one__title">'.acym_translation('ACYM_AND').'</span><br />';
                                echo $andValue.'<br />';
                                $andNum++;
                            }
                            $orNum++;
                        }
                    } else {
                        echo '<strong class="acym__color__red cell text-center">'.acym_translation('ACYM_SELECT_FILTERS').'</strong>';
                    }
                    ?>
				</div>
			</div>
			<div class="cell grid-x grid-margin-x">
				<div class="auto cell"></div>
                <?php if (empty($data['id'])) { ?>
					<button type="button" data-task="listing" class="cell shrink button-secondary button acy_button_submit"><?php echo acym_translation('ACYM_CANCEL'); ?></button>
					<button type="button" data-task="processMassAction" class="cell shrink button acy_button_submit"><?php echo acym_translation(
                            'ACYM_PROCESS_MASS_ACTION'
                        ); ?></button>
                <?php } else { ?>
					<button type="button" data-task="listing" class="cell shrink button button-secondary acy_button_submit"><?php echo acym_translation(
                            'ACYM_SAVE_EXIT'
                        ); ?></button>
					<button type="button" data-task="activeAutomation" class="cell shrink button acy_button_submit"><?php echo acym_translation(
                            'ACYM_ACTIVE_AUTOMATION'
                        ); ?></button>
                <?php } ?>
			</div>
		</div>
		<div class="cell auto"></div>
	</div>
    <?php acym_formOptions(); ?>
</form>
