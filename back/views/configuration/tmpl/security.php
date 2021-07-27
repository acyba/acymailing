<?php if (!empty($data['acl'])) { ?>
	<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
		<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_PERMISSIONS'); ?></div>
		<div class="grid-x grid-margin-x margin-y">
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
	<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_CONFIGURATION_CAPTCHA'); ?></div>
	<div class="grid-x grid-margin-x margin-y">
		<div class="cell medium-6 grid-x">
			<label class="cell large-3" for="security_key">
                <?php echo acym_translation('ACYM_CONFIGURATION_CAPTCHA').acym_info('ACYM_CAPTCHA_DESC'); ?>
			</label>
			<div class="cell large-9">
                <?php
                $captchaOptions = array_replace(
                    [
                        'none' => acym_translation('ACYM_NONE'),
                        'acym_ireCaptcha' => acym_translation('ACYM_CAPTCHA_INVISIBLE'),
                    ],
                    acym_getCmsCaptcha()
                );
                echo acym_select(
                    $captchaOptions,
                    'config[captcha]',
                    $this->config->get('captcha', 'none'),
                    [
                        'class' => 'acym__select',
                        'data-toggle-select' => '{"acym_ireCaptcha":".acym__config__captcha__recaptcha"}',
                    ]
                );
                ?>
			</div>
		</div>
		<div class="cell medium-6 grid-x">
			<label class="cell large-3" for="security_key">
                <?php echo acym_translation('ACYM_SECURITY_KEY').acym_info('ACYM_SECURITY_KEY_DESC'); ?>
			</label>
			<input class="cell large-9" id="security_key" type="text" name="config[security_key]" value="<?php echo acym_escape($this->config->get('security_key')); ?>" />
		</div>
		<div class="cell medium-6 grid-x acym__config__captcha__recaptcha">
			<label class="cell large-3" for="recaptcha_sitekey">
                <?php
                echo acym_translation('ACYM_SITE_KEY');
                echo acym_tooltip(
                    '<span class="acym__tooltip__info__container"><i class="acym__tooltip__info__icon acymicon-info-circle"></i></span>',
                    acym_translation('ACYM_RECAPTCHA_KEY_DESC'),
                    'acym__tooltip__info',
                    '',
                    'https://www.google.com/recaptcha/admin'
                );
                ?>
			</label>
			<input class="cell large-9"
				   id="recaptcha_sitekey"
				   type="text"
				   name="config[recaptcha_sitekey]"
				   value="<?php echo acym_escape($this->config->get('recaptcha_sitekey')); ?>" />
		</div>
		<div class="cell medium-6 grid-x acym__config__captcha__recaptcha">
			<label class="cell large-3" for="recaptcha_secretkey">
                <?php
                echo acym_translation('ACYM_SECRET_KEY');
                echo acym_tooltip(
                    '<span class="acym__tooltip__info__container"><i class="acym__tooltip__info__icon acymicon-info-circle"></i></span>',
                    acym_translation('ACYM_RECAPTCHA_KEY_DESC'),
                    'acym__tooltip__info',
                    '',
                    'https://www.google.com/recaptcha/admin'
                );
                ?>
			</label>
			<input class="cell large-9"
				   id="recaptcha_secretkey"
				   type="text"
				   name="config[recaptcha_secretkey]"
				   value="<?php echo acym_escape($this->config->get('recaptcha_secretkey')); ?>" />
		</div>
	</div>
</div>

<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
	<div class="acym__title acym__title__secondary grid-x">
        <?php echo acym_translation('ACYM_EMAIL_VERIFICATION'); ?>
		<div class="margin-left-1">
            <?php
            //TODO AcyChecker online
            if (false) {
                $score = 100;
                $tooltip = [];
                if ($this->config->get('require_confirmation') == 0) {
                    $score -= 40;
                    $tooltip[] = acym_translation('ACYM_EMAIL_VERIFICATION_SCORE_DESC_DOUBLE_OPT_IN');
                }
                if ($this->config->get('email_spellcheck') == 0) {
                    $score -= 10;
                    $tooltip[] = acym_translation('ACYM_EMAIL_VERIFICATION_SCORE_DESC_TYPO');
                }
                if ($this->config->get('email_verification') == 0) {
                    $score -= 50;
                    $tooltip[] = acym_translation('ACYM_EMAIL_VERIFICATION_SCORE_DESC_DISPOSABLE');
                }

                echo '('.acym_translationSprintf('ACYM_EMAIL_VERIFICATION_SCORE', $score).')';
                if (!empty($tooltip)) {
                    echo acym_info(implode('<br />', $tooltip));
                }
            }
            ?>
		</div>
	</div>
	<div class="grid-x margin-y">
		<div class="cell medium-6 grid-x">
            <?php
            echo acym_switch(
                'config[email_checkdomain]',
                $this->config->get('email_checkdomain'),
                acym_translation('ACYM_CHECK_DOMAIN_EXISTS'),
                [],
                'xlarge-9 large-6 small-9'
            );
            ?>
		</div>
		<div class="cell medium-6 grid-x">
            <?php
            echo acym_switch(
                'config[email_spellcheck]',
                $this->config->get('email_spellcheck'),
                acym_translation('ACYM_SPELLCHECK_SUGGESTIONS').acym_info('ACYM_SPELLCHECK_SUGGESTIONS_DESC'),
                [],
                'xlarge-9 large-6 small-9'
            );
            ?>
		</div>
        <?php
        //TODO AcyChecker online
        if (false) {
            ?>
			<div class="cell grid-x acychecker_ad">
				<div class="cell ">
					<h6>
                        <?php echo acym_translation('ACYM_ACYCHECKER_CONFIG_AD_TITLE'); ?>
						<img class="acychecker_logo" alt="logo check this email" src="<?php echo ACYM_IMAGES.'icons/logo_acychecker.png'; ?>" />
					</h6>
				</div>
				<div class="cell xlarge-6 grid-x">
                    <?php
                    echo acym_switch(
                        'config[email_verification]',
                        $this->config->get('email_verification'),
                        acym_translation('ACYM_ACYCHECKER_CHECK_SUBSCRIPTION'),
                        [],
                        'xlarge-9 large-6 small-9',
                        'auto',
                        '',
                        $data['acychecker_installed'] ? 'email_verification' : null,
                        true,
                        '',
                        !$data['acychecker_installed']
                    );
                    ?>
				</div>
				<div id="email_verification" class="cell grid-x">
					<label class="cell margin-top-1">
                        <?php echo acym_translation('ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK'); ?>
					</label>
					<div class="cell large-6 grid-x">
                        <?php
                        $verificationOptions = [
                            'email_verification_non_existing' => ['ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_NON_EXISTING', ''],
                            'email_verification_disposable' => ['ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_DISPOSABLE', 'ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_DISPOSABLE_DESC'],
                            'email_verification_free' => ['ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_FREE', 'ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_FREE_DESC'],
                            'email_verification_role' => ['ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_ROLE', 'ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_ROLE_DESC'],
                            'email_verification_acceptall' => ['ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_ACCEPT_ALL', 'ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_ACCEPT_ALL_DESC'],
                        ];

                        foreach ($verificationOptions as $option => $label) {
                            echo '<div class="cell grid-x">';
                            $optionText = acym_translation($label[0]);
                            if (!empty($label[1])) $optionText .= acym_info($label[1]);
                            echo acym_switch(
                                'config['.$option.']',
                                $this->config->get($option),
                                $optionText,
                                [],
                                'small-9',
                                'auto',
                                '',
                                null,
                                true,
                                '',
                                !$data['acychecker_installed']
                            );
                            echo '</div>';
                        }
                        ?>
					</div>
                    <?php if (!$data['acychecker_installed']) { ?>
						<div class="cell large-6 grid-x align-center">
							<div class="cell large-6 grid-x align-center text-center">
                                <?php echo acym_translation('ACYM_ACYCHECKER_CONFIG_AD'); ?>
								<a target="_blank" class="cell shrink button button-secondary" href="<?php echo $data['acychecker_get_link']; ?>">
                                    <?php echo acym_translation('ACYM_ACYCHECKER_CONFIG_AD_BUTTON'); ?>
								</a>
							</div>
						</div>
                    <?php } ?>
				</div>
			</div>
            <?php
        }
        ?>
	</div>
</div>

<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
	<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_FILES'); ?></div>
	<div class="grid-x grid-margin-x margin-y">
		<div class="cell medium-6 grid-x">
			<label class="cell large-3" for="allowed_files">
                <?php echo acym_translation('ACYM_ALLOWED_FILES').acym_info('ACYM_ALLOWED_FILES_DESC'); ?>
			</label>
			<input class="cell large-9" id="allowed_files" type="text" name="config[allowed_files]" value="<?php echo acym_escape($this->config->get('allowed_files')); ?>" />
		</div>
		<div class="cell medium-6 grid-x">
			<label class="cell large-3" for="uploadfolder">
                <?php echo acym_translation('ACYM_UPLOAD_FOLDER').acym_info('ACYM_UPLOAD_FOLDER_DESC'); ?>
			</label>
			<input class="cell large-9" id="uploadfolder" type="text" name="config[uploadfolder]" value="<?php echo acym_escape($this->config->get('uploadfolder')); ?>" />
		</div>
	</div>
</div>
<div class="acym__configuration__check-database acym__content acym_area padding-vertical-1 padding-horizontal-2">
	<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_REDIRECTIONS'); ?></div>
	<div class="grid-x grid-margin-x margin-y">
		<div class="cell medium-6 grid-x acym_vcenter">
			<label class="cell large-3" for="allowed_files">
                <?php echo acym_translation('ACYM_ALLOWED_HOSTS').acym_info('ACYM_ALLOWED_HOSTS_DESC'); ?>
			</label>
			<div class="cell grid-x large-9">
                <?php
                $allowedHosts = $this->config->get('allowed_hosts', '');
                if (empty($allowedHosts)) {
                    $allowedHosts = [];
                } else {
                    $allowedHosts = explode(',', $allowedHosts);
                }
                $allowedHostsFormatted = [];
                if (!empty($allowedHosts)) {
                    $allowedHostsFormatted = [];
                    foreach ($allowedHosts as $host) {
                        $allowedHostsFormatted[$host] = $host;
                    }
                }
                echo acym_selectMultiple(
                    $allowedHostsFormatted,
                    'config[allowed_hosts]',
                    $allowedHosts,
                    ['class' => 'acym__allowed__hosts__select', 'placeholder' => acym_translation('ACYM_ENTER_NEW_DOMAIN')]
                );
                ?>
			</div>
		</div>
	</div>
</div>

<div class="acym__configuration__check-database acym__content acym_area padding-vertical-1 padding-horizontal-2">
	<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_CONFIGURATION_DB_MAINTENANCE'); ?></div>
	<div class="grid-x grid-margin-x margin-y">
		<div class="cell grid-x">
            <?php
            echo acym_tooltip(
                '<button type="button" class="cell medium-shrink button button-secondary" id="checkdb_button">'.acym_translation('ACYM_CHECK_DB').'</button>',
                acym_translation('ACYM_INTRO_CHECK_DATABASE')
            );
            ?>
			<div class="cell auto padding-left-1" id="checkdb_report"></div>
		</div>
        <?php if (acym_existsAcyMailing59()) { ?>
			<div class="cell grid-x">
				<button type="submit" data-task="redomigration" class="cell medium-shrink button button-secondary acy_button_submit">
                    <?php echo acym_translation('ACYM_REDO_MIGRATION'); ?>
				</button>
			</div>
        <?php } ?>
	</div>
</div>
