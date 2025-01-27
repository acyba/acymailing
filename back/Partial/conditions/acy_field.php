<div class="intext_select_automation cell">
    <?php
    echo acym_select(
        $fields,
        'acym_condition[conditions][__numor__][__numand__][acy_field][field]',
        null,
        ['class' => 'acym__select acym__automation__conditions__fields__dropdown']
    );
    ?>
</div>
<div class="intext_select_automation cell">
    <?php
    echo $operator->display(
        'acym_condition[conditions][__numor__][__numand__][acy_field][operator]',
        '',
        'acym__automation__conditions__operator__dropdown'
    );
    ?>
</div>
<input class="acym__automation__one-field intext_input_automation cell acym__automation__condition__regular-field"
	   type="text"
	   name="acym_condition[conditions][__numor__][__numand__][acy_field][value]">
<?php echo implode(' ', $customFieldValues); ?>
