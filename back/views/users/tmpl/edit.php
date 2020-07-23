<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" data-abide novalidate enctype="multipart/form-data">
	<div class="grid-x">
		<div id="acym__user__edit" class="cell grid-x acym__content ">
            <?php include acym_getView('users', 'edit_actions'); ?>

			<div class="cell grid-x grid-margin-x grid-margin-y">
				<div class="cell grid-x large-5">
                    <?php include acym_getView('users', 'edit_information'); ?>
				</div>
				<div class="cell grid-x large-7 acym_center_baseline">
                    <?php include acym_getView('users', 'edit_stats'); ?>
                    <?php include acym_getView('users', 'edit_history'); ?>
				</div>
			</div>
            <?php include acym_getView('users', 'edit_subscription'); ?>
		</div>

		<input type="hidden" name="lists_already_add" id="acym__user__lists_already_add" value="<?php echo acym_escape(json_encode($data['subscriptionsIds'])); ?>">
		<input type="hidden" name="id" value="<?php echo empty($data['user-information']->id) ? '' : acym_escape($data['user-information']->id); ?>">
		<input type="hidden" name="acy_source" value="Back-end" />
        <?php acym_formOptions(); ?>
	</div>
</form>
