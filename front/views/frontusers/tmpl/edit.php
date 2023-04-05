<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" data-abide novalidate enctype="multipart/form-data"
    <?php echo !empty($data['menuClass']) ? 'class="'.acym_escape($data['menuClass']).'"' : ''; ?> >
	<div class="grid-x">
		<div id="acym__user__edit" class="cell grid-x acym__content">
            <?php include acym_getView('users', 'edit_actions'); ?>

			<div class="cell grid-x grid-margin-x grid-margin-y margin-top-1">
				<div class="cell grid-x">
                    <?php include acym_getView('users', 'edit_information', true); ?>
				</div>
				<div class="cell grid-x acym_center_baseline">
                    <?php include acym_getView('users', 'edit_stats', true); ?>
				</div>
			</div>
            <?php include acym_getView('users', 'edit_subscription', true); ?>
		</div>

		<input type="hidden" name="lists_already_add" id="acym__user__lists_already_add" value="<?php echo acym_escape(json_encode($data['subscriptionsIds'])); ?>">
		<input type="hidden" name="userId" value="<?php echo empty($data['user-information']->id) ? '' : acym_escape($data['user-information']->id); ?>">
		<input type="hidden" name="acy_source" value="Front-end" />
        <?php acym_formOptions(); ?>
	</div>
</form>
