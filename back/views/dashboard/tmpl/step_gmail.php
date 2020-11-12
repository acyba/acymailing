<h2 class="acym__walkthrough__title cell"><?php echo acym_translation('ACYM_YOUR_EMAIL_CONFIGURATION'); ?></h2>

<div class="cell grid-x">
	<p class="cell text-center acym__walkthrough__text">
        <?php echo acym_translation('ACYM_WALKTHROUGH_MAIL_CONFIG_TEXT'); ?>
	</p>
</div>

<div class="cell medium-2 hide-for-small-only"></div>
<div class="cell medium-auto small-12 grid-x">
	<div class="cell grid-x text-left margin-top-2">
		<div class="cell">
			<label>
                <?php echo acym_translation('ACYM_FROM_NAME').acym_info('ACYM_FROM_NAME_INFO'); ?>
				<input type="text" name="from_name" class="acym__light__input" required>
			</label>
		</div>

		<div class="cell">
			<label>
                <?php echo acym_translation('ACYM_FROM_MAIL_ADDRESS').acym_info('ACYM_FROM_ADDRESS_INFO'); ?>
				<input type="email" name="from_address" class="acym__light__input" value="<?php echo empty($data['userEmail']) ? '' : acym_escape($data['userEmail']); ?>" required>
			</label>
		</div>
	</div>

	<div class="cell grid-x text-left margin-top-2">
		<h3 class="cell medium-shrink acym__walkthrough__section"><?php echo acym_translation('ACYM_YOUR_GMAIL_ACCOUNT'); ?></h3>

        <?php echo acym_tooltip(
            '<p class="cell medium-auto acym__walkthrough__section__help">'.acym_translation('ACYM_WHY_DO_WE_NEED_THIS').'</p>',
            acym_translation('ACYM_WALKTHROUGH_GMAIL_TEXT')
        ); ?>

		<div class="cell">
			<label>
                <?php echo acym_translation('ACYM_GMAIL_EMAIL'); ?>
				<input type="email" name="gmail_address" class="acym__light__input" required>
			</label>
		</div>

		<div class="cell">
			<label>
                <?php echo acym_translation('ACYM_GMAIL_PASSWORD'); ?>
				<input type="password" name="gmail_password" class="acym__light__input" required>
			</label>
		</div>
	</div>
</div>
<div class="cell medium-2 hide-for-small-only"></div>

<div class="cell grid-x align-center margin-top-3">
	<button type="submit" class="acy_button_submit button" data-task="saveStepGmail"><?php echo acym_translation('ACYM_SEND_TEST'); ?></button>
</div>

