<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
	<div class="intext_select_automation cell">
        <?php
        acym_select(
            $userFields,
            'acym_action[actions][__and__][acy_user_value][field]',
            null,
            ['class' => 'acym__select acym__automation__actions__fields__dropdown'],
            'value',
            'text',
            null,
            false,
            true
        );
        ?>
	</div>
	<div class="intext_select_automation cell">
        <?php
        acym_select(
            $userOperator,
            'acym_action[actions][__and__][acy_user_value][operator]',
            null,
            ['class' => 'acym__select acym__automation__actions__operator__dropdown'],
            'value',
            'text',
            null,
            false,
            true
        );
        ?>
	</div>
	<input type="text"
	       name="acym_action[actions][__and__][acy_user_value][value]"
	       class="intext_input_automation cell acym__automation__one-field acym__automation__action__regular-field">
<?php
foreach ($customFields as $field) {
    if (in_array($field->type, ['single_dropdown', 'radio', 'checkbox', 'multiple_dropdown']) && !empty($field->value)) {
        $values = [];
        $field->value = json_decode($field->value, true);
        foreach ($field->value as $value) {
            $valueTmp = new stdClass();
            $valueTmp->text = $value['title'];
            $valueTmp->value = $value['value'];
            if ($value['disabled'] == 'y') $valueTmp->disable = true;
            $values[$value['value']] = $valueTmp;
        }
        echo '<div class="acym__automation__one-field intext_select_automation cell" style="display: none">';
        acym_select(
            $values,
            '[actions][__and__][acy_user_value][value]',
            null,
            [
                'class' => 'acym__select acym__automation__actions__fields__select',
                'data-action-field' => $field->id,
            ],
            'value',
            'text',
            null,
            false,
            true
        );
        echo '</div>';
    } elseif ('date' == $field->type) {
        acym_tooltip(
            [
                'hoveredText' => '<input class="acym__automation__one-field acym__automation__actions__fields__select intext_input_automation cell" 
										type="text" 
										name="[actions][__and__][acy_user_value][value]" 
										style="display: none" 
										data-action-field="'.intval($field->id).'">',
                'textShownInTooltip' => acym_translation('ACYM_DATE_FORMAT_FILTER'),
                'classContainer' => 'intext_select_automation cell',
            ]
        );
    }
}
