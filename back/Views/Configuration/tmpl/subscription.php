<div class="acym__configuration__subscription acym__content acym_area padding-vertical-1 padding-horizontal-2">
	<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_SUBSCRIPTION'); ?></div>
	<div class="grid-x margin-y">
		<div class="cell grid-x grid-margin-x">
            <?php echo acym_switch(
                'config[allow_visitor]',
                $this->config->get('allow_visitor'),
                acym_translation('ACYM_ALLOW_VISITOR'),
                [],
                'xlarge-3 medium-5 small-9',
                'auto'
            ); ?>
		</div>
		<div class="cell grid-x grid-margin-x">
            <?php
            echo acym_switch(
                'config[generate_name]',
                $this->config->get('generate_name'),
                acym_translation('ACYM_GENERATE_NAME').acym_info(['textShownInTooltip' => 'ACYM_GENERATE_NAME_DESC']),
                [],
                'xlarge-3 medium-5 small-9',
                'auto'
            );
            ?>
		</div>
		<div class="cell grid-x grid-margin-x">
            <?php
            echo acym_switch(
                'config[require_confirmation]',
                $this->config->get('require_confirmation'),
                acym_translation('ACYM_REQUIRE_CONFIRMATION').acym_info(['textShownInTooltip' => 'ACYM_REQUIRE_CONFIRMATION_DESC']),
                [],
                'xlarge-3 medium-5 small-9',
                'auto',
                '',
                'confirm_config'
            );
            ?>
		</div>
		<div class="cell grid-x" id="confirm_config">
			<div class="cell grid-x">
				<div class="cell xlarge-3 medium-5"></div>
				<div class="cell medium-auto">
                    <?php if (acym_isAllowed('mails')) { ?>
						<a class="button button-secondary margin-bottom-1"
						   href="<?php echo acym_completeLink('mails&task=edit&notification=acy_confirm&return='.urlencode(base64_encode(acym_completeLink('configuration')))); ?>">
                            <?php echo acym_translation('ACYM_EDIT_EMAIL'); ?>
						</a>
                    <?php } ?>
				</div>
			</div>
			<label for="confirm_redirect" class="cell grid-x margin-bottom-1">
				<span class="cell xlarge-3 medium-5 acym_vcenter"><?php echo acym_translation('ACYM_CONFIRMATION_REDIRECTION'); ?></span>
				<input id="confirm_redirect"
					   class="cell xlarge-4 medium-auto margin-bottom-0"
					   type="text"
					   name="config[confirm_redirect]"
					   value="<?php echo acym_escape($this->config->get('confirm_redirect')); ?>">
				<span class="cell large-auto hide-for-large-only hide-for-medium-only"></span>
			</label>
		</div>
		<div class="cell medium-3">
            <?php
            echo acym_translation('ACYM_ALLOW_MODIFICATION_UNAUTH');
            echo '&nbsp;'.acym_info(['textShownInTooltip' => 'ACYM_ALLOW_MODIFICATION_DESC']);
            ?>
		</div>
		<div class="cell medium-9">
            <?php
            $allowModif = [
                'none' => acym_translation('ACYM_NO'),
                'data' => acym_translation('ACYM_ALLOW_ONLY_THEIRS'),
                'all' => acym_translation('ACYM_YES'),
            ];
            echo acym_radio($allowModif, 'config[allow_modif]', $this->config->get('allow_modif', 'data'));
            ?>
		</div>
		<div class="cell grid-x grid-margin-x">
            <?php
            echo acym_switch(
                'config[extra_errors]',
                $this->config->get('extra_errors'),
                acym_translation('ACYM_EXTRA_ERRORS').acym_info(['textShownInTooltip' => 'ACYM_EXTRA_ERRORS_DESC']),
                [],
                'xlarge-3 medium-5 small-9',
                'auto',
                '',
                'extra_errors'
            );
            ?>
		</div>
	</div>
</div>

