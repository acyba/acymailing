<h2 class="cell acym__title text-center"><?php echo acym_translationSprintf('ACYM_WALKTHROUGH_LICENCE_TITLE', $data['level']); ?></h2>
<div class="cell margin-top-1 align-center">
	<div class="cell margin-bottom-2">
		<p> <?php echo acym_translationSprintf('ACYM_ENTER_LICENSE_KEY_TO_ACTIVATE_ACYM_X_FEATURES', $data['level']); ?></p>
		<p> <?php echo '('.acym_externalLink('ACYM_FIND_YOUR_LICENSE', ACYM_REDIRECT.'subscription-page', true, true).')' ?></p>
	</div>
</div>
<div class="cell margin-bottom-3 text-left">
	<div class="cell grid-x grid-margin-x acym_vcenter margin-top-2">
		<label class="cell small-5 large-3">
            <?php echo acym_translation('ACYM_STATUS_ACTIVATION'); ?>
		</label>
		<div class="cell small-7 large-9 acym__color__red" id="acym__walk_through_license__licenseStatus">
            <?php echo acym_translation('ACYM_NOT_ENABLED_YET'); ?>
		</div>
	</div>
	<div class="cell grid-x grid-margin-x margin-y acym_vcenter margin-top-2">
		<label class="cell large-3" for="acym__configuration__license-key">
            <?php echo acym_translation('ACYM_YOUR_LICENSE_KEY').acym_info('ACYM_LICENSE_DESC'); ?>
		</label>
		<input class="cell large-5" type="text" name="config[license_key]" id="acym__configuration__license-key" value="">
		<button type="button"
				id="acym__walk_through_license__button__license"
				class="cell shrink button">
            <?php echo acym_translation('ACYM_ATTACH_MY_LICENSE'); ?>
		</button>
		<i class="cell shrink acymicon-circle-o-notch acymicon-spin is-hidden" id="acym__walkthrough__step_license__wait_attach_license_icon"></i>
	</div>
	<div class="cell grid-x grid-margin-x margin-y acym_vcenter margin-top-2 padding-right-0">
		<label class="cell small-5 large-3"><?php echo acym_translation('ACYM_AUTOMATED_TASKS').acym_info('ACYM_AUTOMATED_TASKS_DESC'); ?></label>
		<div class="cell small-7 large-5 acym__color__red" id="acym__walk_through_license__cron_label"><?php echo acym_translation('ACYM_DEACTIVATED'); ?></div>
        <?php
        echo acym_tooltip(
            '<a type="button" id="acym__walk_through_license__button__cron" class="grid-x align-center acym_vcenter button" disabled>'.
            acym_translation('ACYM_ACTIVATE_IT')
            .'</a>',
            acym_translation('ACYM_ACTIVATE_IT_CRON_DESC'),
            'cell shrink',
            '',
            '',
            'acym__tooltip_button__cron'
        );
        ?>
		<i class="cell shrink acymicon-circle-o-notch acymicon-spin is-hidden" id="acym__walkthrough__step_license__wait_active_cron_icon"></i>
	</div>
</div>
<div class="cell text-center margin-top-3">
	<button type="button" id="acy_button_submit_stepLicense" class="acy_button_submit button" data-task="passStepLicence">
        <?php echo acym_translation('ACYM_CONTINUE'); ?>
	</button>
</div>

