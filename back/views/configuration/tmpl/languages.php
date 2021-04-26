<?php if (count($data['languages']) > 1 && acym_level(ACYM_ESSENTIAL)) { ?>
	<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2">
		<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_MULTILINGUAL'); ?></div>
		<div class="grid-x margin-y">
			<div class="cell grid-x grid-margin-x">
                <?php
                echo acym_switch(
                    'config[multilingual]',
                    $this->config->get('multilingual'),
                    acym_translation('ACYM_MULTILINGUAL_EMAILS'),
                    [],
                    'xlarge-3 medium-5 small-9',
                    'auto',
                    '',
                    'multilingual_config'
                );
                ?>
			</div>
			<div class="cell grid-x margin-y" id="multilingual_config">
				<div class="cell grid-x">
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__multilingual-default">
                            <?php echo acym_translation('ACYM_DEFAULT_LANGUAGE').acym_info('ACYM_DEFAULT_LANGUAGE_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        $defaultLanguage = ACYM_DEFAULT_LANGUAGE;
                        if (ACYM_CMS === 'wordpress') $defaultLanguage = strtolower($defaultLanguage);
                        echo acym_select(
                            $data['languages'],
                            'config[multilingual_default]',
                            $this->config->get('multilingual_default', $defaultLanguage),
                            [
                                'class' => 'acym__select',
                            ],
                            'language',
                            'name'
                        );
                        ?>
					</div>
				</div>
				<div class="cell grid-x">
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__multilingual-languages">
                            <?php echo acym_translation('ACYM_LANGUAGES_USED').acym_info('ACYM_LANGUAGES_USED_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        $selectedLanguages = $this->config->get('multilingual_languages');
                        echo acym_selectMultiple(
                            $data['languages'],
                            'config[multilingual_languages]',
                            explode(',', $selectedLanguages),
                            [
                                'class' => 'acym__select',
                                'id' => 'configmultilingual_languages',
                            ],
                            'language',
                            'name'
                        );
                        ?>
						<input type="hidden" name="previous_multilingual_languages" value="<?php echo acym_escape($selectedLanguages); ?>" />
					</div>
				</div>
                <?php if (!empty($data['content_translation'])) { ?>
					<div class="cell grid-x">
						<div class="cell xlarge-3 medium-5">
							<label for="acym__config__multilingual-languages">
                                <?php echo acym_translation('ACYM_TRANSLATE_CONTENT').acym_info('ACYM_TRANSLATE_CONTENT_DESC'); ?>
							</label>
						</div>
						<div class="cell xlarge-4 medium-7">
                            <?php
                            echo acym_select(
                                $data['content_translation'],
                                'config[translate_content]',
                                $this->config->get('translate_content', 'no'),
                                [
                                    'class' => 'acym__select',
                                ],
                                'value',
                                'text',
                                false,
                                true
                            );
                            ?>
						</div>
					</div>
                <?php } ?>
			</div>
		</div>
	</div>
<?php } ?>

<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2" id="acym__configuration__languages">
	<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_TRANSLATIONS'); ?></div>
	<div class="acym__listing margin-top-2">
		<div class="grid-x cell acym__configuration__languages__listing acym__listing__header">
			<div class="grid-x medium-auto small-11 cell">
				<div class="medium-1 small-1 cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_EDIT'); ?>
				</div>
				<div class="medium-auto small-3 cell text-left acym__listing__header__title">
                    <?php echo acym_translation('ACYM_NAME'); ?>
				</div>
				<div class="medium-2 small-2 text-center cell acym__listing__header__title">
                    <?php echo acym_translation('ACYM_ID'); ?>
				</div>
			</div>
		</div>
        <?php foreach ($data['languages'] as $oneLanguage) { ?>
			<div class="grid-x cell acym__listing__row">
				<div class="medium-1 small-1 cell acym__listing__text">
                    <?php echo $oneLanguage->edit; ?>
				</div>
				<div class="medium-auto small-auto cell acym__listing__text">
                    <?php echo $oneLanguage->name; ?>
				</div>
				<div class="medium-2 small-2 cell text-center acym__listing__text">
                    <?php echo $oneLanguage->language; ?>
				</div>
			</div>
        <?php } ?>
	</div>
</div>
