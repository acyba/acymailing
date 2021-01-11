<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" data-abide novalidate>
	<input type="hidden" name="bounce[id]" value="<?php echo empty($data['rule']) || empty($data['rule']->id) ? '' : intval($data['rule']->id); ?>">
	<input type="hidden" name="bounce[ordering]" value="<?php echo empty($data['rule']) || empty($data['rule']->ordering) ? '' : intval($data['rule']->ordering); ?>">

	<div id="acym__bounces__listing" class="acym__content acym__bounce_rule__edit acym_area grid-x cell grid-margin-x margin-left-0">
		<div class="cell grid-x text-right grid-margin-x margin-bottom-1 margin-y">
			<div class="cell auto hide-for-small-only hide-for-medium-only"></div>
            <?php echo acym_cancelButton(); ?>
			<button type="button" data-task="apply" class="button button-secondary acy_button_submit cell medium-6 large-shrink">
                <?php echo acym_translation('ACYM_SAVE'); ?>
			</button>
			<button type="button" data-task="save" class="button margin-right-0 acy_button_submit cell medium-6 large-shrink">
                <?php echo acym_translation('ACYM_SAVE_EXIT'); ?>
			</button>
		</div>

        <?php
        include acym_getView('bounces', 'edit_info');
        include acym_getView('bounces', 'edit_summary');
        include acym_getView('bounces', 'edit_details');
        ?>

	</div>
    <?php acym_formOptions(); ?>
</form>
