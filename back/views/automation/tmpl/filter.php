<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" data-abide novalidate>
	<input type="hidden" name="id" value="<?php echo empty($data['id']) ? '' : intval($data['id']); ?>">
	<input type="hidden" id="filters" value="<?php echo acym_escape($data['action']->filters); ?>">
	<input type="hidden" name="conditionId" id="conditionId" value="<?php echo empty($data['condition']->id) ? '' : intval($data['condition']->id); ?>">
	<input type="hidden" name="stepAutomationId" value="<?php echo empty($data['step_automation_id']) ? '' : intval($data['step_automation_id']); ?>">
	<input type="hidden" name="actionId" value="<?php echo empty($data['action']->id) ? '' : intval($data['action']->id); ?>">
	<input type="hidden" id="acym__automation__filters__count__and" value="0">
	<input type="hidden" id="acym__automation__filters__count__or" value="0">

	<div class="acym__content grid-x cell" id="acym__automation__filters">
        <?php
        echo $data['workflowHelper']->display($this->steps, 'filter');
        ?>
		<div id="acym__automation__or__example" style="display: none;">
			<h6 class="cell acym__title acym__title__secondary margin-top-1"><?php echo acym_translation('ACYM_OR'); ?></h6>
			<div class="cell grid-x acym__content acym__automation__group__filter" data-filter-number="0">
				<div class="acym__automation__new__or cell grid-x">
					<div class="cell auto"></div>
					<i class="acymicon-close acym__color__red acym__automation__delete__group__filter shrink cell cursor-pointer"></i>
				</div>
				<div class="cell grid-x">
					<div class="auto cell hide-for-medium-only hide-for-small-only"></div>
					<span class="cell large-shrink acym__automation__or__total__result"></span>
				</div>
				<div class="cell grid-x margin-top-2">
					<button data-filter-type="" type="button" class="button-secondary button medium-shrink acym__automation__add-filter"><?php echo acym_translation(
                            'ACYM_ADD_FILTER'
                        ); ?></button>
				</div>
			</div>
		</div>
		<div style="display: none" id="acym__segment__see-users__example">
            <?php echo acym_modalInclude(
                '<span class="acym__segment__see-users__button cursor-pointer text-underline">'.acym_translation('ACYM_SEE_USERS').'</span>',
                acym_getPartial('modal', 'users'),
                'acym__segments__see-users',
                [
                    'ctrl' => 'segments',
                    'task' => 'usersSummary',
                ],
                '',
                'acym__modal__users__summary__container'
            ); ?>
		</div>
		<div class="cell grid-x acym__automation__one__filter" id="acym__automation__and__example" style="display: none;">
			<div class="acym__automation__and cell grid-x margin-top-2">
				<h6 class="cell medium-shrink small-11 acym__title acym__title__secondary"><?php echo acym_translation('ACYM_AND'); ?></h6>
				<div class="cell medium-4 hide-for-small-only"></div>
				<i class="cell medium-shrink small-1 cursor-pointer acymicon-close acym__color__red acym__automation__delete__one__filter"></i>
			</div>
			<div class="medium-5 cell acym__automation__and__example__classic__select" style="display: none;">
                <?php echo acym_select($data['classic_name'], 'filters_name', null, 'class="acym__automation__select__classic__filter" data-class="acym__select"'); ?>
			</div>
			<div class="medium-5 cell acym__automation__and__example__user__select" style="display: none;">
                <?php echo acym_select($data['classic_name'], 'filters_name', null, 'class="acym__automation__select__user__filter" data-class="acym__select"'); ?>
			</div>
		</div>

		<h6 class="acym__title acym__title__secondary cell"><?php echo acym_translation('ACYM_SELECT_TARGETS_ACTIONS'); ?></h6>
		<div class="cell grid-x grid-margin-x margin-bottom-2" <?php echo $data['type_trigger'] == 'classic' ? 'style="display: none;"' : ''; ?>>
			<div class="cell auto"></div>
			<input type="hidden" name="type_filter" id="acym__automation__type-filter__input" value="<?php echo acym_escape($data['type_filter']); ?>">
			<p data-filter="user" class="acym__automation__choose__filter medium-shrink cell <?php echo $data['type_filter'] == 'classic' ? '' : 'selected-filter'; ?>">
                <?php echo acym_translation('ACYM_EXECUTE_ACTIONS_ON_ONE_SUBSCRIBER'); ?>
			</p>
			<p data-filter="classic" class="acym__automation__choose__filter medium-shrink cell <?php echo $data['type_filter'] == 'classic' ? 'selected-filter' : ''; ?>">
                <?php echo acym_translation('ACYM_EXECUTE_ACTIONS_ON_ALL_SUBSCRIBERS'); ?>
			</p>
			<div class="cell auto"></div>
		</div>

		<div class="cell grid-x acym__automation__filter__container"
			 id="acym__automation__filters__type__classic" <?php echo $data['type_filter'] == 'classic' ? '' : 'style="display:none;"'; ?>>
			<input type="hidden" value="<?php echo acym_escape($data['classic_option']); ?>" id="acym__automation__filter__classic__options">
			<div class="cell grid-x acym__content acym__automation__group__filter" data-filter-number="0">
				<div class="auto cell hide-for-medium-only hide-for-small-only"></div>
				<span class="cell large-shrink acym__automation__or__total__result"></span>
				<div class="cell grid-x acym__automation__one__filter acym__automation__one__filter__classic">
					<div class="medium-5 cell">
                        <?php
                        echo acym_select($data['classic_name'], 'filters_name', null, 'class="acym__select acym__automation__select__classic__filter"');
                        ?>
					</div>
				</div>
				<div class="cell grid-x margin-top-2">
					<button data-filter-type="classic"
							data-block="0"
							type="button"
							class="button-secondary button medium-shrink acym__automation__add-filter"><?php echo acym_translation('ACYM_ADD_FILTER'); ?></button>
				</div>
			</div>
			<button data-filter-type="classic" type="button" class="acym__automation__filters__or margin-top-1 button button-secondary"><?php echo acym_translation(
                    'ACYM_OR'
                ); ?></button>
		</div>

		<div class="cell grid-x acym__automation__filter__container"
			 id="acym__automation__filters__type__user" <?php echo $data['type_filter'] == 'classic' ? 'style="display:none;"' : ''; ?>>
		</div>

		<div class="cell grid-x grid-margin-x margin-top-2">
            <?php if (empty($data['id'])) { ?>
				<div class="auto cell"></div>
				<button type="button" class="button button-secondary acy_button_submit medium-shrink cell" data-task="listing"><?php echo acym_translation(
                        'ACYM_CANCEL'
                    ); ?></button>
				<button type="button" class="button acy_button_submit medium-shrink cell" data-task="edit" data-step="setFilterMassAction"><?php echo acym_translation(
                        'ACYM_SAVE_CONTINUE'
                    ); ?></button>
            <?php } else { ?>
				<div class="cell medium-shrink medium-margin-bottom-0 margin-bottom-1 text-left">
                    <?php echo acym_backToListing("automation"); ?>
				</div>
				<div class="auto cell"></div>
				<button type="button"
						class="button button-secondary acy_button_submit medium-margin-bottom-0 margin-bottom-1 medium-shrink cell"
						data-task="edit"
						data-step="saveExitFilters"><?php echo acym_translation(
                        'ACYM_SAVE_EXIT'
                    ); ?></button>
				<button type="button" class="button acy_button_submit medium-shrink cell" data-task="edit" data-step="saveFilters"><?php echo acym_translation(
                        'ACYM_SAVE_CONTINUE'
                    ); ?></button>
            <?php } ?>
		</div>
	</div>
    <?php acym_formOptions(); ?>
</form>