<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2">
	<div class="cell grid-x acym__configuration__showmore-head">
		<div class="acym__title acym__title__secondary cell auto margin-bottom-0"><?php echo acym_translation('ACYM_CONFIGURATION_ADVANCED'); ?></div>
		<div class="cell shrink">
            <?php echo acym_showMore('acym__configuration__subscription__notifications'); ?>
		</div>
	</div>
	<div id="acym__configuration__subscription__notifications" style="display: none;">
		<div class="cell margin-bottom-2 grid-x margin-y">
			<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_NOTIFICATIONS'); ?></div>
            <?php
            foreach ($data['notifications'] as $identifier => $notification) {
                ?>
				<div class="cell grid-x grid-margin-x">
					<div class="cell xlarge-3">
						<label for="acym__config__<?php echo acym_escape($identifier); ?>">
                            <?php echo acym_escape(acym_translation($notification['label'])); ?>
						</label>
					</div>
					<div class="cell xlarge-4 large-5 medium-6">
                        <?php
                        $saved = explode(',', $this->config->get($identifier));
                        $selected = [];
                        foreach ($saved as $i => $value) {
                            if (acym_isValidEmail($value)) {
                                $selected[$value] = $value;
                            }
                        }

                        echo acym_selectMultiple(
                            $selected,
                            'config['.acym_escape($identifier).']',
                            $selected,
                            [
                                'id' => 'acym__config__'.acym_escape($identifier),
                                'class' => 'acym__multiselect__email',
                            ]
                        );
                        ?>
					</div>
					<div class="cell large-2 medium-4 shrink grid-x">
                        <?php if (acym_isAllowed('mails')) { ?>
							<a class="cell shrink button button-secondary acym__configuration__edit-email"
							   href="<?php echo acym_completeLink(
                                   'mails&task=edit&notification='.$identifier.'&return='.urlencode(base64_encode(acym_completeLink('configuration')))
                               ); ?>">
                                <?php echo acym_translation('ACYM_EDIT_EMAIL'); ?>
							</a>
                        <?php } ?>
					</div>
					<div class="cell xxlarge-2 xlarge-1 hide-for-large-only medium-2 hide-for-small-only"></div>
				</div>
                <?php
            }
            ?>
		</div>
		<div class="cell margin-bottom-2">
			<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_UNSUBSCRIBE_PAGE'); ?></div>
			<div class="grid-x grid-margin-x margin-y">
				<div class="cell grid-x margin-top-1">
                    <?php
                    echo acym_switch(
                        'config[unsubscribe_page]',
                        $this->config->get('unsubscribe_page', 1),
                        acym_translation('ACYM_REDIRECT_ON_UNSUBSCRIBE_PAGE'),
                        [],
                        'xlarge-3 medium-5 small-9',
                        'auto',
                        '',
                        'unsubpage_settings'
                    );
                    ?>
				</div>
				<div class="cell grid-x margin-bottom-1" id="unsubpage_settings">
					<div class="cell grid-x margin-bottom-1">
                        <?php
                        echo acym_switch(
                            'config[unsubscribe_campaign_list_only]',
                            $this->config->get('unsubscribe_campaign_list_only', 0),
                            acym_translation('ACYM_CAMPAIGN_LIST_ONLY'),
                            [],
                            'xlarge-3'
                        );
                        ?>
					</div>
					<div class="cell grid-x margin-bottom-1">
						<label for="acym__configuration__subscription__unsub-title" class="cell xlarge-3"><?php echo acym_translation(
                                'ACYM_UNSUBSCRIBE_PAGE_CHANGE'
                            ); ?></label>
						<input id="acym__configuration__subscription__unsub-title"
							   class="cell xlarge-4 large-5 medium-6"
							   type="text"
							   name="config[unsubscribe_title]"
							   value="<?php echo acym_escape($this->config->get('unsubscribe_title', '')); ?>">
						<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					</div>
					<div class="cell grid-x margin-bottom-1">
						<label for="acym__config__settings__color-picker" class="cell xlarge-3"><?php echo acym_translation(
                                'ACYM_UNSUBSCRIBE_PAGE_COLOR'
                            ); ?></label>
						<p class="cell margin-bottom-1 small-6" id="acym__config__settings__unsub-color">
							<input type="text"
								   name="config[unsubscribe_color]"
								   id="acym__config__settings__color-picker"
								   value="<?php echo acym_escape($this->config->get('unsubscribe_color', '#00a4ff')); ?>" />
						</p>
					</div>
					<div class="cell grid-x margin-bottom-1">
						<label for="acym__configuration__subscription__unsub-logo" class="cell xlarge-3"><?php echo acym_translation(
                                'ACYM_UNSUBSCRIBE_PAGE_LOGO'
                            ); ?></label>
						<button type="button" class="cell shrink button button-secondary margin-bottom-0" id="acym__unsubscribe__logo">
                            <?php echo acym_translation('ACYM_UPLOAD_IMAGE'); ?>
						</button>
						<span class="cell grid-x acym_vcenter shrink acym__unsub__logo__text margin-left-1">
						    <?php if (!empty($this->config->get('unsubscribe_logo'))) { ?>
                                <?php echo $this->config->get('unsubscribe_logo'); ?>
								<i class="acymicon-trash-o acym__color__red acym__unsub__logo__remove margin-left-1"></i>
                            <?php } ?>
						</span>
						<input type="hidden" id="acym__unsubscribe__logo_value" name="config[unsubscribe_logo]" value="<?php echo $this->config->get('unsubscribe_logo'); ?>">
					</div>
					<div class="cell grid-x margin-bottom-1">
                        <?php
                        echo acym_switch(
                            'config[display_unsub_image]',
                            $this->config->get('display_unsub_image', 1),
                            acym_translation('ACYM_DISPLAY_UNSUBSCRIBE_IMAGE'),
                            [],
                            'xlarge-3 medium-5 small-9',
                            'auto',
                            '',
                            'unsub_image'
                        );
                        ?>
					</div>
					<div class="cell grid-x margin-bottom-1" id="unsub_image">
						<label for="acym__configuration__subscription__unsub-image" class="cell xlarge-3"><?php echo acym_translation(
                                'ACYM_UNSUBSCRIBE_PAGE_IMAGE'
                            ); ?></label>
						<button type="button" class="cell shrink button button-secondary margin-bottom-0" id="acym__unsubscribe__image">
                            <?php echo acym_translation('ACYM_UPLOAD_IMAGE'); ?>
						</button>
						<span class="cell shrink grid-x acym_vcenter acym__unsub__image__text margin-left-1">
							<?php if (!empty($this->config->get('unsubscribe_image'))) { ?>
                                <?php echo $this->config->get('unsubscribe_image'); ?>
								<i class="acymicon-trash-o acym__color__red acym__unsub__image__remove margin-left-1"></i>
                            <?php } ?>
						</span>
						<input type="hidden" id="acym__unsubscribe__image_value" name="config[unsubscribe_image]" value="<?php echo $this->config->get('unsubscribe_image'); ?>">
					</div>
					<div class="cell grid-x margin-bottom-1" id="acym__configuration__unsubscription__survey">
                        <?php
                        echo acym_switch(
                            'config[unsubpage_survey]',
                            $this->config->get('unsubpage_survey', 0),
                            acym_translation('ACYM_UNSUBSCRIBE_PAGE_SURVEY'),
                            [],
                            'xlarge-3',
                            'auto',
                            '',
                            'acym__configuration__unsubscription__survey-text'
                        );
                        ?>
					</div>
					<div class="cell grid-x margin-bottom-1" id="acym__configuration__unsubscription__survey-text">
                        <?php
                        if (acym_isMultilingual()) { ?>
							<div class="cell grid-x">
                                <?php
                                if (!empty($data['translation_languages'])) {
                                    echo acym_displayLanguageRadio(
                                        $data['translation_languages'],
                                        'config[unsub_survey_translation]',
                                        $this->config->get('unsub_survey_translation', ''),
                                        acym_translation('ACYM_CUSTOM_SURVEY_LANGUAGE'),
                                        [],
                                        'configuration_subscription'
                                    );
                                } ?>
							</div>
                            <?php
                        } ?>
						<div class="cell grid-x acym__customs__change margin-bottom-1" id="acym__customs__answer">
							<label class="cell large-3 margin-top-1" for="unsubscribe_survey">
                                <?php echo acym_translation('ACYM_CUSTOM_SURVEY').acym_info(['textShownInTooltip' => 'ACYM_CUSTOM_SURVEY_DESC']); ?>
							</label>
							<div class="cell grid-x acym__listing">
								<div class="acym__customs__answers__listing__sortable cell medium-6 grid-x">
                                    <?php if (empty($data['surveyAnswers'])) { ?>
										<div class="grid-x cell acym__customs__answers acym__content acym_noshadow grid-margin-x margin-y">
											<input type="text"
												   name="config[unsub_survey][]"
												   class="cell medium-10 acym__customs__answer__answer"
												   data-response="1"
												   value="">
										</div>
                                    <?php } else {
                                        $i = 0;
                                        foreach ($data['surveyAnswers'] as $answer) { ?>
											<div class="grid-x cell acym__customs__answers acym__content acym_noshadow grid-margin-x margin-y">
												<input type="text"
													   name="config[unsub_survey][]"
													   class="cell medium-10 acym__customs__answer__answer"
													   data-response="<?php echo $i; ?>"
													   value="<?php echo acym_escape($answer); ?>">
                                                <?php
                                                if ($i > 0) { ?>
													<i class=" cell acymicon-close small-1 acym__color__red cursor-pointer acym__custom__delete__value"></i>
                                                <?php } ?>
											</div>
                                            <?php $i++;
                                        } ?>
                                    <?php } ?>
								</div>
							</div>
							<button type="button" class="cell button button-secondary margin-top-1" id="acym__custom_answer__add-answer"><?php echo acym_translation(
                                    'ACYM_ADD_ANSWER'
                                ); ?></button>
						</div>
					</div>
                    <?php if (acym_isMultilingual()) { ?>
						<div class="cell grid-x margin-top-1">
                            <?php
                            echo acym_switch(
                                'config[unsubpage_languages_multi_only]',
                                $this->config->get('unsubpage_languages_multi_only', 0),
                                acym_translation('ACYM_UNSUBSCRIBE_USED_LANGUAGE').acym_info(['textShownInTooltip' => 'ACYM_UNSUBSCRIBE_USED_LANGUAGE_DESC']),
                                [],
                                'xlarge-3 medium-5 small-9'
                            );
                            ?>
						</div>
                    <?php } ?>
					<label for="acym__configuration__subscription__unsub-url" class="cell grid-x margin-bottom-1 margin-top-1">
						<span class="cell xlarge-3 medium-5 acym_vcenter"><?php echo acym_translation('ACYM_REDIRECTION_URL'); ?></span>
						<input id="acym__configuration__subscription__unsub-url"
							   class="cell xlarge-4 medium-auto margin-bottom-0"
							   type="text"
							   name="config[unsub_redirect_url]"
							   value="<?php echo acym_escape($this->config->get('unsub_redirect_url'), ''); ?>">
						<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					</label>
				</div>
                <?php if ('joomla' === ACYM_CMS) {
                    include acym_getPartial('joomla', 'media_modal');
                } ?>
			</div>
		</div>
	</div>
