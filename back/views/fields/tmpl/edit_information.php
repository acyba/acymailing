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
	<div class="cell margin-top-1 large-11" id="required_error_message">
		<label class="acym__fields__change" id="acym__fields__error-message">
            <?php echo acym_translation('ACYM_CUSTOM_ERROR'); ?>
			<input type="text"
				   name="field[option][error_message]"
				   value="<?php echo empty($data['field']->option->error_message) ? '' : acym_escape($data['field']->option->error_message); ?>"
				   placeholder="<?php echo acym_escape(acym_translationSprintf('ACYM_DEFAULT_REQUIRED_MESSAGE', 'xxx')); ?>">
		</label>
	</div>
<?php } ?>
