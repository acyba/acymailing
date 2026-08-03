<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<h2 class="cell acym__title acym__title__secondary acym__fields__edit__section__title"
    id="acym__fields__edit__section__title--content">
    <?php echo acym_escapeHtml(acym_translation('ACYM_FIELD_CONTENT')); ?>
</h2>

<label class="cell large-6 acym__fields__change" id="acym__fields__default-value">
    <?php echo acym_escapeHtml(acym_translation('ACYM_DEFAULT_VALUE')); ?>
	<input type="text" name="field[default_value]"
	       value="<?php echo isset($data['field']->default_value) ? acym_escape($data['field']->default_value) : ''; ?>">
</label>

<div class="cell acym__fields__change" id="acym__fields__authorized-content">
	<label><?php echo acym_escapeHtml(acym_translation('ACYM_AUTHORIZED_CONTENT')); ?></label>
    <?php
    $regex = empty($data['field']->option->authorized_content->regex) ? '' : $data['field']->option->authorized_content->regex;

    $authorizedContent = ['all' => acym_translation('ACYM_ALL')];
    if ($data['field']->id != 2) {
        $authorizedContent['number'] = acym_translation('ACYM_NUMBER_ONLY');
        $authorizedContent['letters'] = acym_translation('ACYM_LETTERS_ONLY');
        $authorizedContent['numbers_letters'] = acym_translation('ACYM_NUMBERS_LETTERS_ONLY');
    }
    $placeholder = acym_translation('ACYM_REGULAR_EXPRESSION');
    $authorizedContent['regex'] = ' <input type="text" 
    										value="'.acym_escape($regex).'" 
    										name="field[option][authorized_content][regex]" 
    										placeholder="'.acym_escape($placeholder).'">';

    acym_radio(
        $authorizedContent,
        'field[option][authorized_content][]',
        empty($data['field']->option->authorized_content->{'0'}) ? 'all' : $data['field']->option->authorized_content->{'0'}
    );
    ?>
</div>

<!--if the user didn't respect the authorized content then we display the message below-->
<label class="cell large-6 acym__fields__change" id="acym__fields__error-message-invalid">
    <?php echo acym_escapeHtml(acym_translation('ACYM_ERROR_MESSAGE_INVALID_CONTENT')); ?>
	<input type="text"
	       name="field[option][error_message_invalid]"
	       value="<?php echo empty($data['field']->option->error_message_invalid) ? '' : acym_escape($data['field']->option->error_message_invalid); ?>">
</label>

<label class="cell large-6 acym__fields__change" id="acym__fields__format">
    <?php
    echo acym_escapeHtml(acym_translation('ACYM_FORMAT'));
    acym_info(
        [
            'textShownInTooltip' => acym_translationSprintf(
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
                ).'<br>'.acym_translation('ACYM_EXEMPLE_FORMAT'),
        ]
    );
    ?>
	<input type="text" name="field[option][format]"
	       value="<?php echo empty($data['field']->option->format) ? '%d%m%y' : acym_escape($data['field']->option->format); ?>">
</label>

<label class="cell acym__fields__change grid-x" id="acym__fields__max_characters">
	<span class="cell">
	<?php
    echo acym_escapeHtml(acym_translation('ACYM_MAXIMUM_CHARACTERS'));
    acym_info(['textShownInTooltip' => 'ACYM_MAXIMUM_CHARACTERS_TOOLTIP']);
    ?>
	</span>
	<input type="number"
	       min="0"
	       name="field[option][max_characters]"
	       value="<?php echo empty($data['field']->option->max_characters) ? '' : acym_escape($data['field']->option->max_characters); ?>"
	       class="cell medium-2 small-3">
</label>
