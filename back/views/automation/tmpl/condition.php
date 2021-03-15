<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" data-abide novalidate>
	<input type="hidden" name="id" value="<?php echo empty($data['id']) ? '' : intval($data['id']); ?>">
	<input type="hidden" id="conditions" value="<?php echo acym_escape($data['condition']->conditions); ?>">
	<input type="hidden" name="stepAutomationId" value="<?php echo empty($data['step_automation_id']) ? '' : intval($data['step_automation_id']); ?>">
	<input type="hidden" name="conditionId" value="<?php echo empty($data['condition']->id) ? '' : intval($data['condition']->id); ?>">
	<input type="hidden" id="acym__automation__conditions__count__and" value="0">
	<input type="hidden" id="acym__automation__conditions__count__or" value="0">

	<div class="acym__content grid-x cell" id="acym__automation__conditions">
        <?php
        if ('[]' == $data['condition']->conditions) {
            $data['workflowHelper']->disabledAfter = 'condition';
        }
        echo $data['workflowHelper']->display($this->steps, 'condition');
        ?>
		<div id="acym__automation__or__example" style="display: none;">
			<h6 class="cell acym__title acym__title__secondary margin-top-1"><?php echo acym_translation('ACYM_OR'); ?></h6>
			<div class="cell grid-x acym__content acym__automation__group__condition" data-condition-number="0">
				<div class="acym__automation__new__or cell grid-x">
					<div class="cell auto"></div>
					<i class="acymicon-close acym__color__red acym__automation__delete__group__condition shrink cell cursor-pointer"></i>
				</div>
				<div class="cell grid-x margin-top-2">
					<button data-condition-type="" type="button" class="button-secondary button medium-shrink acym__automation__add-condition">
                        <?php echo acym_translation('ACYM_ADD_CONDITION'); ?>
					</button>
				</div>
			</div>
		</div>
		<div class="cell grid-x acym__automation__one__condition" id="acym__automation__and__example" style="display: none;">
			<div class="acym__automation__and cell grid-x margin-top-2">
				<h6 class="cell medium-shrink small-11 acym__title acym__title__secondary"><?php echo acym_translation('ACYM_AND'); ?></h6>
				<div class="cell medium-4 hide-for-small-only"></div>
				<i class="cell medium-shrink small-1 cursor-pointer acymicon-close acym__color__red acym__automation__delete__one__condition"></i>
			</div>
			<div class="medium-5 cell acym__automation__and__example__classic__select" style="display: none;">
                <?php echo acym_select($data['classic_name'], 'conditions_name', null, 'class="acym__automation__select__classic__condition" data-class="acym__select"'); ?>
			</div>
			<div class="medium-5 cell acym__automation__and__example__user__select" style="display: none;">
                <?php echo acym_select($data['user_name'], 'conditions_name', null, 'class="acym__automation__select__user__condition" data-class="acym__select"'); ?>
			</div>
		</div>

		<h6 class="acym__title acym__title__secondary cell">
			<?php echo acym_translation('ACYM_SELECT_YOUR_CONDITIONS').acym_info('ACYM_CONDITIONS_DESC'); ?>
		</h6>
		<div class="cell grid-x grid-margin-x margin-bottom-2" <?php echo $data['type_trigger'] == 'classic' ? 'style="display: none;"' : ''; ?>>
			<div class="cell auto"></div>
			<input type="hidden" name="type_condition" id="acym__automation__type-condition__input" value="<?php echo acym_escape($data['type_condition']); ?>">
			<p data-condition="user" class="acym__automation__choose__condition medium-shrink cell <?php echo $data['type_condition'] == 'classic' ? '' : 'selected-condition'; ?>">
                <?php echo acym_translation('ACYM_EXECUTE_CONDITIONS_ON_ONE_SUBSCRIBER'); ?>
			</p>
			<p data-condition="classic"
			   class="acym__automation__choose__condition medium-shrink cell <?php echo $data['type_condition'] == 'classic' ? 'selected-condition' : ''; ?>">
                <?php echo acym_translation('ACYM_EXECUTE_CONDITIONS_ON_ALL_SUBSCRIBERS'); ?>
			</p>
			<div class="cell auto"></div>
		</div>

		<div class="cell grid-x acym__automation__condition__container"
			 id="acym__automation__conditions__type__classic" <?php echo $data['type_condition'] == 'classic' ? '' : 'style="display:none;"'; ?>>
			<input type="hidden" value="<?php echo acym_escape($data['classic_option']); ?>" id="acym__automation__condition__classic__options">
			<div class="cell grid-x acym__content acym__automation__group__condition" data-condition-number="0">
				<div class="cell grid-x acym__automation__one__condition acym__automation__one__condition__classic">
					<div class="medium-5 cell">
                        <?php echo acym_select($data['classic_name'], 'conditions_name', null, 'class="acym__select acym__automation__select__classic__condition"'); ?>
					</div>
				</div>
				<div class="cell grid-x margin-top-2">
					<button data-condition-type="classic"
							data-block="0"
							type="button"
							class="button-secondary button medium-shrink acym__automation__add-condition"><?php echo acym_translation('ACYM_ADD_CONDITION'); ?></button>
				</div>
			</div>
			<button data-condition-type="classic" type="button" class="acym__automation__conditions__or margin-top-1 button button-secondary"><?php echo acym_translation(
                    'ACYM_OR'
                ); ?></button>
		</div>

		<div class="cell grid-x acym__automation__condition__container"
			 id="acym__automation__conditions__type__user" <?php echo $data['type_condition'] == 'classic' ? 'style="display:none;"' : ''; ?>>
			<input type="hidden" value="<?php echo acym_escape($data['user_option']); ?>" id="acym__automation__condition__user__options">
			<div class="cell grid-x acym__content acym__automation__group__condition" data-condition-number="0">
				<div class="cell grid-x acym__automation__one__condition acym__automation__one__condition__user">
					<div class="medium-5 cell">
                        <?php echo acym_select($data['user_name'], 'conditions_name', null, 'class="acym__select acym__automation__select__user__condition"'); ?>
					</div>
				</div>
				<div class="cell grid-x margin-top-2">
					<button data-condition-type="user"
							data-block="0"
							type="button"
							class="button-secondary button medium-shrink acym__automation__add-condition"><?php echo acym_translation('ACYM_ADD_CONDITION'); ?></button>
				</div>
			</div>
			<button data-condition-type="user" type="button" class="acym__automation__conditions__or margin-top-1 button button-secondary"><?php echo acym_translation(
                    'ACYM_OR'
                ); ?></button>
		</div>

		<div class="cell grid-x grid-margin-x margin-top-2">
            <?php if (empty($data['id'])) { ?>
				<div class="cell medium-auto grid-x grid-margin-x text-right">
					<div class="cell auto"></div>
					<button type="button" class="button button-secondary acy_button_submit medium-shrink cell" data-task="listing">
                        <?php echo acym_translation('ACYM_CANCEL'); ?>
					</button>
					<button type="button" class="button acy_button_submit medium-shrink cell" data-task="edit" data-step="setConditionMassAction">
                        <?php echo acym_translation('ACYM_SET_FILTERS'); ?>
					</button>
				</div>
            <?php } else { ?>
				<div class="cell medium-shrink medium-margin-bottom-0 margin-bottom-1 text-left">
                    <?php echo acym_backToListing('automation'); ?>
				</div>
				<div class="cell medium-auto grid-x grid-margin-x text-right">
					<div class="cell auto"></div>
					<button type="button"
							class="button button-secondary acy_button_submit medium-shrink medium-margin-bottom-0 margin-bottom-1 cell"
							data-task="edit"
							data-step="saveExitConditions"><?php echo acym_translation('ACYM_SAVE_EXIT'); ?></button>
					<button type="button" class="button acy_button_submit medium-shrink cell" data-task="edit" data-step="saveConditions">
                        <?php echo acym_translation('ACYM_SAVE_CONTINUE'); ?>
					</button>
				</div>
            <?php } ?>
		</div>
	</div>
    <?php acym_formOptions(); ?>
</form>
