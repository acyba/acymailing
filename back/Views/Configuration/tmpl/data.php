<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<div class="acym__content acym_area padding-vertical-1 padding-horizontal-2 margin-bottom-2">
	<div class="cell margin-bottom-2">
		<div class="acym__title acym__title__secondary"><?php echo acym_escapeHtml(acym_translation('ACYM_CONFIDENTIALITY')); ?></div>
		<div class="grid-x grid-margin-x">
            <?php acym_switch([
                'name' => 'config[gdpr_export]',
                'value' => $this->config->get('gdpr_export'),
                'label' => acym_translation('ACYM_GDPR_EXPORT_BUTTON'),
                'labelClass' => 'xlarge-3 medium-5 small-9',
                'toggle' => 'export_config',
            ]); ?>
		</div>
		<div class="grid-x grid-margin-x margin-top-1">
            <?php acym_switch([
                'name' => 'config[gdpr_delete]',
                'value' => $this->config->get('gdpr_delete'),
                'label' => acym_translation('ACYM_GDPR_DELETE_BUTTON'),
                'labelClass' => 'xlarge-3 medium-5 small-9',
            ]); ?>
		</div>
		<div class="grid-x grid-margin-x margin-top-1">
            <?php acym_switch([
                'name' => 'config[dont_track_by_default]',
                'value' => $this->config->get('dont_track_by_default', 0),
                'label' => acym_translation('ACYM_DONT_TRACK_BY_DEFAULT'),
                'labelClass' => 'xlarge-3 medium-5 small-9',
                'tip' => ['textShownInTooltip' => 'ACYM_DONT_TRACK_BY_DEFAULT_DESC'],
            ]); ?>
		</div>
		<div class="grid-x grid-margin-x margin-top-1">
            <?php acym_switch([
                'name' => 'config[user_tracking_control]',
                'value' => $this->config->get('user_tracking_control', 0),
                'label' => acym_translation('ACYM_USER_TRACKING_CONTROL'),
                'labelClass' => 'xlarge-3 medium-5 small-9',
            ]); ?>
		</div>
		<div class="grid-x grid-margin-x margin-top-1">
			<label class="cell large-3" for="security_key">
                <?php echo acym_escapeHtml(acym_translation('ACYM_IP_COLLECTION'));
                acym_info(['textShownInTooltip' => 'ACYM_IP_COLLECTION_DESC']); ?>
			</label>
			<div class="cell large-9">
				<div>
                    <?php
                    acym_select(
                        [
                            'yes' => acym_translation('ACYM_YES'),
                            'anonymised' => acym_translation('ACYM_ANONYMISED'),
                            'no' => acym_translation('ACYM_NO'),
                        ],
                        'config[ip_collection]',
                        $this->config->get('ip_collection', 'yes'),
                        [
                            'class' => 'acym__select intext_select',
                        ],
                        'value',
                        'text',
                        null,
                        false,
                        true
                    );
                    ?>
				</div>
			</div>
		</div>
	</div>

	<div class="cell margin-bottom-2">
		<div class="acym__title acym__title__secondary"><?php echo acym_escapeHtml(acym_translation('ACYM_TRACKING')); ?></div>

		<div class="grid-x grid-margin-x margin-y">
			<label class="cell xlarge-3 small-5" for="from_as_replyto">
                <?php echo acym_escapeHtml(acym_translation('ACYM_TRACKINGSYSTEM')); ?>
			</label>
			<div class="cell xlarge-9 small-7 acym_vcenter">
                <?php $trackingMode = $this->config->get('trackingsystem', 'acymailing'); ?>

				<input
						type="checkbox"
						name="config[trackingsystem][]"
						id="trackingsystem[0]"
						value="acymailing"
                    <?php acym_checked(stripos($trackingMode, 'acymailing') !== false); ?>
				/>
				<label for="trackingsystem[0]">AcyMailing<?php acym_info(['textShownInTooltip' => 'ACYM_TRACKINGSYSTEM_ACY_DESC']); ?></label>

				<input
						type="checkbox"
						name="config[trackingsystem][]"
						id="trackingsystem[1]"
						value="google"
                    <?php acym_checked(stripos($trackingMode, 'google') !== false); ?>
				/>
				<label for="trackingsystem[1]">Google Analytics<?php acym_info(['textShownInTooltip' => 'ACYM_TRACKINGSYSTEM_GA_DESC']); ?></label>

				<input type="hidden" name="config[trackingsystem][]" value="1" />
			</div>
		</div>

		<div class="grid-x grid-margin-x margin-y margin-top-1">
            <?php
            acym_switch([
                'name' => 'config[trackingsystemexternalwebsite]',
                'value' => $this->config->get('trackingsystemexternalwebsite'),
                'label' => acym_translation('ACYM_TRACKINGSYSTEM_EXTERNAL_LINKS'),
                'tip' => ['textShownInTooltip' => 'ACYM_TRACKINGSYSTEM_EXTERNAL_LINKS_DESC'],
                'labelClass' => 'xlarge-3 medium-5 small-9',
                'toggle' => 'external_config',
            ]);
            ?>
		</div>
		<div class="cell grid-x margin-y margin-top-1">
			<div class="cell medium-3">
				<label for="tracking_delay">
                    <?php
                    echo acym_escapeHtml(acym_translation('ACYM_TRACKING_DELAY'));
                    acym_info(
                        [
                            'textShownInTooltip' => 'ACYM_TRACKING_DELAY_DESC',
                        ]
                    );
                    ?>
				</label>
			</div>
			<div class="cell medium-9 grid-x">
				<div class="cell medium-6 large-4 xlarge-3 xxlarge-2">
					<input
							id="tracking_delay"
							type="number"
							class="intext_input"
							min="0"
							name="config[tracking_delay]"
							value="<?php echo intval($this->config->get('tracking_delay', 0)); ?>"
					/>
				</div>
			</div>
		</div>
	</div>
	<div class="cell margin-bottom-2">
		<div class="acym__title acym__title__secondary"><?php echo acym_escapeHtml(acym_translation('ACYM_DATA_MANAGEMENT')); ?></div>
        <?php if (acym_level(ACYM_ESSENTIAL)) { ?>
			<div class="grid-x grid-margin-x margin-y">
                <?php
                acym_switch([
                    'name' => 'config[delete_stats_enabled]',
                    'value' => $this->config->get('delete_stats_enabled', 0),
                    'label' => acym_translation('ACYM_DELETE_DETAILED_STATS_AFTER'),
                    'tip' => ['textShownInTooltip' => 'ACYM_DELETE_DETAILED_STATS_AFTER_DESC'],
                    'labelClass' => 'xlarge-3 medium-5 small-9',
                    'toggle' => 'delete_stats_enabled',
                ]); ?>
				<div class="cell grid-x" id="delete_stats_enabled">
                    <?php
                    $delayTypeAuto = $data['typeDelay'];
                    $delayTypeAuto->display('config[delete_stats]', $this->config->get('delete_stats', 86400 * 360), \AcyMailing\Types\DelayType::TYPE_WEEKS_MONTHS);
                    ?>
				</div>
			</div>
        <?php } ?>
		<div class="grid-x grid-margin-x margin-y margin-top-1">
            <?php
            acym_switch([
                'name' => 'config[delete_user_history_enabled]',
                'value' => $this->config->get('delete_user_history_enabled', 0),
                'label' => acym_translation('ACYM_DELETE_USER_HISTORY_AFTER'),
                'labelClass' => 'xlarge-3 medium-5 small-9',
                'toggle' => 'delete_user_history_enabled',
            ]); ?>
			<div class="cell grid-x" id="delete_user_history_enabled">
                <?php
                $delayTypeAuto = $data['typeDelay'];
                $delayTypeAuto->display('config[delete_user_history]', $this->config->get('delete_user_history', 0), \AcyMailing\Types\DelayType::TYPE_WEEKS_MONTHS);
                ?>
			</div>
		</div>
		<div class="grid-x grid-margin-x margin-y margin-top-1">
            <?php
            acym_switch([
                'name' => 'config[delete_archive_history_enabled]',
                'value' => $this->config->get('delete_archive_history_enabled', 1),
                'label' => acym_translation('ACYM_DELETE_ARCHIVE_HISTORY_AFTER'),
                'labelClass' => 'xlarge-3 medium-5 small-9',
                'toggle' => 'delete_archive_history_enabled',
            ]); ?>
			<div class="cell grid-x" id="delete_archive_history_enabled">
                <?php
                $delayTypeAuto = $data['typeDelay'];
                $delayTypeAuto->display(
                    'config[delete_archive_history_after]',
                    $this->config->get('delete_archive_history_after', 86400 * 90),
                    \AcyMailing\Types\DelayType::TYPE_WEEKS_MONTHS
                );
                ?>
			</div>
		</div>
	</div>
	<div class="cell margin-bottom-2">
		<div class="acym__title acym__title__secondary"><?php echo acym_escapeHtml(acym_translation('ACYM_USER_DATA_MANAGEMENT')); ?></div>
		<div class="grid-x grid-margin-x margin-y">
            <?php
            acym_switch([
                'name' => 'config[export_data_changes]',
                'value' => $this->config->get('export_data_changes', 0),
                'label' => acym_translation('ACYM_EXPORT_DATA_CHANGES'),
                'tip' => ['textShownInTooltip' => 'ACYM_EXPORT_DATA_CHANGES_DESC'],
                'labelClass' => 'xlarge-3 medium-5 small-9',
                'toggle' => 'export_data_changes',
            ]); ?>
			<div id="export_data_changes" class="cell grid-x margin-top-1">
				<div class="cell grid-x">
					<label class="xlarge-3 medium-5 small-9"><?php echo acym_escapeHtml(acym_translation('ACYM_SELECT_FIELDS_TO_EXPORT')); ?></label>
					<div class="cell xlarge-3 medium-5 small-9">
                        <?php
                        $exportDataFields = [];
                        foreach ($data['fields'] as $oneExportField) {
                            $exportDataFields[$oneExportField->id] = acym_translation($oneExportField->name);
                        }
                        acym_selectMultiple(
                            $exportDataFields,
                            'config[export_data_changes_fields]',
                            $data['export_data_changes_fields'],
                            ['class' => 'acym__select'],
                            'value',
                            'text',
                            true
                        );
                        ?>
					</div>
				</div>
				<div class="cell grid-x margin-top-1">
					<div class="cell xlarge-3 medium-5 small-9 grid-x">
						<button type="button" class="button button-secondary acy_button_submit cell shrink" data-task="downloadExportChangesFile">
                            <?php echo acym_escapeHtml(acym_translation('ACYM_EXPORT')); ?>
						</button>
					</div>
					<div class="cell xlarge-3 medium-5 small-9">
                        <?php
                        $choices = [1 => acym_translation('ACYM_CURRENT_MONTH'), 0 => acym_translation('ACYM_PREVIOUS_MONTH')];
                        acym_select($choices, 'export_changes_file_current', 1, ['class' => 'acym__select'], 'value', 'text', null, false, true);
                        ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="cell margin-bottom-2">
		<div class="acym__title acym__title__secondary">
            <?php echo acym_escapeHtml(acym_translation('ACYM_DATA_EXPORT_MANAGEMENT')); ?>
		</div>

		<div class="grid-x grid-margin-x margin-y">
			<label class="cell xlarge-3 medium-5 small-9">
                <?php
                echo acym_escapeHtml(acym_translation('ACYM_CSV_SEPARATOR_CHOICE'));
                acym_info(
                    [
                        'textShownInTooltip' => acym_translation('ACYM_CSV_SEPARATOR_CHOICE_DESC'),
                    ]
                );
                ?>
			</label>
			<div class="cell xlarge-9 medium-7 small-12 acym_vcenter">
				<label class="margin-right-1">
					<input type="radio"
					       name="config[csv_separator]"
					       value=";"
                        <?php acym_checked($this->config->get('csv_separator', ',') === ';'); ?> />
					;
				</label>

				<label>
					<input type="radio"
					       name="config[csv_separator]"
					       value=","
                        <?php acym_checked($this->config->get('csv_separator', ',') === ','); ?> />
					,
				</label>
			</div>
		</div>
	</div>
</div>
