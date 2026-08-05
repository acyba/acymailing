<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
use AcyMailing\Helpers\EntitySelectHelper;

?>
<form id="acym_form" action="<?php echo acym_escapeUrl(acym_prepareAjaxURL(acym_getVar('cmd', 'ctrl'))); ?>" method="post" name="acyForm">
	<div class="grid-x acym__content" id="acym__users__export">
		<div class="cell grid-x grid-margin-x">
			<div class="cell acym_area medium-6 acym__content">
				<div class="acym__title"><?php echo acym_escapeHtml(acym_translation('ACYM_FIELDS_TO_EXPORT')); ?></div>
				<p><span id="acym__users__export__check_all_field"><?php echo acym_escapeHtml(acym_strtolower(acym_translation('ACYM_ALL'))); ?></span> |
					<span id="acym__users__export__check_default_field"><?php echo acym_escapeHtml(acym_strtolower(acym_translation('ACYM_DEFAULT'))); ?></span></p>
				<div class="margin-bottom-1">
                    <?php
                    $defaultFields = explode(',', $this->config->get('export_fields', 'name,email'));
                    foreach ($data['fields'] as $fieldName) {
                        if (in_array($fieldName, ['id', 'automation'])) {
                            continue;
                        }
                        ?>
						<input <?php acym_checked(in_array($fieldName, $defaultFields)); ?>
								id="checkbox_<?php echo acym_escape($fieldName); ?>"
								class="acym__users__export__export_fields"
								type="checkbox"
								name="export_fields[]"
								value="<?php echo acym_escape($fieldName); ?>">
						<label for="checkbox_<?php echo acym_escape($fieldName); ?>"><?php echo acym_escapeHtml($fieldName); ?></label>
						<br />
                        <?php
                    }

                    foreach ($data['customfields'] as $field) {
                        if ($field->type === 'file' || in_array($field->id, $data['coreFields'])) {
                            continue;
                        }

                        $fieldName = $field->name;
                        ?>
						<input <?php acym_checked(in_array($field->id, $defaultFields)); ?>
								id="checkbox_<?php echo acym_escape($fieldName); ?>"
								class="acym__users__export__export_fields"
								type="checkbox"
								name="export_fields[]"
								value="<?php echo acym_escape($field->id); ?>">
						<label for="checkbox_<?php echo acym_escape($fieldName); ?>">
                            <?php echo acym_escapeHtml(acym_translation($fieldName)); ?>
						</label>
						<br />
                        <?php
                    }
                    ?>
					<input <?php acym_checked(in_array('subscribe_date', $defaultFields)); ?>
							id="checkbox_subscribe_date"
							class="acym__users__export__export_fields"
							type="checkbox"
							name="export_fields[]"
							value="subscribe_date">
					<label for="checkbox_subscribe_date">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_SUBSCRIPTION_DATE')); ?>
					</label>
					<br />

					<input <?php acym_checked(in_array('unsubscribe_date', $defaultFields)); ?>
							id="checkbox_unsubscribe_date"
							class="acym__users__export__export_fields"
							type="checkbox"
							name="export_fields[]"
							value="unsubscribe_date">
					<label for="checkbox_unsubscribe_date">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_UNSUBSCRIPTION_DATE')); ?>
					</label>
					<br />
				</div>
				<div class="grid-x margin-bottom-1" id="userField_separator">
					<label class="cell"><?php echo acym_escapeHtml(acym_translation('ACYM_SEPARATOR')); ?></label>
                    <?php
                    acym_radio(
                        ['semicol' => acym_translation('ACYM_SEMICOLON'), 'comma' => acym_translation('ACYM_COMMA')],
                        'export_separator',
                        $this->config->get('export_separator', 'comma')
                    );
                    ?>
					<div class="cell medium-auto"></div>
				</div>
				<div class="grid-x margin-bottom-1">
					<label class="cell medium-6 xxlarge-3"><?php echo acym_escapeHtml(acym_translation('ACYM_ENCODING')); ?>
                        <?php
                        $data['encodingHelper']->charsetField(
                            'export_charset',
                            $this->config->get('export_charset', 'UTF-8'),
                            ['class' => 'acym__select'],
                            true
                        );
                        ?>
					</label>
					<div class="cell medium-auto"></div>
				</div>
				<div class="grid-x" id="userField_excel">
					<label class="cell"><?php echo acym_escapeHtml(acym_translation('ACYM_EXCEL_SECURITY'));
                        acym_info(['textShownInTooltip' => 'ACYM_EXCEL_SECURITY_DESC']); ?></label>
                    <?php
                    acym_boolean(
                        'export_excelsecurity',
                        (bool)$this->config->get('export_excelsecurity', 0)
                    );
                    ?>
					<div class="cell medium-auto"></div>
				</div>
			</div>
			<div class="cell acym_area medium-6 acym__content">
				<div class="acym__title"><?php echo acym_escapeHtml(acym_translation('ACYM_SUBSCRIBERS_TO_EXPORT')); ?></div>
				<p class="cell margin-bottom-1"><?php echo acym_escapeHtml(acym_translation('ACYM_WARNING_FILTERS_APPLIED_EXPORT')); ?></p>
                <?php if (empty($data['checkedElements']) || $data['isPreselectedList']) { ?>
					<fieldset id="acym__users__export__users-to-export">
                        <?php
                        acym_radio(
                            [
                                'all' => acym_translation('ACYM_ALL_SUBSCRIBERS'),
                                'list' => acym_translation('ACYM_SUBSCRIBERS_FROM_LISTS'),
                            ],
                            'export_users-to-export',
                            $data['isPreselectedList'] ? 'list' : 'all'
                        );
                        ?>
					</fieldset>
					<div id="acym__users__export__select_lists" class="margin-bottom-1" style="display: <?php echo $data['isPreselectedList'] ? 'block' : 'none'; ?>">
                        <?php
                        $entityHelper = new EntitySelectHelper();
                        if ($data['isPreselectedList']) {
                            $entityHelper->entitySelect(
                                [
                                    'display' => true,
                                    'entity' => 'list',
                                    'entityParams' => ['join' => 'join_lists-'.implode(',', $data['checkedElements'])],
                                    'columnsToDisplay' => $entityHelper->getColumnsForList('lists.list_id', true),
                                ]
                            );
                        } else {
                            $entityHelper->entitySelect(
                                [
                                    'display' => true,
                                    'entity' => 'list',
                                    'columnsToDisplay' => $entityHelper->getColumnsForList('', true),
                                ]
                            );
                        }
                        ?>
						<div class="margin-top-1">
                            <?php
                            acym_radio(
                                [
                                    'sub' => acym_translation('ACYM_SUBSCRIBED_USER'),
                                    'unsub' => acym_translation('ACYM_UNSUBSCRIBED_USER'),
                                    'all' => acym_translation('ACYM_EXPORT_BOTH'),
                                    'none' => acym_translation('ACYM_NO_SUBSCRIPTION_STATUS'),
                                ],
                                'export_list',
                                $data['exportListStatus']
                            );
                            ?>
						</div>
					</div>
					<div id="acym__users__export__segments" class="padding-top-1">
                        <?php echo acym_escapeHtml(acym_translation('ACYM_FILTER_EXPORTED_SUBSCRIBERS_SEGMENT')); ?>
					</div>
					<div id="acym__users__export__select_segments" class="margin-bottom-1">
						<div class="margin-bottom-1 margin-top-1">
                            <?php
                            acym_select(
                                $data['segments'],
                                'export_segment',
                                $data['preselectedSegment'],
                                [
                                    'class' => 'acym__select',
                                    'acym-data-infinite' => '',
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
                <?php } else { ?>
					<input type="hidden" name="selected_users" value="<?php echo acym_escape(implode(',', $data['checkedElements'])); ?>" />
					<div class="grid-x">
                        <?php
                        if (!$data['isPreselectedList']) {
                            foreach ($data['checkedElements'] as $id) {
                                $user = $data['userClass']->getOneById($id);
                                echo '<div class="cell grid-x acym__listing__row">';
                                echo '    <div class="cell small-6">'.acym_escapeHtml($user->name ?? '').'</div>
                                      <div class="cell small-6">'.acym_escapeHtml($user->email).'</div>';
                                echo '</div>';
                            }
                        }
                        ?>
					</div>
                <?php } ?>
			</div>
		</div>
		<div class="cell grid-x grid-margin-x margin-top-1">
			<div class="cell hide-for-small-only medium-auto"></div>
            <?php
            acym_cancelButton();
            $exportButton = '<button type="button" data-task="doexport" class="cell button acy_button_submit" id="acym__export__button">';
            $exportButton .= acym_translation('ACYM_EXPORT_SUBSCRIBERS');
            $exportButton .= '</button>';
            acym_tooltip(
                [
                    'hoveredText' => $exportButton,
                    'textShownInTooltip' => acym_translation('ACYM_DATA_WILL_EXPORT_CSV_FORMAT'),
                ]
            );
            ?>
			<div class="cell hide-for-small-only medium-auto"></div>
		</div>
	</div>

    <?php acym_formOptions(); ?>
</form>
