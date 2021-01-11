<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acySegments" data-abide novalidate>
	<input type="hidden" value="<?php echo acym_escape($data['filter_option']); ?>" id="acym__segments__edit__info__options">
	<input type="hidden" value="<?php echo empty($data['segment']->id) ? '' : $data['segment']->id; ?>" name="id">
	<input type="hidden" id="acym__segments__filters" value="<?php echo acym_escape(empty($data['segment']->filters) ? '' : $data['segment']->filters); ?>">
	<input type="hidden" id="acym__segments__filters__count__and" value="0">
	<div class="cell grid-x grid-margin-x acym__segments__edit__info acym__content margin-bottom-1 padding-bottom-0 margin-y margin-left-0">
		<div class="cell large-4 medium-6 grid-x grid-margin-x acym_vcenter">
			<label for="acym__segments__edit__info__name" class="cell shrink"><?php echo acym_translation('ACYM_SEGMENT_NAME'); ?></label>
			<input required
				   type="text"
				   name="segment[name]"
				   class="cell auto"
				   id="acym__segments__edit__info__name"
				   value="<?php echo empty($data['segment']->name) ? '' : $data['segment']->name; ?>">
		</div>
		<div class="cell large-3 medium-6 grid-x grid-margin-x acym_vcenter">
            <?php echo acym_switch('segment[active]', $data['segment']->active, acym_translation('ACYM_ACTIVE'), [], 'shrink'); ?>
		</div>
		<div class="cell large-5 align-right grid-x grid-margin-x acym__segments__edit__info__actions margin-y margin-bottom-0">
            <?php echo acym_cancelButton(); ?>
			<button acym-data-before="acym_helperSegments.beforeSave()" class="cell large-shrink medium-6 button acy_button_submit button-secondary" data-task="apply">
                <?php echo acym_translation('ACYM_SAVE'); ?>
			</button>
			<button acym-data-before="acym_helperSegments.beforeSave()" class="cell large-shrink medium-6 button acy_button_submit" data-task="save">
                <?php echo acym_translation('ACYM_SAVE_EXIT'); ?>
			</button>
		</div>
	</div>
	<div class="cell grid-x acym__segments__edit__filters acym__content">
        <?php include acym_getView('segments', 'edit_filters'); ?>
	</div>
    <?php acym_formOptions(); ?>
</form>
