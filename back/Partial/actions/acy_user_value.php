<div class="intext_select_automation">
    <?php
    echo acym_select(
        $userFields,
        'acym_action[actions][__and__][acy_user_value][field]',
        null,
        ['class' => 'acym__select acym__automation__actions__fields__dropdown']
    );
    ?>
</div>
<div class="intext_select_automation cell">
    <?php
    echo acym_select(
        $userOperator,
        'acym_action[actions][__and__][acy_user_value][operator]',
        null,
        ['class' => 'acym__select acym__automation__actions__operator__dropdown']
    );
    ?>
</div>
<input type="text"
	   name="acym_action[actions][__and__][acy_user_value][value]"
	   class="intext_input_automation cell acym__automation__one-field acym__automation__action__regular-field">
<?php echo implode(' ', $customFieldValues); ?>