</div>

<!-- CMS Integration -->
<div class="acym__configuration__subscription acym__content acym_area padding-vertical-1 padding-horizontal-2">
	<div class="cell grid-x acym__configuration__showmore-head">
		<div class="acym__title acym__title__secondary cell auto margin-bottom-0">
            <?php echo acym_translationSprintf('ACYM_XX_INTEGRATION', ACYM_CMS_TITLE); ?>
		</div>
		<div class="cell shrink">
            <?php echo acym_showMore('acym__configuration__subscription__integration-cms'); ?>
		</div>
	</div>

    <?php
    if (!acym_isPluginActive('acymtriggers')) {
        acym_display(acym_translationSprintf('ACYM_NEEDS_SYSTEM_PLUGIN', 'AcyMailing - Joomla integration'), 'error', false);
    }
    ?>

	<div id="acym__configuration__subscription__integration-cms" style="display:none;">
		<div class="grid-x margin-y">
			<div class="cell grid-x grid-margin-x">
                <?php
                echo acym_switch(
                    'config[regacy]',
                    $this->config->get('regacy'),
                    acym_translation('ACYM_CREATE_SUBSCRIBER_FOR_CMS_USER'),
                    [],
                    'xlarge-3 medium-5 small-9',
                    'auto',
                    '',
                    'acym__config__regacy'
                );
                ?>
			</div>
			<div class="cell grid-x margin-y" id="acym__config__regacy">
				<div class="cell grid-x grid-margin-x">
                    <?php
                    echo acym_switch(
                        'config[regacy_forceconf]',
                        $this->config->get('regacy_forceconf'),
                        acym_translation('ACYM_SEND_CONF_REGACY'),
                        [],
                        'xlarge-3 medium-5 small-9',
                        'auto',
                        '',
                        'regforceconf_config'
                    );
                    ?>
				</div>
				<div class="cell grid-x grid-margin-x">
                    <?php
                    echo acym_switch(
                        'config[regacy_delete]',
                        $this->config->get('regacy_delete'),
                        acym_translation('ACYM_DELETE_SUBSCRIBER_OF_CMS_USER'),
                        [],
                        'xlarge-3 medium-5 small-9',
                        'auto',
                        '',
                        'regdelete_config'
                    );
                    ?>
				</div>

                <?php acym_trigger('onRegacyUseExternalPlugins', []); ?>

				<div class="cell xlarge-3 medium-5">
					<label for="acym__config__regacy-text" title="<?php echo acym_escape(acym_translation('ACYM_SUBSCRIBE_CAPTION_DESC')); ?>">
                        <?php echo acym_escape(acym_translation('ACYM_SUBSCRIBE_CAPTION')); ?>
					</label>
				</div>
				<div class="cell xlarge-4 medium-7">
					<input type="text" name="config[regacy_text]" id="acym__config__regacy-text" value="<?php echo acym_escape($this->config->get('regacy_text')); ?>" />
				</div>
				<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
				<div class="cell xlarge-3 medium-5">
					<label for="acym__config__regacy-lists">
                        <?php echo acym_translation('ACYM_DISPLAYED_LISTS').acym_info(['textShownInTooltip' => 'ACYM_DISPLAYED_LISTS_DESC']); ?>
					</label>
				</div>
				<div class="cell xlarge-4 medium-7">
                    <?php
                    echo acym_selectMultiple(
                        $data['lists'],
                        'config[regacy_lists]',
                        explode(',', $this->config->get('regacy_lists', '')),
                        ['class' => 'acym__select', 'id' => 'acym__config__regacy-lists'],
                        'id',
                        'name'
                    );
                    ?>
				</div>
				<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>

				<div class="cell xlarge-3 medium-5">
					<label for="acym__config__regacy-checkedlists">
                        <?php echo acym_translation('ACYM_LISTS_CHECKED_DEFAULT').acym_info(['textShownInTooltip' => 'ACYM_LISTS_CHECKED_DEFAULT_DESC']); ?>
					</label>
				</div>
				<div class="cell xlarge-4 medium-7">
                    <?php
                    echo acym_selectMultiple(
                        $data['lists'],
                        'config[regacy_checkedlists]',
                        explode(',', $this->config->get('regacy_checkedlists', '')),
                        ['class' => 'acym__select', 'id' => 'acym__config__regacy-checkedlists'],
                        'id',
                        'name'
                    );
                    ?>
				</div>
				<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>

				<div class="cell xlarge-3 medium-5">
					<label for="acym__config__regacy-autolists">
                        <?php echo acym_translation('ACYM_AUTO_SUBSCRIBE_TO').acym_info(['textShownInTooltip' => 'ACYM_AUTO_SUBSCRIBE_TO_DESC']); ?>
					</label>
				</div>
				<div class="cell xlarge-4 medium-7">
                    <?php
                    echo acym_selectMultiple(
                        $data['lists'],
                        'config[regacy_autolists]',
                        explode(',', $this->config->get('regacy_autolists', '')),
                        ['class' => 'acym__select', 'id' => 'acym__config__regacy-autolists'],
                        'id',
                        'name'
                    );
                    ?>
				</div>
				<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>


                <?php
                if ('joomla' === ACYM_CMS) {
                    ?>
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__regacy-listsposition">
                            <?php echo acym_escape(acym_translation('ACYM_LISTS_POSITION')); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo acym_select(
                            acym_getOptionRegacyPosition(),
                            'config[regacy_listsposition]',
                            $this->config->get('regacy_listsposition', 'password'),
                            [
                                'class' => 'acym__select',
                                'data-toggle-select' => '{"custom":"#acym__config__regacy__custom-list-position"}',
                            ],
                            'value',
                            'text',
                            'acym__config__regacy-listsposition'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>

					<div class="cell grid-x" id="acym__config__regacy__custom-list-position">
						<div class="cell xlarge-3 medium-5"></div>
						<div class="cell xlarge-4 medium-7">
							<input type="text" name="config[regacy_listspositioncustom]" value="<?php echo acym_escape($this->config->get('regacy_listspositioncustom')); ?>" />
						</div>
					</div>
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__regacy-listsposition">
                            <?php
                            echo acym_escape(acym_translation('ACYM_HTML_REGISTRATION_FORM_STRUCTURE'));
                            echo acym_info(['textShownInTooltip' => 'ACYM_HTML_REGISTRATION_FORM_STRUCTURE_DESC']);
                            ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        $customHtmlElement = [
                            acym_selectOption('', 'ACYM_AUTOMATIC'),
                            acym_selectOption('li', 'li'),
                            acym_selectOption('div', 'div'),
                            acym_selectOption('p', 'p'),
                            acym_selectOption('dd', 'dd'),
                        ];

                        echo acym_select(
                            $customHtmlElement,
                            'config[regacy_customhtmlelement]',
                            $this->config->get('regacy_customhtmlelement', ''),
                            ['class' => 'acym__select'],
                            'value',
                            'text',
                            'acym__config__regacy-customhtmlelement'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
                <?php } ?>
			</div>
		</div>
	</div>
</div>

<?php
acym_trigger('onRegacyOptionsDisplay', [$data['lists']]);
