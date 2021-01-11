<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm">
	<div id="acym_content" class="acym__content cell grid-x popup_size">
		<div class="cell margin-bottom-1">
			<h1 class="acym__modal__language__share__title cell text-center margin-bottom-1"><?php echo acym_translation('ACYM_SHARE_TRANSLATION'); ?></h1>
			<div class="acym__modal__language__share__information">
				<h6 class="margin-bottom-1"><?php echo acym_translation('ACYM_SHARE_CONFIRMATION_1'); ?></h6>
				<h6><?php echo acym_translation('ACYM_SHARE_CONFIRMATION_2'); ?></h6>
				<h6><?php echo acym_translation('ACYM_SHARE_CONFIRMATION_3'); ?></h6>
			</div>
			<h3 class="acym__title acym__title__secondary margin-top-1"><?php echo acym_translation('ACYM_EMAIL_BODY'); ?></h3>
			<textarea rows="8" name="mailbody" class="acym__language__modal__body margin-top-1">Hi Acyba team,
Here is a new version of the language file, I translated few more strings...</textarea>
		</div>
		<input type="hidden" name="code" value="<?php echo acym_escape($data['file']->name); ?>" />
		<div class="medium-10 hide-for-small-only cell"></div>
		<button type="button" data-task="send" data-confirmation-message="ACYM_SURE_SEND_TRANSALTION" class="button cell medium-2 acy_button_submit">
            <?php echo acym_translation('ACYM_SEND'); ?>
		</button>
        <?php acym_formOptions(); ?>
	</div>
</form>
