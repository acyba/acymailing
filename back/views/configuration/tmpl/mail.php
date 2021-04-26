<input type="hidden" id="acym__config__mail__embed__image__blocked" value="<?php echo acym_escape($data['embedImage']); ?>">
<input type="hidden" id="acym__config__mail__embed__attachment__blocked" value="<?php echo acym_escape($data['embedAttachment']); ?>">
<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
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

<div class="acym__configuration__mail-settings acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
	<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_CONFIGURATION_MAIL').acym_info('ACYM_INTRO_MAIL_SETTINGS'); ?></div>
    <?php include acym_getPartial('configuration', 'sending_methods'); ?>
</div>

<div class="acym__configuration__advanced acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
	<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_CONFIGURATION_ADVANCED'); ?></div>
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
            'embed_images' => [
                'label' => 'ACYM_CONFIGURATION_EMBED_IMAGES',
                'info_disabled' => 'ACYM_CONFIGURATION_OPTION_DESC_DISABLED',
            ],
            'embed_files' => [
                'label' => 'ACYM_CONFIGURATION_EMBED_ATTACHMENTS',
                'info_disabled' => 'ACYM_CONFIGURATION_OPTION_DESC_DISABLED',
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
        ?>
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
</div>

<div class="acym__configuration__dkim acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-y" id="dkim_config">
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
					<input id="dkim_passphrase" type="text" name="config[dkim_passphrase]" value="<?php echo acym_escape($this->config->get('dkim_passphrase', '')); ?>">
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
        if ($this->config->get('dkim', 0) == 1 && ($this->config->get('dkim_private', '') == '' || $this->config->get('dkim_public', '') == '')) {
            //We need to load our JS file to load a new key
            //So we also display the private hidden field there...
            echo acym_translation('ACYM_DKIM_SAVE');
            acym_addScript(false, ACYM_UPDATEMEURL.'generatedkim');
            ?>
			<input type="hidden" id="dkim_private" name="config[dkim_private]" />
			<input type="hidden" id="dkim_public" name="config[dkim_public]" />

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
    }
    ?>

	<div class="cell">
		<a class="button button-secondary margin-bottom-0 margin-top-1" target="_blank" href="<?php echo ACYM_HELPURL; ?>dkim">
            <?php echo acym_translation('ACYM_HELP'); ?>
		</a>
	</div>
</div>
