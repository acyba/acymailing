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
                acym_translation('ACYM_GENERATE_NAME').acym_info('ACYM_GENERATE_NAME_DESC'),
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
                acym_translation('ACYM_REQUIRE_CONFIRMATION').acym_info('ACYM_REQUIRE_CONFIRMATION_DESC'),
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
						   href="<?php echo acym_completeLink('mails&task=edit&notification=acy_confirm'); ?>">
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
            echo '&nbsp;'.acym_info('ACYM_ALLOW_MODIFICATION_DESC');
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
	</div>
</div>

<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
	<div class="acym__title acym__title__secondary"><?php echo acym_translation('ACYM_NOTIFICATIONS'); ?></div>
	<div class="grid-x grid-margin-x margin-y">
        <?php
        foreach ($data['notifications'] as $identifier => $notification) {
            ?>
			<div class="cell xxlarge-4 large-5 medium-6">
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
					   href="<?php echo acym_completeLink('mails&task=edit&notification='.$identifier); ?>">
                        <?php echo acym_translation('ACYM_EDIT_EMAIL'); ?>
					</a>
                <?php } ?>
			</div>
			<div class="cell xxlarge-2 xlarge-1 hide-for-large-only medium-8 hide-for-small-only"></div>
            <?php
        }
        ?>
	</div>
</div>

<div class="acym__configuration__subscription acym__content acym_area padding-vertical-1 padding-horizontal-2">
	<div class="acym__title acym__title__secondary"><?php echo acym_translationSprintf('ACYM_XX_INTEGRATION', ACYM_CMS_TITLE); ?></div>

    <?php
    if (!acym_isPluginActive('acymtriggers')) {
        acym_display(acym_translationSprintf('ACYM_NEEDS_SYSTEM_PLUGIN', 'AcyMailing - Joomla integration'), 'error', false);
    }
    ?>

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
                    <?php echo acym_translation('ACYM_DISPLAYED_LISTS').acym_info('ACYM_DISPLAYED_LISTS_DESC'); ?>
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
                    <?php echo acym_translation('ACYM_LISTS_CHECKED_DEFAULT').acym_info('ACYM_LISTS_CHECKED_DEFAULT_DESC'); ?>
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
                    <?php echo acym_translation('ACYM_AUTO_SUBSCRIBE_TO').acym_info('ACYM_AUTO_SUBSCRIBE_TO_DESC'); ?>
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
                        'class="acym__select" data-toggle-select="'.acym_escape('{"custom":"#acym__config__regacy__custom-list-position"}').'"',
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
            <?php } ?>
		</div>
	</div>
</div>

<!-- Integrations -->
<?php
acym_trigger('onRegacyOptionsDisplay', [$data['lists']]);
