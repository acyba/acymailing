<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
    <?php if (acym_level(ACYM_ESSENTIAL)) { ?>
		<div class="margin-bottom-2 acym__configuration__rest_api cell grid-x">
			<div class="acym__title acym__title__secondary grid-x">
                <?php echo acym_translation('ACYM_REST_API'); ?>
                <?php echo acym_externalLink(
                    'ACYM_DOCUMENTATION',
                    ACYM_DOCUMENTATION.'v/rest-api/',
                    true,
                    true,
                    ['margin-left-1']
                ); ?>
			</div>
			<div class="cell grid-x acym_vcenter">
				<label class="cell large-3" for="php_overrides">
                    <?php echo acym_translation('ACYM_ACTIVATE_REST_API').acym_info(['textShownInTooltip' => 'ACYM_ACTIVATE_REST_API_DESC']); ?>
				</label>
				<div class="cell grid-x large-9">
                    <?php
                    echo acym_switch(
                        'config[rest_api]',
                        $this->config->get('rest_api', 0)
                    );
                    ?>
				</div>
			</div>
		</div>
    <?php } ?>

    <?php if (!empty($data['labelDropdownCaptcha']) && isset($data['level']) && isset($data['captchaOptions'])) { ?>
		<div class="margin-bottom-2 cell margin-y acym__configuration__security__captcha">
			<div class="acym__title acym__title__secondary"><?php echo $data['labelDropdownCaptcha']; ?></div>
			<div class="grid-x grid-margin-x margin-y">
				<div class="cell medium-6 grid-x">
					<label class="cell large-3" for="security_key">
                        <?php echo acym_translation('ACYM_CONFIGURATION_CAPTCHA').acym_info(['textShownInTooltip' => 'ACYM_CAPTCHA_DESC']); ?>
					</label>
					<div class="cell large-9">
                        <?php
                        $attribs = [
                            'class' => 'acym__select',
                            'data-toggle-select' => '{"acym_ireCaptcha":".acym__config__captcha__recaptcha","acym_reCaptcha_v3":".acym__config__captcha__recaptcha, .acym__config__captcha__recaptcha_v3","acym_hcaptcha":".acym__config__captcha__hcaptcha" }',
                        ];
                        // To disable Captcha dropdown
                        if (!$data['level']) {
                            $attribs['disabled'] = '';
                        }

                        echo acym_select(
                            $data['captchaOptions'],
                            'config[captcha]',
                            $this->config->get('captcha', 'none'),
                            $attribs
                        );
                        ?>
					</div>
				</div>
                <?php if ($data['level']) { ?>
					<div class="cell medium-6 grid-x">
						<label class="cell large-3" for="security_key">
                            <?php echo acym_translation('ACYM_SECURITY_KEY').acym_info(['textShownInTooltip' => 'ACYM_SECURITY_KEY_DESC']); ?>
						</label>
						<input class="cell large-9"
							   id="security_key"
							   type="text"
							   name="config[security_key]"
							   value="<?php echo acym_escape($this->config->get('security_key')); ?>" />
					</div>
					<div class="cell medium-6 grid-x acym__config__captcha__hcaptcha">
						<label class="cell large-3" for="hcaptcha_sitekey">
                            <?php
                            echo acym_translation('ACYM_GENERAL_SITE_KEY');
                            echo acym_tooltip(
                                [
                                    'hoveredText' => '<span class="acym__tooltip__info__container"><i class="acym__tooltip__info__icon acymicon-info-circle"></i></span>',
                                    'textShownInTooltip' => acym_translation('ACYM_CLICK_HERE_TO_CREATE'),
                                    'classContainer' => 'acym__tooltip__info',
                                    'link' => 'https://dashboard.hcaptcha.com/sites/new',
                                ]
                            );
                            ?>
						</label>
						<input class="cell large-9"
							   id="hcaptcha_sitekey"
							   type="text"
							   name="config[hcaptcha_sitekey]"
							   value="<?php echo acym_escape($this->config->get('hcaptcha_sitekey')); ?>" />
					</div>
					<div class="cell medium-6 grid-x acym__config__captcha__hcaptcha">
						<label class="cell large-3" for="hcaptcha_secretkey">
                            <?php
                            echo acym_translation('ACYM_GENERAL_SECRET_KEY');
                            echo acym_tooltip(
                                [
                                    'hoveredText' => '<span class="acym__tooltip__info__container"><i class="acym__tooltip__info__icon acymicon-info-circle"></i></span>',
                                    'textShownInTooltip' => acym_translation('ACYM_CLICK_HERE_TO_CREATE'),
                                    'classContainer' => 'acym__tooltip__info',
                                    'link' => 'https://dashboard.hcaptcha.com/sites/new',
                                ]
                            );
                            ?>
						</label>
						<input class="cell large-9"
							   id="hcaptcha_secretkey"
							   type="text"
							   name="config[hcaptcha_secretkey]"
							   value="<?php echo acym_escape($this->config->get('hcaptcha_secretkey')); ?>" />
					</div>

					<div class="cell medium-6 grid-x acym__config__captcha__recaptcha">
						<label class="cell large-3" for="recaptcha_sitekey">
                            <?php
                            echo acym_translation('ACYM_SITE_KEY');
                            echo acym_tooltip(
                                [
                                    'hoveredText' => '<span class="acym__tooltip__info__container"><i class="acym__tooltip__info__icon acymicon-info-circle"></i></span>',
                                    'textShownInTooltip' => acym_translation('ACYM_CLICK_HERE_TO_CREATE'),
                                    'classContainer' => 'acym__tooltip__info',
                                    'link' => 'https://www.google.com/recaptcha/admin',
                                ]
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
                                [
                                    'hoveredText' => '<span class="acym__tooltip__info__container"><i class="acym__tooltip__info__icon acymicon-info-circle"></i></span>',
                                    'textShownInTooltip' => acym_translation('ACYM_CLICK_HERE_TO_CREATE'),
                                    'classContainer' => 'acym__tooltip__info',
                                    'link' => 'https://www.google.com/recaptcha/admin',
                                ]
                            );
                            ?>
						</label>
						<input class="cell large-9"
							   id="recaptcha_secretkey"
							   type="text"
							   name="config[recaptcha_secretkey]"
							   value="<?php echo acym_escape($this->config->get('recaptcha_secretkey')); ?>" />
					</div>
					<div class="cell medium-6 grid-x acym__config__captcha__recaptcha_v3">
						<label class="cell large-3" for="recaptcha_score">
                            <?php
                            echo acym_translation('ACYM_CAPTCHA_SCORE');
                            echo acym_tooltip(
                                [
                                    'hoveredText' => '<span class="acym__tooltip__info__container"><i class="acym__tooltip__info__icon acymicon-info-circle"></i></span>',
                                    'textShownInTooltip' => acym_translation('ACYM_CAPTCHA_SCORE_DESC'),
                                    'classContainer' => 'acym__tooltip__info',
                                    'link' => 'https://developers.google.com/recaptcha/docs/v3',
                                ]
                            );
                            ?>
						</label>
						<input class="cell large-9"
							   id="recaptcha_score"
							   type="number"
							   name="config[recaptcha_score]"
							   min="0"
							   max="1"
							   step="0.1"
							   onKeyDown="return false"
							   value="<?php echo acym_escape($this->config->get('recaptcha_score', 0.5)); ?>" />
					</div>
                <?php } ?>
			</div>
		</div>
    <?php } ?>

	<div class="acym__configuration__security__email cell grid-x">
		<div class="acym__title acym__title__secondary grid-x">
            <?php echo acym_translation('ACYM_EMAIL_VERIFICATION'); ?>
			<div class="margin-left-1">
                <?php
                $score = 100;
                $tooltip = [];
                if ($this->config->get('email_checkdomain') == 0) {
                    $score -= 10;
                    $tooltip[] = acym_translation('ACYM_EMAIL_VERIFICATION_SCORE_DESC_DOMAIN_VERIFICATION');
                }
                if (empty($this->config->get('require_confirmation', 1))) {
                    $score -= 30;
                    $tooltip[] = acym_translation('ACYM_EMAIL_VERIFICATION_SCORE_DESC_DOUBLE_OPT_IN');
                }
                if ($this->config->get('email_spellcheck') == 0) {
                    $score -= 10;
                    $tooltip[] = acym_translation('ACYM_EMAIL_VERIFICATION_SCORE_DESC_TYPO');
                }

                if (!acym_isAcyCheckerInstalled()) {
                    $score -= 50;
                    $tooltip[] = acym_translation('ACYM_EMAIL_VERIFICATION_SCORE_DESC_CHECKER');
                } else {
                    if ($this->config->get('email_verification') == 0) {
                        $score -= 50;
                        $tooltip[] = acym_translation('ACYM_EMAIL_VERIFICATION_SCORE_DESC_CHECKER');
                    }

                    $oneEnabled = false;

                    if ($this->config->get('email_verification', 0) != 0) {
                        $verificationOptions = [
                            'email_verification_non_existing',
                            'email_verification_disposable',
                            'email_verification_free',
                            'email_verification_role',
                            'email_verification_acceptall',
                        ];

                        foreach ($verificationOptions as $verificationOption) {
                            if ($this->config->get($verificationOption, 0) == 0) continue;

                            $oneEnabled = true;
                            break;
                        }
                    }

                    if (!$oneEnabled) $tooltip[] = acym_translation('ACYM_YOU_SHOULD_ENABLE_CHECK');
                }

                echo '('.acym_translationSprintf('ACYM_EMAIL_VERIFICATION_SCORE', $score).')';
                if (!empty($tooltip)) {
                    echo acym_info(
                        [
                            'textShownInTooltip' => implode('<br />', $tooltip),
                        ]
                    );
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
                    acym_translation('ACYM_SPELLCHECK_SUGGESTIONS').acym_info(['textShownInTooltip' => 'ACYM_SPELLCHECK_SUGGESTIONS_DESC']),
                    [],
                    'xlarge-9 large-6 small-9'
                );
                ?>
			</div>
			<div class="cell medium-6 grid-x">
                <?php
                echo acym_switch(
                    'config[email_confirmation]',
                    $this->config->get('email_confirmation'),
                    acym_translation('ACYM_ENABLE_EMAIL_CONFIRMATION_FOR_SUBSCRIPTION_FORM').acym_info(
                        ['textShownInTooltip' => 'ACYM_ENABLE_EMAIL_CONFIRMATION_FOR_SUBSCRIPTION_FORM_DESC']
                    ),
                    [],
                    'xlarge-9 large-6 small-9'
                );
                ?>
			</div>
			<div class="cell grid-x acychecker_ad">
				<div class="cell ">
					<h6>
                        <?php echo acym_translation('ACYM_ACYCHECKER_CONFIG_AD_TITLE'); ?>
						<img class="acychecker_logo" alt="logo AcyChecker" src="<?php echo ACYM_IMAGES.'icons/logo_acychecker.png'; ?>" />
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
                            'email_verification_disposable' => [
                                'ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_DISPOSABLE',
                                'ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_DISPOSABLE_DESC',
                            ],
                            'email_verification_free' => ['ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_FREE', 'ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_FREE_DESC'],
                            'email_verification_role' => ['ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_ROLE', 'ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_ROLE_DESC'],
                            'email_verification_acceptall' => [
                                'ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_ACCEPT_ALL',
                                'ACYM_ACYCHECKER_CHECK_SUBSCRIPTION_BLOCK_ACCEPT_ALL_DESC',
                            ],
                        ];

                        foreach ($verificationOptions as $option => $label) {
                            echo '<div class="cell grid-x margin-bottom-1">';
                            $optionText = acym_translation($label[0]);
                            if (!empty($label[1])) {
                                $optionText .= acym_info(['textShownInTooltip' => $label[1]]);
                            }
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
		</div>
	</div>
</div>

<div class="acym__content acym_area padding-horizontal-2 acym__configuration__advanced">
	<div class="cell grid-x acym__configuration__showmore-head">
		<div class="acym__title acym__title__secondary cell auto margin-bottom-0"><?php echo acym_translation('ACYM_CONFIGURATION_ADVANCED'); ?></div>
		<div class="cell shrink">
            <?php echo acym_showMore('acym__configuration__security__advanced__content'); ?>
		</div>
	</div>
	<div id="acym__configuration__security__advanced__content" style="display:none;">
        <?php if (!empty($data['acl'])) { ?>
			<div class="margin-bottom-2">
				<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_PERMISSIONS'); ?></div>
				<div class="grid-x grid-margin-x margin-y">
                    <?php echo $data['acl']; ?>
					<div class="cell grid-x">
						<label class="cell large-3 medium-5 small-9">
                            <?php echo acym_translation('ACYM_ADVANCED_ACL').acym_info(['textShownInTooltip' => 'ACYM_ADVANCED_ACL_DESC']); ?>
						</label>
						<div class="cell auto">
							<button type="button" class="button button-secondary" id="acym__configuration__acl__toggle"><?php echo acym_translation('ACYM_SHOW_HIDE'); ?></button>
						</div>
					</div>
				</div>
				<div class="grid-x grid-margin-x" id="acym__configuration__acl__zone">
					<input type="hidden" name="json_acl" id="json_acl" />
                    <?php foreach ($data['acl_advanced'] as $page => $title) { ?>
						<div class="cell grid-x acym__configuration__acl__row">
							<div class="cell large-3 margin-left-1">
                                <?php echo acym_translation($title); ?>
							</div>
							<div class="cell auto">
                                <?php echo $data['aclType']->display($page); ?>
							</div>
						</div>
                    <?php } ?>
				</div>
			</div>
        <?php } ?>
        <?php if ('joomla' === ACYM_CMS) {
            //__START__enterprise_
            if (acym_level(ACYM_ENTERPRISE)) { ?>
				<div class="margin-bottom-2">
					<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_FRONTEND_EDITION'); ?></div>
					<div class="grid-x grid-margin-x grid-margin-y">
						<div class="cell large-3 small-12"><?php echo acym_translation('ACYM_FRONT_DELETE_BUTTON').acym_info(
                                    ['textShownInTooltip' => 'ACYM_FRONT_DELETE_BUTTON_DESC']
                                ); ?></div>
						<div class="cell auto">
                            <?php
                            echo acym_radio(
                                [
                                    'delete' => acym_translation('ACYM_DELETE_THE_SUBSCRIBER'),
                                    'removesub' => acym_translation('ACYM_REMOVE_USER_SUBSCRIPTION'),
                                ],
                                'config[frontend_delete_button]',
                                $this->config->get('frontend_delete_button', 'delete')
                            );
                            ?>
						</div>
					</div>
					<div class="grid-x grid-margin-x grid-margin-y">
						<div class="cell large-3"><?php echo acym_translation('ACYM_FRONT_FILTER_CAMPAIGNS'); ?></div>
						<div class="cell large-9">
                            <?php
                            echo acym_select(
                                [
                                    'own' => acym_translation('ACYM_FRONT_ONLY_CREATED_CAMPAIGNS'),
                                    'allowed' => acym_translation('ACYM_FRONT_SENT_TO_ALLOWED_LISTS'),
                                ],
                                'config[front_campaigns_filter]',
                                $this->config->get('front_campaigns_filter', 'own'),
                                [
                                    'class' => 'acym__select',
                                ]
                            );
                            ?>
						</div>
					</div>
				</div>
                <?php // __END__enterprise_
            }
        } ?>
		<div class="margin-bottom-2">
			<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_FILES'); ?></div>
			<div class="grid-x grid-margin-y margin-y">

				<label class="cell large-3" for="allowed_files">
                    <?php echo acym_translation('ACYM_ALLOWED_FILES').acym_info(['textShownInTooltip' => 'ACYM_ALLOWED_FILES_DESC']); ?>
				</label>
				<input class="cell auto"
					   id="allowed_files"
					   type="text"
					   name="config[allowed_files]"
					   value="<?php echo acym_escape($this->config->get('allowed_files')); ?>" />
			</div>
			<div class="grid-x grid-margin-y margin-y">
				<label class="cell large-3" for="uploadfolder">
                    <?php echo acym_translation('ACYM_UPLOAD_FOLDER').acym_info(['textShownInTooltip' => 'ACYM_UPLOAD_FOLDER_DESC']); ?>
				</label>
				<input class="cell large-9" id="uploadfolder" type="text" name="config[uploadfolder]" value="<?php echo acym_escape($this->config->get('uploadfolder')); ?>" />
			</div>
            <?php
            //__START__joomla_
            if ('joomla' === ACYM_CMS) {
                ?>
				<div class="grid-x grid-margin-y margin-y">
					<label class="cell large-3">
                        <?php echo acym_translation('ACYM_SCAN_SITE_FILES').acym_info(['textShownInTooltip' => 'ACYM_SCAN_SITE_FILES_DESC']); ?>
					</label>

					<div class="cell large-9 grid-x">
						<button type="button" class="cell medium-shrink button button-secondary" id="scanfiles_button">
                            <?php echo acym_translation('ACYM_SCAN_SITE_FILES'); ?>
						</button>
						<div class="cell auto padding-left-1" id="scanfiles_report"></div>
					</div>
				</div>
                <?php
            }
            //__END__joomla_
            ?>
			<div class="cell grid-x grid-margin-y margin-y acym_vcenter">
				<label class="cell large-3" for="php_overrides">
                    <?php echo acym_translation('ACYM_ALLOW_PHP_OVERRIDES').acym_info(['textShownInTooltip' => 'ACYM_ALLOW_PHP_OVERRIDES_DESC']); ?>
				</label>
				<div class="cell grid-x large-9">
                    <?php
                    echo acym_switch(
                        'config[php_overrides]',
                        $this->config->get('php_overrides', 0)
                    );
                    ?>
				</div>
			</div>
		</div>
		<div class="acym__configuration__check-database margin-bottom-2">
			<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_REDIRECTIONS'); ?></div>
			<div class="grid-x grid-margin-x margin-y">
				<div class="cell grid-x acym_vcenter">
					<label class="cell large-3" for="allowed_files">
                        <?php echo acym_translation('ACYM_ALLOWED_HOSTS').acym_info(['textShownInTooltip' => 'ACYM_ALLOWED_HOSTS_DESC']); ?>
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
				<div class="cell grid-x acym_vcenter">
					<label class="cell large-3" for="allowed_files">
                        <?php echo acym_translation('ACYM_ACTIVATE_AUTOLOGIN_URLS').acym_info(['textShownInTooltip' => 'ACYM_ACTIVATE_AUTOLOGIN_URLS_DESC']); ?>
					</label>
					<div class="cell grid-x large-9">
                        <?php
                        echo acym_switch(
                            'config[autologin_urls]',
                            $this->config->get('autologin_urls', 0)
                        );
                        ?>
					</div>
				</div>
			</div>
		</div>
		<div class="margin-bottom-2">
			<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_CRON'); ?></div>
			<div class="grid-x grid-margin-x margin-y">
				<div class="cell grid-x acym_vcenter">
					<label class="cell large-3" for="cron_security">
                        <?php echo acym_translation('ACYM_ADD_SECURITY_KEY_CRON'); ?>
					</label>
					<div class="cell grid-x large-9">
                        <?php
                        echo acym_switch(
                            'config[cron_security]',
                            $this->config->get('cron_security', 0)
                        );
                        ?>
					</div>
				</div>
			</div>
			<div class="grid-x grid-margin-y margin-y">
				<label class="cell large-3" for="cron_key">
                    <?php echo acym_translation('ACYM_CRON_KEY').acym_info(['textShownInTooltip' => 'ACYM_CRON_KEY_DESC']); ?>
				</label>
				<input class="cell large-9"
					   id="cron_key"
					   type="text"
					   name="config[cron_key]"
					   value="<?php echo acym_escape($this->config->get('cron_key', $this->config->get('security_key', ''))); ?>" />
			</div>
		</div>

		<div class="margin-bottom-2">
			<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_URL'); ?></div>
			<div class="grid-x grid-margin-x margin-y">
				<div class="cell grid-x acym_vcenter">
					<label class="cell large-3" for="different_admin_url_toggle">
                        <?php echo acym_translation('ACYM_FRONT_BACK_URL'); ?>
					</label>
					<div class="cell grid-x large-9">
                        <?php
                        echo acym_switch(
                            'config[different_admin_url_toggle]',
                            $this->config->get('different_admin_url_toggle', 0),
                            '',
                            [],
                            '',
                            '',
                            '',
                            'cron_security_config'
                        );
                        ?>
					</div>
				</div>
			</div>
			<div class="grid-x grid-margin-y margin-y" id="cron_security_config">
				<div class="cell grid-x acym_vcenter">
					<label class="cell large-3" for="different_admin_url_value">
                        <?php echo acym_translation('ACYM_BACKEND_URL'); ?>
					</label>
                    <?php $differentUrl = $this->config->get('different_admin_url_value'); ?>
					<input class="cell large-9"
						   id="different_admin_url_value"
						   type="text"
						   name="config[different_admin_url_value]"
						   placeholder="<?php echo acym_escape(rtrim(ACYM_LIVE, '/')); ?>"
						   value="<?php echo acym_escape($differentUrl); ?>" />
				</div>
			</div>
		</div>

		<div class="acym__configuration__check-database margin-bottom-2">
			<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_CONFIGURATION_DB_MAINTENANCE'); ?></div>
			<div class="grid-x grid-margin-x margin-y">
				<div class="cell grid-x">
                    <?php
                    echo acym_tooltip(
                        [
                            'hoveredText' => '<button type="button" class="cell medium-shrink button button-secondary" id="checkdb_button">'.acym_translation(
                                    'ACYM_CHECK_DB'
                                ).'</button>',
                            'textShownInTooltip' => acym_translation('ACYM_INTRO_CHECK_DATABASE'),
                            'classContainer' => 'cell medium-shrink',
                        ]
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

        <?php if (acym_isLogFileErrorExist()) { ?>
			<div class="acym__configuration__log">
				<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_LOG_FILE'); ?></div>
				<div class="grid-x grid-margin-x margin-y">
					<div class="cell grid-x">
						<div class="cell grid-x">
                            <?php
                            echo acym_modal(
                                acym_translation('ACYM_REPORT_SEE'),
                                '',
                                null,
                                [],
                                [
                                    'class' => 'button button-secondary',
                                    'data-ajax' => 'true',
                                    'data-iframe' => '&ctrl=configuration&task=seeLogs&filename='.acym_getErrorLogFilename(),
                                ]
                            );
                            ?>
						</div>
					</div>
				</div>
			</div>
        <?php } ?>
	</div>
</div>
