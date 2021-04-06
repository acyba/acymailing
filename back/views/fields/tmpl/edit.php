<?php
$beforeSave = '';
if (!empty($data['translation_languages'])) {
    $beforeSave = 'acym-data-before="acym_helperSelectionMultilingual.changeLanguage_field(acym_helperSelectionMultilingual.mainLanguage)"';
}
?>
<form id="acym_form" action="<?php echo acym_completeLink(acym_getVar('cmd', 'ctrl')); ?>" method="post" name="acyForm" data-abide novalidate>
	<input type="hidden" name="id" value="<?php echo empty($data['field']->id) ? '' : intval($data['field']->id); ?>">
	<input type="hidden" name="namekey" value="<?php echo empty($data['field']->namekey) ? '' : acym_escape($data['field']->namekey); ?>">
	<div id="acym__fields__edit" class="acym__content grid-x cell">
		<div class="cell grid-x text-right grid-margin-x margin-left-0 margin-right-0 margin-y margin-bottom-0">
			<h5 class="cell medium-auto medium-text-left text-center hide-for-small-only hide-for-medium-only acym__title">
                <?php echo acym_translation('ACYM_CUSTOM_FIELD'); ?>
			</h5>
			<div class="cell auto hide-for-small-only hide-for-medium-only"></div>
            <?php echo acym_cancelButton(); ?>
			<button data-task="apply" <?php echo $beforeSave; ?> class="cell button button-secondary medium-6 large-shrink acy_button_submit"><?php echo acym_translation(
                    'ACYM_SAVE'
                ); ?></button>
			<button data-task="save" <?php echo $beforeSave; ?> class="cell button medium-6 large-shrink margin-right-0 acy_button_submit"><?php echo acym_translation(
                    'ACYM_SAVE_EXIT'
                ); ?></button>
		</div>
		<div class="cell grid-x grid-margin-x">
			<div class="xlarge-4 cell grid-x acym__fields__edit__field__general acym__content grid-margin-x margin-bottom-1 acym_center_baseline">
				<h2 class="cell acym__title acym__title__secondary"><?php echo acym_translation('ACYM_INFORMATION'); ?></h2>
                <?php
                if (!empty($data['translation_languages'])) {
                    echo acym_displayLanguageRadio(
                        $data['translation_languages'],
                        'field[translation]',
                        empty($data['field']->translation) ? '' : $data['field']->translation,
                        acym_translation('ACYM_LANGUAGE_CUSTOM_FIELD_DESC')
                    );
                } ?>
				<label class="cell xlarge-12 large-6 margin-top-1">
                    <?php echo acym_translation('ACYM_NAME'); ?>
					<input required type="text" name="field[name]" value="<?php echo empty($data['field']->name) ? '' : acym_escape($data['field']->name); ?>">
				</label>
				<label class="cell xlarge-12 large-6 margin-top-1">
                    <?php echo acym_translation('ACYM_FIELD_TYPE'); ?>
                    <?php
                    echo acym_select(
                        $data['fieldType'],
                        'field[type]',
                        $data['field']->type,
                        'acym-data-infinite class="acym__fields__edit__select acym__select"'.((!empty($data['field']->id) && in_array(
                                $data['field']->id,
                                [1, 2, $data['languageFieldId']]
                            )) ? 'disabled' : ''),
                        'value',
                        'name'
                    );
                    ?>
				</label>

				<div class="cell large-12 grid-x margin-top-1">
                    <?php

                    $disableActiveSwitch = in_array($data['field']->id, [2, $data['languageFieldId']]);

                    echo acym_switch(
                        'field[active]',
                        $data['field']->active,
                        acym_translation('ACYM_ACTIVE'),
                        [],
                        'auto',
                        'shrink',
                        'margin-0',
                        null,
                        true,
                        '',
                        $disableActiveSwitch
                    ); ?>
				</div>

                <?php if (empty($data['field']->id) || !in_array($data['field']->id, [2, $data['languageFieldId']])) { ?>
					<div class="cell large-12 grid-x acym__fields__change margin-top-1" id="acym__fields__required">
                        <?php echo acym_switch(
                            'field[required]',
                            $data['field']->required,
                            acym_translation('ACYM_REQUIRED').acym_info('ACYM_REQUIRED_DESC'),
                            [],
                            'auto',
                            'shrink',
                            'margin-0',
                            'required_error_message'
                        ); ?>
					</div>
                <?php } ?>

				<h2 class="cell acym__title acym__title__secondary margin-top-2"><?php echo acym_translation('ACYM_DISPLAY'); ?></h2>

				<div class="cell grid-x large-12 margin-top-1">
                    <?php echo acym_switch(
                        'field[backend_edition]',
                        $data['field']->backend_edition,
                        acym_translationSprintf('ACYM_BACKEND_X', acym_translation('ACYM_EDITION')),
                        [],
                        'auto',
                        'shrink',
                        'margin-0'
                    ); ?>
				</div>

				<div class="cell grid-x large-12 margin-top-1">
                    <?php echo acym_switch(
                        'field[backend_listing]',
                        $data['field']->backend_listing,
                        acym_translationSprintf('ACYM_BACKEND_X', acym_translation('ACYM_LISTING')),
                        [],
                        'auto',
                        'shrink',
                        'margin-0'
                    ); ?>
				</div>

                <?php if ('joomla' === ACYM_CMS) { ?>
					<div class="cell grid-x large-12 margin-top-1">
                        <?php echo acym_switch(
                            'field[frontend_edition]',
                            $data['field']->frontend_edition,
                            acym_translationSprintf('ACYM_FRONTEND_X', acym_translation('ACYM_EDITION')),
                            [],
                            'auto',
                            'shrink',
                            'margin-0'
                        ); ?>
					</div>
					<div class="cell grid-x large-12 margin-top-1">
                        <?php echo acym_switch(
                            'field[frontend_listing]',
                            $data['field']->frontend_listing,
                            acym_translationSprintf('ACYM_FRONTEND_X', acym_translation('ACYM_LISTING')),
                            [],
                            'auto',
                            'shrink',
                            'margin-0'
                        ); ?>
					</div>
                <?php } ?>
			</div>

			<div class="cell xlarge-8 acym__content grid-x grid-margin-x acym__fields__edit__properties<?php echo in_array(
                $data['field']->id,
                [1, 2]
            ) ? ' is-hidden' : ''; ?> acym_center_baseline">
				<h2 class="cell acym__title acym__title__secondary acym__fields__edit__section__title" id="acym__fields__edit__section__title--properties">
                    <?php echo acym_translation('ACYM_FIELD_PROPERTIES'); ?>
				</h2>

				<!--It's in general like the user didn't fill the field we display that message-->
				<div class="cell xlarge-6 grid-x acym__fields__change margin-top-1" id="acym__fields__editable-user-creation">
                    <?php echo acym_switch(
                        'field[option][editable_user_creation]',
                        empty($data['field']->option) ? 1 : $data['field']->option->editable_user_creation,
                        acym_translation('ACYM_EDITABLE_SUBSCRIBER_CREATION'),
                        [],
                        'auto',
                        'shrink',
                        'margin-0'
                    ); ?>
				</div>

				<div class="cell xlarge-6 hide-for-large-only hide-for-medium-only hide-for-small-only"></div>

				<div class="cell xlarge-6 grid-x acym__fields__change margin-top-1" id="acym__fields__editable-user-modification">
                    <?php echo acym_switch(
                        'field[option][editable_user_modification]',
                        empty($data['field']->option) ? 1 : $data['field']->option->editable_user_modification,
                        acym_translation('ACYM_EDITABLE_SUBSCRIBER_MODIFICATION'),
                        [],
                        'auto',
                        'shrink',
                        'margin-0'
                    ); ?>
				</div>

				<div class="cell xlarge-6 hide-for-large-only hide-for-medium-only hide-for-small-only"></div>

                <?php if (empty($data['field']->id) || $data['field']->id != 2) { ?>
					<div class="cell margin-top-1 large-11" id="required_error_message">
						<label class="acym__fields__change" id="acym__fields__error-message"><?php echo acym_translation('ACYM_CUSTOM_ERROR'); ?>
							<input type="text"
								   name="field[option][error_message]"
								   value="<?php echo empty($data['field']->option->error_message) ? '' : acym_escape($data['field']->option->error_message); ?>"
								   placeholder="<?php echo acym_escape(acym_translationSprintf('ACYM_DEFAULT_REQUIRED_MESSAGE', 'xxx')); ?>">
						</label>
					</div>
                <?php } ?>

				<h2 class="cell acym__title acym__title__secondary margin-top-2 acym__fields__edit__section__title" id="acym__fields__edit__section__title--content">
                    <?php echo acym_translation('ACYM_FIELD_CONTENT'); ?>
				</h2>

				<div class="cell margin-top-1 acym__fields__change" id="acym__fields__authorized-content"><?php echo acym_translation('ACYM_AUTHORIZED_CONTENT'); ?>
                    <?php
                    $regex = empty($data['field']->option->authorized_content->regex) ? '' : acym_escape($data['field']->option->authorized_content->regex);
                    $authorizedContent = [
                        'all' => acym_translation('ACYM_ALL'),
                        'number' => acym_translation('ACYM_NUMBER_ONLY'),
                        'letters' => acym_translation('ACYM_LETTERS_ONLY'),
                        'numbers_letters' => acym_translation('ACYM_NUMBERS_LETTERS_ONLY'),
                        'regex' => ' <input type="text" value="'.$regex.'" name="field[option][authorized_content][regex]" placeholder="'.acym_translation(
                                'ACYM_REGULAR_EXPRESSION',
                                true
                            ).'">',
                    ];
                    echo acym_radio(
                        $authorizedContent,
                        'field[option][authorized_content][]',
                        empty($data['field']->option->authorized_content->{'0'}) ? 'all' : $data['field']->option->authorized_content->{'0'}
                    );
                    ?>
				</div>

				<!--if the user didn't respect the authorized content then we display the message below-->
				<label class="cell margin-top-1 large-6 acym__fields__change" id="acym__fields__error-message-invalid">
                    <?php echo acym_translation('ACYM_ERROR_MESSAGE_INVALID_CONTENT'); ?>
					<input type="text"
						   name="field[option][error_message_invalid]"
						   value="<?php echo empty($data['field']->option->error_message_invalid) ? '' : $data['field']->option->error_message_invalid; ?>">
				</label>

				<label class="cell margin-top-1 large-6 acym__fields__change" id="acym__fields__default-value"><?php echo acym_translation('ACYM_DEFAULT_VALUE'); ?>
					<input type="text" name="field[default_value]" value="<?php echo isset($data['field']->default_value) ? $data['field']->default_value : ''; ?>">
				</label>

				<label class="cell margin-top-1 large-6 acym__fields__change" id="acym__fields__format">
                    <?php
                    echo '<h6>';
                    echo acym_translation('ACYM_FORMAT');
                    echo acym_info(
                        acym_translationSprintf(
                            'ACYM_X_TO_ENTER_X',
                            '%d',
                            acym_translation('ACYM_DAY')
                        ).'<br>'.acym_translationSprintf(
                            'ACYM_X_TO_ENTER_X',
                            '%m',
                            acym_translation('ACYM_MONTH')
                        ).'<br>'.acym_translationSprintf(
                            'ACYM_X_TO_ENTER_X',
                            '%y',
                            acym_translation('ACYM_YEAR')
                        ).'<br>'.acym_translation('ACYM_EXEMPLE_FORMAT')
                    );
                    echo '</h6>';
                    ?>
					<input type="text" name="field[option][format]" value="<?php echo empty($data['field']->option->format) ? '%d%m%y' : $data['field']->option->format; ?>">
				</label>

				<label class="cell margin-top-1 large-6 acym__fields__change grid-x" id="acym__fields__max_characters">
					<span class="cell">
                    <?php
                    echo acym_translation('ACYM_MAXIMUM_CHARACTERS');
                    echo acym_info('ACYM_MAXIMUM_CHARACTERS_TOOLTIP');
                    ?>
					</span>
					<input type="number"
						   min="0"
						   name="field[option][max_characters]"
						   value="<?php echo empty($data['field']->option->max_characters) ? '' : $data['field']->option->max_characters; ?>"
						   class="cell medium-2 small-3">
				</label>

				<h2 class="cell acym__title acym__title__secondary margin-top-2 acym__fields__edit__section__title" id="acym__fields__edit__section__title--style">
                    <?php echo acym_translation('ACYM_FIELD_STYLE'); ?>
				</h2>

				<label class="cell margin-top-1 large-6 acym__fields__change" id="acym__fields__rows"><?php echo acym_translation('ACYM_ROWS'); ?>
					<input type="text" name="field[option][rows]" value="<?php echo empty($data['field']->option->rows) ? '' : $data['field']->option->rows; ?>">
				</label>

				<label class="cell margin-top-1 large-6 acym__fields__change" id="acym__fields__columns"><?php echo acym_translation('ACYM_COLUMNS'); ?>
					<input type="text" name="field[option][columns]" value="<?php echo empty($data['field']->option->columns) ? '' : $data['field']->option->columns; ?>">
				</label>

				<label class="cell margin-top-1 large-6 acym__fields__change grid-x" id="acym__fields__size">
					<span class="cell"><?php echo acym_translation('ACYM_INPUT_WIDTH'); ?></span>
					<input type="text"
						   name="field[option][size]"
						   value="<?php echo empty($data['field']->option->size) ? '' : $data['field']->option->size; ?>"
						   class="cell medium-4">
				</label>

				<h2 class="cell acym__title acym__title__secondary margin-top-2 acym__fields__edit__section__title" id="acym__fields__edit__section__title--values">
                    <?php echo acym_translation('ACYM_FIELD_VALUES'); ?>
				</h2>

				<label class="cell margin-top-1 large-11 acym__fields__change" id="acym__fields__custom-text"><?php echo acym_translation('ACYM_CUSTOM_TEXT'); ?>
					<textarea
							name="field[option][custom_text]"
							cols="30"
							rows="10"><?php echo empty($data['field']->option->custom_text) ? '' : $data['field']->option->custom_text; ?></textarea>
				</label>

				<div class="cell grid-x acym__fields__change margin-bottom-2" id="acym__fields__value">
					<div class="cell grid-x acym__listing">
						<div class="grid-x cell acym__listing__fields__header">
							<div class="medium-1 cell acym__listing__header__title"></div>
							<div class="medium-4 cell acym__listing__header__title text-center">
                                <?php echo acym_translation('ACYM_VALUE'); ?>
							</div>
							<div class="medium-4 cell acym__listing__header__title text-center">
                                <?php echo acym_translation('ACYM_TITLE'); ?>
							</div>
							<div class="medium-2 cell acym__listing__header__title text-center">
                                <?php echo acym_translation('ACYM_DISABLE'); ?>
							</div>
							<div class="small-1 cell acym__listing__header__title"></div>
						</div>
						<div class="acym__fields__values__listing__sortable cell grid-x">
                            <?php if (empty($data['field']->value)) { ?>
								<div class="grid-x cell acym__fields__value__sortable acym__content margin-bottom-1 grid-margin-x">
									<div class="medium-1 cell acym_vcenter align-center acym__field__sortable__listing__handle">
										<div class="grabbable acym__sortable__field__edit__handle grid-x">
											<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>
											<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>
										</div>
									</div>
									<input type="text" name="field[value][value][]" class="cell medium-4" value="">
									<input type="text" name="field[value][title][]" class="cell medium-4" value="">
									<div class="cell medium-2">
                                        <?php
                                        echo acym_select(
                                            [
                                                'n' => acym_translation('ACYM_NO'),
                                                'y' => acym_translation('ACYM_YES'),
                                            ],
                                            'field[value][disabled][]',
                                            'n',
                                            'class="acym__fields__edit__select acym__select" acym-data-infinite ',
                                            'value',
                                            'name'
                                        ); ?>
									</div>
								</div>
                            <?php } else {
                                $i = 0;
                                foreach ($data['field']->value as $value) { ?>
									<div class="grid-x cell acym__fields__value__sortable acym__content margin-bottom-1 grid-margin-x">
										<div class="medium-1 cell acym_vcenter align-center acym__field__sortable__listing__handle">
											<div class="grabbable acym__sortable__field__edit__handle grid-x">
												<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>
												<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>
											</div>
										</div>
										<input type="text" name="field[value][value][]" class="cell medium-4" value="<?php echo acym_escape($value->value); ?>">
										<input type="text" name="field[value][title][]" class="cell medium-4" value="<?php echo acym_escape($value->title); ?>">
										<div class="cell medium-2">
                                            <?php
                                            echo acym_select(
                                                [
                                                    'n' => acym_translation('ACYM_NO'),
                                                    'y' => acym_translation('ACYM_YES'),
                                                ],
                                                'field[value][disabled][]',
                                                $value->disabled,
                                                'class="acym__fields__edit__select acym__select" acym-data-infinite ',
                                                'value',
                                                'name'
                                            );
                                            ?>
										</div>
										<i class="cell acymicon-close small-1 acym__color__red cursor-pointer acym__field__delete__value"></i>
									</div>
                                    <?php $i++;
                                } ?>
                            <?php } ?>
						</div>
						<button type="button" class="button button-secondary margin-top-1" id="acym__fields__value__add-value"><?php echo acym_translation(
                                'ACYM_ADD_VALUE'
                            ); ?></button>
					</div>
				</div>

				<div class="cell grid-x acym__fields__change" id="acym__fields__from-db">
					<p class="cell"><?php echo acym_translation('ACYM_VALUES_FROM_DB'); ?></p>
					<label class="cell margin-top-1 medium-5"><?php echo acym_translation('ACYM_DATABASE'); ?>
                        <?php
                        echo acym_select(
                            $data['database'],
                            'fieldDB[database]',
                            empty($data['field']->fieldDB->database) ? '' : $data['field']->fieldDB->database,
                            'class="acym__fields__edit__select acym__select" acym-data-infinite ',
                            'value',
                            'name'
                        );
                        ?>
					</label>
					<div class="medium-1"></div>
					<label class="cell margin-top-1 medium-5"><?php echo acym_translation('ACYM_TABLES'); ?>
                        <?php
                        echo acym_select(
                            empty($data['field']->fieldDB->tables) ? [] : $data['field']->fieldDB->tables,
                            'fieldDB[table]',
                            empty($data['field']->fieldDB->table) ? '' : $data['field']->fieldDB->table,
                            'class="acym__fields__edit__select acym__select" acym-data-infinite',
                            'value',
                            'name'
                        );
                        ?>
					</label>
					<label class="cell margin-top-1 medium-5"><?php echo acym_translation('ACYM_VALUE'); ?>
                        <?php
                        echo acym_select(
                            empty($data['field']->fieldDB->columns) ? [] : $data['field']->fieldDB->columns,
                            'fieldDB[value]',
                            empty($data['field']->fieldDB->value) ? '' : $data['field']->fieldDB->value,
                            'class="acym__fields__edit__select acym__fields__database__columns acym__select" acym-data-infinite',
                            'value',
                            'name'
                        );
                        ?>
					</label>
					<div class="medium-1"></div>
					<label class="cell margin-top-1 medium-5"><?php echo acym_translation('ACYM_TITLE'); ?>
                        <?php
                        echo acym_select(
                            empty($data['field']->fieldDB->columns) ? [] : $data['field']->fieldDB->columns,
                            'fieldDB[title]',
                            empty($data['field']->fieldDB->title) ? '' : $data['field']->fieldDB->title,
                            'class="acym__fields__edit__select acym__fields__database__columns acym__select" acym-data-infinite',
                            'value',
                            'name'
                        );
                        ?>
					</label>
					<label class="cell margin-top-1 medium-4 margin-right-1"><?php echo acym_translation('ACYM_WHERE'); ?>
                        <?php
                        echo acym_select(
                            empty($data['field']->fieldDB->columns) ? [] : $data['field']->fieldDB->columns,
                            'fieldDB[where]',
                            empty($data['field']->fieldDB->where) ? '' : $data['field']->fieldDB->where,
                            'class="acym__fields__edit__select acym__fields__database__columns acym__select" acym-data-infinite',
                            'value',
                            'name'
                        );
                        ?>
					</label>
					<label class="cell margin-top-1 medium-3 margin-right-1"><?php echo acym_translation('ACYM_WHERE_OPERATION'); ?>
                        <?php
                        $operator = $data['operatorType'];
                        echo $operator->display(
                            'fieldDB[where_sign]',
                            empty($data['field']->fieldDB->where_sign) ? '' : $data['field']->fieldDB->where_sign,
                            'acym__fields__edit__select acym__select'
                        );
                        ?>
					</label>
					<label class="cell margin-top-1 medium-4"><?php echo acym_translation('ACYM_WHERE_VALUE'); ?>
						<input type="text"
							   name="fieldDB[where_value]"
							   class="margin-bottom-0"
							   value="<?php echo isset($data['field']->fieldDB->where_value) && strlen($data['field']->fieldDB->where_value) > 0 ? acym_escape(
                                   $data['field']->fieldDB->where_value
                               ) : ''; ?>">
					</label>
					<label class="cell margin-top-1 medium-5"><?php echo acym_translation('ACYM_ORDER_BY'); ?>
                        <?php
                        echo acym_select(
                            empty($data['field']->fieldDB->columns) ? [] : $data['field']->fieldDB->columns,
                            'fieldDB[order_by]',
                            empty($data['field']->fieldDB->order_by) ? '' : $data['field']->fieldDB->order_by,
                            'class="acym__fields__edit__select acym__fields__database__columns acym__select" acym-data-infinite',
                            'value',
                            'name'
                        );
                        ?>
					</label>
					<div class="medium-1"></div>
					<label class="cell margin-top-1 medium-5"><?php echo acym_translation('ACYM_SORT_ORDERING'); ?>
                        <?php
                        echo acym_select(
                            ['asc' => 'ASC', 'desc' => 'DESC'],
                            'fieldDB[sort_order]',
                            empty($data['field']->fieldDB->sort_order) ? '' : $data['field']->fieldDB->sort_order,
                            'class="acym__fields__edit__select acym__select" acym-data-infinite',
                            'value',
                            'name'
                        );
                        ?>
					</label>
				</div>
			</div>
		</div>
        <?php acym_formOptions(); ?>
	</div>
</form>
