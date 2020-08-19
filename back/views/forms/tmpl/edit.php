<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" novalidate data-abide>
	<input type="hidden" id="acym__form__structure" value="<?php echo acym_escape(json_encode($data['form'])); ?>">
	<div id="acym__forms" class="grid-x">
		<div class="cell">
			<div class="grid-x align-right grid-margin-x">
				<div class="cell grid-x acym__content align-right margin-bottom-1">
                    <?php echo acym_cancelButton('ACYM_CANCEL', '', 'margin-bottom-0 button cell medium-6 large-shrink'); ?>
					<button class="cell shrink button margin-bottom-0 margin-left-1 button-secondary" type="button" @click="save(false)"><?php echo acym_translation('ACYM_SAVE'); ?></button>
					<button class="cell shrink button margin-bottom-0 margin-left-1" type="button" @click="save(true)"><?php echo acym_translation('ACYM_SAVE_EXIT'); ?></button>
				</div>
			</div>
		</div>
		<div class="cell">
			<div class="grid-x grid-margin-x">
				<div class="cell large-8 medium-7 grid-x" id="acym__forms__left">
                    <?php include acym_getView('forms', 'edit_information'); ?>
                    <?php include acym_getView('forms', 'edit_preview'); ?>
				</div>
				<div class="cell large-4 medium-5 grid-x" id="acym__forms__right">
                    <?php include acym_getView('forms', 'edit_menu'); ?>
				</div>
			</div>
		</div>
	</div>
</form>
