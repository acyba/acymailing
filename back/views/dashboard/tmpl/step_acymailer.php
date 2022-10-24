<input type="hidden" name="skip" value="acymailer" />
<input type="hidden" id="acym__walkthrough__acymailer__domain" value="<?php echo acym_escape($data['domain']); ?>" />

<h2 class="acym__title text-center cell"><?php echo acym_translation('ACYM_ASS_CONFIGURATION'); ?></h2>

<div class="cell medium-1 hide-for-small-only"></div>
<div class="cell medium-auto small-12 grid-x margin-top-1 text-left margin-y">
	<div class="cell grid-x">
		<p class="cell text-center">
            <?php echo acym_translation('ACYM_WALK_ACYMAILER_1'); ?>
		</p>
		<p class="cell text-center">
            <?php echo acym_translationSprintf('ACYM_WALK_ACYMAILER_2', empty($data['domain']) ? 'your.domain.com' : $data['domain']); ?>
		</p>
		<p class="cell text-center">
            <?php echo acym_translation('ACYM_WALK_ACYMAILER_3'); ?>
			<a href="<?php echo ACYM_DOCUMENTATION; ?>external-sending-method/acymailing-sending-service#how-to-add-the-dns-entries-on-my-server"
			   class="cell shrink"
			   id="acym__walkthrough__acymailer__cname__doc"
			   target="_blank">
                <?php echo acym_translation('ACYM_STEP_BY_STEP_GUIDE'); ?>
			</a>
		</p>
	</div>

	<div class="grid-x acym__listing cell" id="acym__walkthrough__acymailer__domain__cname">
		<div class="grid-x cell acym__listing__header text-left">
			<div class="medium-6 cell acym__listing__header__title">
                <?php echo acym_translation('ACYM_NAME'); ?>
			</div>
			<div class="medium-6 cell acym__listing__header__title">
                <?php echo acym_translation('ACYM_VALUE'); ?>
			</div>
		</div>
        <?php foreach ($data['CnameRecords'] as $cnameRecord) { ?>
			<div class="grid-x cell acym__listing__row">
				<div class="grid-x medium-6 cell">
                    <?php echo $cnameRecord['name']; ?>
				</div>
				<div class="grid-x medium-6 cell">
                    <?php echo $cnameRecord['value']; ?>
				</div>
			</div>
        <?php } ?>
	</div>
	<div class="cell grid-x acym_vcenter align-center" id="acym__configuration__acymailer__add__error">
		<i class="acymicon-close acym__color__red cell shrink"></i>
		<span class="cell shrink" id="acym__configuration__acymailer__add__error__message"></span>
	</div>

	<div class="cell grid-x">
		<p class="cell text-center">
            <?php echo acym_translation('ACYM_WALK_ACYMAILER_4'); ?>
		</p>
		<p class="cell text-center">
            <?php echo acym_translation('ACYM_WALK_ACYMAILER_5'); ?>
		</p>
		<h5 class="cell text-center font-bold margin-top-2 margin-bottom-1">
            <?php echo acym_translation('ACYM_WALK_ACYMAILER_6'); ?>
		</h5>
		<p class="cell text-center">
            <?php echo acym_translation('ACYM_WALK_ACYMAILER_7'); ?>
		</p>
	</div>

	<div class="cell grid-x margin-top-1 acym_vcenter">
		<div class="cell small-2 medium-4 text-right">
            <?php echo acym_translation('ACYM_WALK_ACYMAILER_DOMAIN_STATUS'); ?>
		</div>
		<div class="cell small-10 medium-8 padding-left-1 acym_vcenter" id="acym__walkthrough__acymailer__domain_status">
            <?php
            if ($data['status'] === 'SUCCESS') {
                $iconClass = 'acymicon-check-circle acym__color__green';
                $text = acym_translation('ACYM_WALK_ACYMAILER_STATUS_SUCCESS');
            } elseif ($data['status'] === 'FAILED') {
                $iconClass = 'acymicon-remove acym__color__red';
                $text = acym_translation('ACYM_WALK_ACYMAILER_STATUS_FAIL');
            } else {
                $iconClass = 'acymicon-access_time acym__color__orange';
                $text = acym_translation('ACYM_WALK_ACYMAILER_STATUS_WAIT');
            }
            ?>
			<i class="<?php echo $iconClass; ?> padding-right-1"></i>
            <?php echo $text; ?>
		</div>
	</div>

	<div class="cell grid-x grid-margin-x align-center">
		<button type="button" class="button cell shrink" id="acym__walkthrough__acymailer__domain_status_reload">
            <?php echo acym_translation('ACYM_CHECK_DOMAIN_VALIDATION'); ?>
		</button>
		<a href="<?php echo ACYM_ACYMAILING_WEBSITE; ?>contact/" target="_blank" class="button button-secondary"><?php echo acym_translation('ACYM_CONTACT_SUPPORT'); ?></a>
	</div>
</div>
<div class="cell medium-1 hide-for-small-only"></div>

<h3 class="cell text-center margin-top-3" id="acym__walkthrough__acymailer__information"><?php echo acym_translation('ACYM_YOUR_EMAIL_INFORMATION'); ?></h3>
<div class="cell medium-1 hide-for-small-only"></div>
<div class="cell medium-auto small-12 grid-x margin-top-1 text-left margin-y">
	<div class="cell">
		<label>
            <?php echo acym_translation('ACYM_FROM_NAME').acym_info('ACYM_FROM_NAME_INFO'); ?>
			<input type="text" name="from_name" value="<?php echo acym_escape($data['siteName']); ?>" required>
		</label>
	</div>

	<div class="cell">
		<label>
            <?php echo acym_translation('ACYM_FROM_MAIL_ADDRESS').acym_info('ACYM_FROM_ADDRESS_INFO'); ?>
			<input type="email" name="from_address" value="<?php echo acym_escape($data['userEmail']); ?>" required>
		</label>
	</div>
</div>
<div class="cell medium-1 hide-for-small-only"></div>

<div class="cell grid-x align-center margin-top-3">
	<button type="submit"
			class="acy_button_submit button"
			id="acym__selection__button-select"
			data-task="saveStepAcyMailer"
        <?php echo $data['status'] === 'SUCCESS' ? '' : 'disabled'; ?>>
        <?php echo acym_translation('ACYM_SEND_TEST'); ?>
	</button>
</div>
