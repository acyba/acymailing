<h2 class="cell acym__title acym__title__secondary margin-top-2 acym__fields__edit__section__title" id="acym__fields__edit__section__title--values">
    <?php echo acym_translation('ACYM_FIELD_VALUES'); ?>
</h2>

<label class="cell large-11 acym__fields__change" id="acym__fields__custom-text">
    <?php echo acym_translation('ACYM_CUSTOM_TEXT'); ?>
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
                <?php echo acym_translation('ACYM_DISABLED'); ?>
			</div>
			<div class="small-1 cell acym__listing__header__title"></div>
		</div>
		<div class="acym__fields__values__listing__sortable cell grid-x">
            <?php if (empty($data['field']->value)) { ?>
				<div class="grid-x cell acym__fields__value__sortable acym__content margin-bottom-1 grid-margin-x margin-y">
					<div class="medium-1 cell acym_vcenter align-center acym__field__sortable__listing__handle">
						<div class="grabbable acym__sortable__field__edit__handle grid-x">
							<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>
							<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>
						</div>
					</div>
					<input type="text" name="field[value][value][]" class="cell medium-4 acym__fields__value__value" value="">
					<input type="text" name="field[value][title][]" class="cell medium-4 acym__fields__value__title" value="">
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
					<div class="grid-x cell acym__fields__value__sortable acym__content margin-bottom-1 grid-margin-x margin-y">
						<div class="medium-1 cell acym_vcenter align-center acym__field__sortable__listing__handle">
							<div class="grabbable acym__sortable__field__edit__handle grid-x">
								<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>
								<i class="acymicon-ellipsis-h cell acym__color__dark-gray"></i>
							</div>
						</div>
						<input type="text" name="field[value][value][]" class="cell medium-4 acym__fields__value__value" value="<?php echo acym_escape($value->value); ?>">
						<input type="text" name="field[value][title][]" class="cell medium-4 acym__fields__value__title" value="<?php echo acym_escape($value->title); ?>">
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
