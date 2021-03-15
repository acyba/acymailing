<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" novalidate data-abide>
	<input type="hidden" id="acym__form__structure" value="<?php echo acym_escape(json_encode($data['form'])); ?>">
	<div id="acym__forms" class="grid-x">
		<div class="cell grid-x align-right grid-margin-x margin-y margin-left-0 margin-bottom-1 padding-bottom-0 acym__content">
            <?php echo acym_cancelButton('ACYM_CANCEL', '', 'button cell medium-6 large-shrink'); ?>
			<button class="cell medium-6 large-shrink button button-secondary" type="button" @click="save(false)" id="acy__form__save">
                <?php echo acym_translation('ACYM_SAVE'); ?>
			</button>
			<button class="cell medium-6 large-shrink button" type="button" @click="save(true)" id="acy__form__save_exit"><?php echo acym_translation('ACYM_SAVE_EXIT'); ?></button>
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
