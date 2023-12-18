<input type="hidden" id="acym__config__mail__embed__image__blocked" value="<?php echo acym_escape($data['embedImage']); ?>">
<input type="hidden" id="acym__config__mail__embed__attachment__blocked" value="<?php echo acym_escape($data['embedAttachment']); ?>">
<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2">
	<div class="cell acym__config__mail-default">
		<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_DEFAULT_SENDER'); ?></div>
        <?php
        if (!empty($data['translation_languages'])) {
            echo acym_displayLanguageRadio(
                $data['translation_languages'],
                'config[sender_info_translation]',
                $this->config->get('sender_info_translation', ''),
                acym_translation('ACYM_LANGUAGE_SENDER_INFORMATION_DESC')
            );
        } ?>
		<div class="grid-x grid-margin-x margin-y">
			<div class="cell large-6 xlarge-4">
				<label class="cell grid-x">
					<span class="cell"><?php echo acym_translation('ACYM_FROM_NAME').acym_info('ACYM_FROM_DESC'); ?></span>
					<input type="text"
						   name="config[from_name]"
						   placeholder="<?php echo acym_translation('ACYM_FROM_NAME_PLACEHOLDER'); ?>"
						   value="<?php echo acym_escape($this->config->get('from_name')); ?>" />
				</label>
			</div>
			<div class="cell large-6 xlarge-4">
				<label class="cell grid-x">
					<span class="cell"><?php echo acym_translation('ACYM_FROM_EMAIL').acym_info('ACYM_FROM_DESC'); ?></span>
					<input type="email"
						   name="config[from_email]"
						   placeholder="<?php echo acym_translation('ACYM_FROM_EMAIL_PLACEHOLDER'); ?>"
						   value="<?php echo acym_escape($this->config->get('from_email')); ?>" />
				</label>
			</div>
            <?php if (!empty($data['button_copy_settings_from'])) echo $data['button_copy_settings_from']; ?>
			<div class="cell margin-bottom-1 acym_vcenter">
				<input type="hidden" id="from_as_replyto_value" name="config[from_as_replyto]" value="<?php echo acym_escape($this->config->get('from_as_replyto', 1)); ?>" />
				<input id="from_as_replyto" data-toggle="acy_toggle_replyto" data-value="from_as_replyto_value" class="acym_toggle" type="checkbox" <?php
                if ($this->config->get('from_as_replyto', 1) == 1) {
                    echo 'checked="checked"';
                }
                ?>/>
				<label for="from_as_replyto">
                    <?php echo acym_translation('ACYM_FROM_AS_REPLYTO'); ?>
				</label>
			</div>

			<div class="cell large-6 xlarge-4 acy_toggle_replyto">
				<label class="cell grid-x">
					<span class="cell"><?php echo acym_translation('ACYM_REPLYTO_NAME').acym_info('ACYM_REPLYTO_DESC'); ?></span>
					<input type="text"
						   name="config[replyto_name]"
						   placeholder="<?php echo acym_translation('ACYM_REPLYTO_NAME_PLACEHOLDER'); ?>"
						   value="<?php echo acym_escape($this->config->get('replyto_name')); ?>" />
				</label>
			</div>
			<div class="cell large-6 xlarge-4 acy_toggle_replyto">
				<label class="cell grid-x">
					<span class="cell"><?php echo acym_translation('ACYM_REPLYTO_EMAIL').acym_info('ACYM_REPLYTO_DESC'); ?></span>
					<input type="email"
						   name="config[replyto_email]"
						   placeholder="<?php echo acym_translation('ACYM_REPLYTO_EMAIL_PLACEHOLDER'); ?>"
						   value="<?php echo acym_escape($this->config->get('replyto_email')); ?>" />
				</label>
			</div>

			<div class="cell grid-x">
				<div class="cell medium-6 large-4 xlarge-3 grid-x">
                    <?php echo acym_switch('config[add_names]', $this->config->get('add_names'), acym_translation('ACYM_ADD_NAMES').acym_info('ACYM_ADD_NAMES_DESC')); ?>
				</div>
			</div>

			<div class="cell grid-x">
				<label class="cell large-6 xlarge-4 grid-x">
					<span class="cell"><?php echo acym_translation('ACYM_BOUNCE_EMAIL').acym_info('ACYM_BOUNCE_ADDRESS_DESC'); ?></span>
					<input type="text"
						   name="config[bounce_email]"
						   placeholder="<?php echo acym_translation('ACYM_BOUNCE_EMAIL_PLACEHOLDER'); ?>"
						   value="<?php echo acym_escape($this->config->get('bounce_email')); ?>" />
				</label>
			</div>
		</div>
	</div>

	<div class="cell acym__configuration__mail-settings">
		<div class="acym__title acym__title__secondary margin-top-3"><?php echo acym_translation('ACYM_CONFIGURATION_MAIL').acym_info('ACYM_INTRO_MAIL_SETTINGS'); ?></div>
        <?php include acym_getPartial('configuration', 'sending_methods'); ?>
	</div>
