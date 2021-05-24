<h2 class="cell acym__title acym__title__secondary margin-top-2 acym__fields__edit__section__title" id="acym__fields__edit__section__title--style">
    <?php echo acym_translation('ACYM_FIELD_STYLE'); ?>
</h2>

<label class="cell large-6 acym__fields__change" id="acym__fields__rows"><?php echo acym_translation('ACYM_ROWS'); ?>
	<input type="number"
		   name="field[option][rows]"
		   value="<?php echo empty($data['field']->option->rows) ? '' : $data['field']->option->rows; ?>">
</label>

<label class="cell large-6 acym__fields__change" id="acym__fields__columns"><?php echo acym_translation('ACYM_COLUMNS'); ?>
	<input type="number"
		   name="field[option][columns]"
		   value="<?php echo empty($data['field']->option->columns) ? '' : $data['field']->option->columns; ?>">
</label>

<label class="cell large-6 acym__fields__change grid-x" id="acym__fields__size">
	<span class="cell"><?php echo acym_translation('ACYM_INPUT_WIDTH'); ?></span>
	<input type="number"
		   name="field[option][size]"
		   value="<?php echo empty($data['field']->option->size) ? '' : $data['field']->option->size; ?>"
		   class="cell medium-4">
</label>
