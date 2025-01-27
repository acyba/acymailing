<button class="shrink grid-x cell acy_button_submit button" type="button" data-task="createMail" data-and="__and__">
    <?php echo acym_translation('ACYM_CREATE_MAIL'); ?>
</button>
<input type="hidden" name="acym_action[actions][__and__][acy_add_queue][mail_id]">
<div class="shrink acym__automation__action__mail__name"></div>
<div class="shrink margin-left-1 margin-right-1">
    <?php echo acym_strtolower(acym_translation('ACYM_OR')); ?>
</div>
<button
		type="button"
		data-modal-name="acym__template__choose__modal__and__"
		data-open="acym__template__choose__modal"
		aria-controls="acym__template__choose__modal"
		tabindex="0"
		aria-haspopup="true"
		class="cell medium-shrink button-secondary auto button ">
    <?php echo acym_translation('ACYM_CHOOSE_EXISTING'); ?>
</button>
<?php echo acym_info('ACYM_CHOOSE_EXISTING_DESC', '', 'margin-left-0'); ?>
<div class="medium-4 grid-x cell">
    <?php echo acym_dateField('acym_action[actions][__and__][acy_add_queue][time]', '[time]', '', '', '+'); ?>
</div>

