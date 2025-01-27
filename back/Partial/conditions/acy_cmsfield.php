<div class="intext_select_automation cell">
    <?php
    echo acym_select(
        $cmsFields,
        'acym_condition[conditions][__numor__][__numand__][acy_cmsfield][field]',
        null,
        ['class' => 'acym__select']
    );
    ?>
</div>
<div class="intext_select_automation cell">
    <?php echo $operator->display('acym_condition[conditions][__numor__][__numand__][acy_cmsfield][operator]'); ?>
</div>
<input class="intext_input_automation cell" type="text" name="acym_condition[conditions][__numor__][__numand__][acy_cmsfield][value]">