</div>

<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2">
	<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_EMAIL_EDITOR'); ?></div>
	<div class="grid-x margin-y">
		<div class="cell grid-x grid-margin-x">
            <?php
            echo acym_switch(
                'config[save_thumbnail]',
                $this->config->get('save_thumbnail', 1),
                acym_translation('ACYM_SAVE_TEMPLATE_THUMBNAIL').acym_info('ACYM_SAVE_TEMPLATE_THUMBNAIL_DESC'),
                [],
                'xlarge-3 medium-5 small-9'
            );
            ?>
		</div>
		<div class="cell grid-x">
			<label class="cell medium-6 grid-x">
				<span class="cell medium-6">
					<?php echo acym_translation('ACYM_UNSPLASH_ACCESS_KEY').acym_info('ACYM_UNSPLASH_ACCESS_KEY_DESC'); ?>
				</span>
                <?php
                $unsplashKey = $this->config->get('unsplash_key');
                if (strlen($unsplashKey) > 5) {
                    $unsplashKey = str_repeat('*', 10).substr($unsplashKey, -5);
                }
                ?>
				<input type="text"
					   name="config[unsplash_key]"
					   value="<?php echo acym_escape($unsplashKey); ?>"
					   class="cell medium-auto" />
			</label>
		</div>
	</div>
</div>

