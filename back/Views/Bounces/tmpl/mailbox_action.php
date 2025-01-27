<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" data-abide novalidate>
	<input type="hidden" name="mailbox[id]" value="<?php echo empty($data['mailboxActions']->id) ? '' : intval($data['mailboxActions']->id); ?>">

	<div id="acym__mailbox__edition" class="acym__content acym_area grid-x cell grid-margin-x margin-left-0">
		<div class="cell grid-x text-right grid-margin-x margin-bottom-1 margin-y">
			<div class="cell auto hide-for-small-only hide-for-medium-only"></div>
            <?php echo acym_cancelButton(); ?>
			<button type="button" data-task="applyMailboxAction" class="button button-secondary acy_button_submit cell medium-6 large-shrink">
                <?php echo acym_translation('ACYM_SAVE'); ?>
			</button>
			<button type="button" data-task="saveMailboxAction" class="button margin-right-0 acy_button_submit cell medium-6 large-shrink">
                <?php echo acym_translation('ACYM_SAVE_EXIT'); ?>
			</button>
		</div>

        <?php
        include acym_getView('bounces', 'mailbox_information');
        include acym_getView('bounces', 'mailbox_configuration');
        include acym_getView('bounces', 'mailbox_conditions');
        include acym_getView('bounces', 'mailbox_actions');
        ?>

	</div>
    <?php acym_formOptions(); ?>
</form>
