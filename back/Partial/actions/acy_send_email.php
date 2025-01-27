<div>
	<div id="acym__action__send__email__saved">
		<input type="hidden" name="acym_action[actions][__and__][acy_send_email][mail_id]" id="acym__action__send__email__saved__id">
		<p id="acym__action__send__email__saved__name"></p>
		<i class="acymicon-close acym__color__red cursor-pointer" id="acym__action__send__email__saved__delete"></i>
		<button class="button" id="acym__action__send__email__saved__edit" type="button">
            <?php echo acym_translation('ACYM_EDIT_MAIL'); ?>
		</button>
	</div>
	<button type="button" class="button button-secondary acy_button_submit" data-task="createMail">
        <?php echo acym_translation('ACYM_CREATE_MAIL'); ?>
	</button>
</div>
<input type="hidden" name="send_mail[step_id]">
<input type="hidden" name="send_mail[mail_id]">