<div class="acym__content acym_area grid-x grid-margin-y padding-horizontal-2 acym__configuration__advanced">
	<div class="cell grid-x acym__configuration__showmore-head">
		<div class="acym__title acym__title__secondary cell auto margin-bottom-0">
            <?php echo acym_translation('ACYM_CONFIGURATION_ADVANCED'); ?>
		</div>
		<div class="cell shrink">
            <?php echo acym_showMore('acym__configuration__mail__advanced__content'); ?>
		</div>
	</div>
	<div id="acym__configuration__mail__advanced__content" style="display:none;">
		<div class="grid-x grid-margin-x margin-y">
			<div class="cell medium-6 grid-x">
				<div class="cell large-6">
					<label for="config_encoding"><?php echo acym_translation('ACYM_CONFIGURATION_ENCODING'); ?></label>
				</div>
				<div class="cell large-6">
                    <?php
                    $encodingHelper = $data['encodingHelper'];
                    $encodingHelper->encodingField('config[encoding_format]', $this->config->get('encoding_format', '8bit'));
                    ?>
				</div>
			</div>

			<div class="cell medium-6 grid-x">
				<div class="cell large-6">
					<label for="config_charset"><?php echo acym_translation('ACYM_CONFIGURATION_CHARSET'); ?></label>
				</div>
				<div class="cell large-6">
                    <?php
                    echo $encodingHelper->charsetField(
                        'config[charset]',
                        $this->config->get('charset'),
                        'class="acym__select"'
                    );
                    ?>
				</div>
			</div>

            <?php
            $options = [
                'special_chars' => [
                    'label' => 'ACYM_SPECIAL_CHARS',
                ],
                'multiple_part' => [
                    'label' => 'ACYM_CONFIGURATION_MULTIPART',
                ],
                'prevent_hyphens' => [
                    'label' => 'ACYM_PREVENT_HYPHENS',
                ],
                'unsubscribe_header' => [
                    'label' => 'ACYM_ADD_UNSUBSCRIBE_HEADER_IN_MAIL',
                    'default' => 1,
                ],
            ];

            if ($this->config->get('built_by_update', 0) == 1 || acym_level(ACYM_ESSENTIAL)) {
                $options['display_built_by'] = [
                    'label' => 'ACYM_ADD_BUILT_BY_FOOTER',
                ];
            }

            $options['embed_images'] = [
                'label' => 'ACYM_CONFIGURATION_EMBED_IMAGES',
                'info_disabled' => 'ACYM_CONFIGURATION_OPTION_DESC_DISABLED',
            ];

            $options['embed_files'] = [
                'label' => 'ACYM_CONFIGURATION_EMBED_ATTACHMENTS',
                'info_disabled' => 'ACYM_CONFIGURATION_OPTION_DESC_DISABLED',
            ];

            foreach ($options as $oneOption => $option) {
                echo '<div class="cell medium-6 grid-x acym__configuration__mail__option">';
                $label = $option['label'];

                $info = empty($option['info_disabled']) ? '' : '<span class="acym__configuration__mail__info__disabled">'.acym_translation($option['info_disabled']).'</span> ';

                $description = $label.'_DESC';
                $translatedDescription = acym_translation($description);
                $label = acym_translation($label);
                if ($translatedDescription !== $description) {
                    $info .= $translatedDescription;
                }

                if (!empty($info)) {
                    $info = acym_info($info);
                }

                $default = empty($option['default']) ? 0 : $option['default'];

                echo acym_switch(
                    'config['.$oneOption.']',
                    $this->config->get($oneOption, $default),
                    $label.$info
                );

                echo '</div>';
            }

            $style = $this->config->get('embed_files', 0) ? 'style="display:none;"' : '';
            ?>
			<div class="cell medium-6 grid-x" id="attachments_position" <?php echo $style; ?>>
				<div class="cell large-6">
                    <?php echo acym_translation('ACYM_CONFIGURATION_ATTACHMENTS_POSITION').acym_info('ACYM_CONFIGURATION_ATTACHMENTS_POSITION_DESC'); ?>
				</div>
				<div class="cell large-6">
                    <?php
                    echo acym_select(
                        [
                            'top' => acym_translation('ACYM_TOP_OF_EMAIL_CONTENT'),
                            'bottom' => acym_translation('ACYM_BOTTOM_OF_EMAIL_CONTENT'),
                        ],
                        'config[attachments_position]',
                        $this->config->get('attachments_position', 'bottom'),
                        ['class' => 'acym__select']
                    );
                    ?>
				</div>
			</div>
			<div class="cell medium-6 grid-x">
				<label class="cell grid-x">
				<span class="cell medium-6">
					<?php echo acym_translation('ACYM_MAIL_MAX_LINE_LENGTH').acym_info('ACYM_MAIL_MAX_LINE_LENGTH_DESC'); ?>
				</span>
					<input type="number"
						   name="config[mailer_wordwrap]"
						   value="<?php echo acym_escape($this->config->get('mailer_wordwrap', 0)); ?>"
						   min="0"
						   max="998"
						   class="cell medium-auto" />
				</label>
			</div>
			<div class="cell medium-6 grid-x">
                <?php echo acym_switch(
                    'config[dkim]',
                    $this->config->get('dkim'),
                    acym_translation('ACYM_CONFIGURATION_DKIM').acym_info('ACYM_INTRO_DKIM'),
                    [],
                    'medium-6 small-9',
                    'auto',
                    '',
                    'dkim_config'
                ); ?>
			</div>
		</div>


		<div class="cell acym__configuration__dkim padding-vertical-1 margin-y" id="dkim_config">
			<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_DKIM_SETTINGS'); ?></div>
            <?php
            $domain = $this->config->get('dkim_domain', '');
            if (empty($domain)) {
                $domain = preg_replace(['#^https?://(www\.)*#i', '#^www\.#'], '', ACYM_LIVE);
                //we load the first part of the domain, until we get a / (yes, there is always a / at the end as the ACYM_LIVE adds one)
                $domain = substr($domain, 0, strpos($domain, '/'));
            }

            $dkimSelector = $this->config->get('dkim_selector', 'acy');
            if ((!empty($dkimSelector) && $dkimSelector != 'acy') || $this->config->get('dkim_passphrase', '') != '' || acym_getVar('int', 'dkimletme')) {
                ?>
				<div class="grid-x grid-margin-x">
					<div class="cell large-6 grid-x grid-margin-x margin-y">
						<label for="dkim_domain_name" class="cell large-2 medium-3">
                            <?php echo acym_translation('ACYM_DKIM_DOMAIN'); ?>
						</label>
						<div class="cell large-10 medium-9">
							<input id="dkim_domain_name" type="text" name="config[dkim_domain]" value="<?php echo acym_escape($domain); ?>">
						</div>

						<label for="dkim_selector" class="cell large-2 medium-3">
                            <?php echo acym_translation('ACYM_DKIM_SELECTOR'); ?>
						</label>
						<div class="cell large-10 medium-9">
							<input id="dkim_selector" type="text" name="config[dkim_selector]" value="<?php echo acym_escape($this->config->get('dkim_selector', 'acy')); ?>">
						</div>

						<label for="dkim_private" class="cell large-2 medium-3">
                            <?php echo acym_translation('ACYM_DKIM_PRIVATE'); ?>
						</label>
						<div class="cell large-10 medium-9">
							<textarea id="dkim_private" name="config[dkim_private]"><?php echo $this->config->get('dkim_private', ''); ?></textarea>
						</div>
					</div>

					<div class="cell large-6 grid-x grid-margin-x margin-y">
						<label for="dkim_passphrase" class="cell large-2 medium-3">
                            <?php echo acym_translation('ACYM_DKIM_PASSPHRASE'); ?>
						</label>
						<div class="cell large-10 medium-9">
							<input id="dkim_passphrase"
								   type="text"
								   name="config[dkim_passphrase]"
								   value="<?php echo acym_escape($this->config->get('dkim_passphrase', '')); ?>">
						</div>

						<label for="dkim_identity" class="cell large-2 medium-3">
                            <?php echo acym_translation('ACYM_DKIM_IDENTITY'); ?>
						</label>
						<div class="cell large-10 medium-9">
							<input id="dkim_identity" type="text" name="config[dkim_identity]" value="<?php echo acym_escape($this->config->get('dkim_identity', '')); ?>">
						</div>

						<label for="dkim_public" class="cell large-2 medium-3">
                            <?php echo acym_translation('ACYM_DKIM_PUBLIC'); ?>
						</label>
						<div class="cell large-10 medium-9">
							<textarea id="dkim_public" name="config[dkim_public]"><?php echo $this->config->get('dkim_public', ''); ?></textarea>
						</div>
					</div>
				</div>
                <?php
            } else {
                //Be compatible with what we used to have:
                $publicKey = 'v=DKIM1;s=email;t=s;p='.trim($this->config->get('dkim_public', ''), '"');

                echo acym_translationSprintf(
                    'ACYM_DKIM_CONFIGURE',
                    '<input class="margin-bottom-0" type="text" id="dkim_domain" name="config[dkim_domain]" value="'.acym_escape($domain).'" />'
                );
                ?>
				<div class="cell">
                    <?php echo acym_translation('ACYM_DKIM_KEY'); ?>
					<input id="dkim_key" class="acym_autoselect margin-bottom-0" type="text" readonly="readonly" value="acy._domainkey" />
				</div>
				<div class="cell">
                    <?php echo acym_translation('ACYM_DKIM_VALUE'); ?>
					<input id="dkim_value" class="acym_autoselect margin-bottom-0" type="text" readonly="readonly" value="<?php echo acym_escape($publicKey); ?>" />
				</div>
				<div class="cell">
					<input type="checkbox" value="1" id="dkimletme" name="dkimletme" />
					<label for="dkimletme"><?php echo acym_translation('ACYM_DKIM_LET_ME'); ?></label>
				</div>
                <?php
            }
            ?>

			<div class="cell">
				<a class="button button-secondary margin-bottom-0 margin-top-1" target="_blank" href="https://docs.acymailing.com/setup/configuration/mail-configuration/dkim">
                    <?php echo acym_translation('ACYM_HELP'); ?>
				</a>
			</div>
		</div>
	</div>

</div>
