<h2 class="acym__title text-center cell"><?php echo acym_translation('ACYM_YOUR_EMAIL_CONFIGURATION'); ?></h2>

<div class="cell grid-x margin-top-2">
	<p class="cell text-center acym__walkthrough__text">
        <?php echo acym_translation('ACYM_WALKTHROUGH_MAIL_CONFIG_TEXT'); ?>
		<br>
        <?php echo acym_translation('ACYM_WALKTHROUGH_PHPMAIL_TEXT'); ?>
	</p>
</div>

<div class="cell medium-2 hide-for-small-only"></div>
<div class="cell medium-auto small-12 grid-x margin-top-3 text-left margin-y">
	<div class="cell">
		<label>
            <?php echo acym_translation('ACYM_FROM_NAME').acym_info('ACYM_FROM_NAME_INFO'); ?>
			<input type="text" name="from_name" required>
		</label>
	</div>

	<div class="cell">
		<label>
            <?php echo acym_translation('ACYM_FROM_MAIL_ADDRESS').acym_info('ACYM_FROM_ADDRESS_INFO'); ?>
			<input type="email" name="from_address" value="<?php echo empty($data['userEmail']) ? '' : acym_escape($data['userEmail']); ?>" required>
		</label>
	</div>
</div>
<div class="cell medium-2 hide-for-small-only"></div>
<h2 class="cell acym__title__primary__color margin-top-1" id="acym__phpmail-step__mailer__title"><?php echo acym_translation('ACYM_YOUR_SENDING_METHOD'); ?></h2>
<p class="cell acym__walkthrough__text margin-top-1"><?php echo acym_translation('ACYM_WHAT_SERVICE_WOULD_LIKE_TO_USE'); ?></p>
<?php
include acym_getPartial('configuration', 'sending_methods');
?>
<div class="cell grid-x align-center margin-top-3">
	<button disabled type="submit" class="acy_button_submit button" id="acym__selection__button-select" data-task="saveStepPhpmail">
        <?php echo acym_translation('ACYM_SEND_TEST'); ?>
	</button>
</div>
