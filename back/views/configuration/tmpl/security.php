<?php if (!empty($data['acl'])) { ?>
	<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
		<div class="acym_area_title"><?php echo acym_translation('ACYM_PERMISSIONS'); ?></div>
		<div class="grid-x grid-margin-x">
            <?php echo $data['acl']; ?>
			<div class="cell medium-7 grid-x">
				<label class="cell medium-6 small-9"><?php echo acym_translation('ACYM_ADVANCED_ACL').acym_info('ACYM_ADVANCED_ACL_DESC'); ?></label>
				<div class="cell auto">
					<button type="button" class="button button-secondary" id="acym__configuration__acl__toggle"><?php echo acym_translation('ACYM_SHOW_HIDE'); ?></button>
				</div>
			</div>
		</div>
		<div class="grid-x grid-margin-x" id="acym__configuration__acl__zone">
            <?php foreach ($data['acl_advanced'] as $page => $title) { ?>
				<div class="cell grid-x acym__configuration__acl__row">
					<div class="cell large-4 xlarge-3 xxlarge-2">
                        <?php echo acym_translation($title); ?>
					</div>
					<div class="cell large-auto">
                        <?php echo $data['aclType']->display($page); ?>
					</div>
				</div>
            <?php } ?>
		</div>
	</div>
<?php } ?>

<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
	<div class="acym_area_title"><?php echo acym_translation('ACYM_CONFIGURATION_CAPTCHA'); ?></div>
	<div class="grid-x grid-margin-x">
		<div class="cell medium-6 grid-x">
            <?php echo acym_switch('config[captcha]', $this->config->get('captcha', 0), acym_translation('ACYM_CAPTCHA_INVISIBLE')); ?>
		</div>
		<div class="cell medium-6 grid-x">
			<label class="cell large-3" for="security_key">
                <?php echo acym_translation('ACYM_SECURITY_KEY'); ?>
			</label>
			<input class="cell large-9" id="security_key" type="text" name="config[security_key]" value="<?php echo acym_escape($this->config->get('security_key')); ?>" />
		</div>
		<div class="cell medium-6 grid-x">
			<label class="cell large-3" for="recaptcha_sitekey">
				<a href="https://www.google.com/recaptcha/admin" target="_blank"><?php echo acym_translation('ACYM_SITE_KEY'); ?></a>
			</label>
			<input class="cell large-9" id="recaptcha_sitekey" type="text" name="config[recaptcha_sitekey]" value="<?php echo acym_escape($this->config->get('recaptcha_sitekey')); ?>" />
		</div>
		<div class="cell medium-6 grid-x">
			<label class="cell large-3" for="recaptcha_secretkey">
				<a href="https://www.google.com/recaptcha/admin" target="_blank"><?php echo acym_translation('ACYM_SECRET_KEY'); ?></a>
			</label>
			<input class="cell large-9" id="recaptcha_secretkey" type="text" name="config[recaptcha_secretkey]" value="<?php echo acym_escape($this->config->get('recaptcha_secretkey')); ?>" />
		</div>
	</div>
</div>

<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
	<div class="acym_area_title"><?php echo acym_translation('ACYM_EMAIL_VERIFICATION'); ?></div>
	<div class="grid-x grid-margin-x">
		<div class="cell medium-6 grid-x">
            <?php echo acym_switch('config[email_checkdomain]', $this->config->get('email_checkdomain'), acym_translation('ACYM_CHECK_DOMAIN_EXISTS')); ?>
		</div>
	</div>
</div>

<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
	<div class="acym_area_title"><?php echo acym_translation('ACYM_FILES'); ?></div>
	<div class="grid-x grid-margin-x">
		<div class="cell medium-6 grid-x">
			<label class="cell large-3" for="allowed_files">
                <?php echo acym_translation('ACYM_ALLOWED_FILES'); ?>
			</label>
			<input class="cell large-9" id="allowed_files" type="text" name="config[allowed_files]" value="<?php echo acym_escape($this->config->get('allowed_files')); ?>" />
		</div>
	</div>
	<div class="grid-x grid-margin-x">
		<div class="cell medium-6 grid-x">
			<label class="cell large-3" for="uploadfolder">
                <?php echo acym_translation('ACYM_UPLOAD_FOLDER'); ?>
			</label>
			<input class="cell large-9" id="uploadfolder" type="text" name="config[uploadfolder]" value="<?php echo acym_escape($this->config->get('uploadfolder')); ?>" />
		</div>
	</div>
</div>

<div class="acym__configuration__check-database acym__content acym_area padding-vertical-1 padding-horizontal-2">
	<div class="acym_area_title"><?php echo acym_translation('ACYM_CONFIGURATION_DB_MAINTENANCE'); ?></div>
	<div class="grid-x grid-margin-x">
		<div class="cell medium-shrink">
			<button type="button" class="button button-secondary" id="checkdb_button"><?php echo acym_translation('ACYM_CHECK_DB'); ?></button>
		</div>
		<div class="cell medium-auto" id="checkdb_report"></div>
	</div>
    <?php if (acym_existsAcyMailing59()) { ?>
		<div class="grid-x grid-margin-x">
			<div class="cell medium-shrink">
				<button type="submit" data-task="redomigration" class="button button-secondary acy_button_submit"><?php echo acym_translation('ACYM_REDO_MIGRATION'); ?></button>
			</div>
		</div>
    <?php } ?>
</div>
