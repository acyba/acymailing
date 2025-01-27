<div class="intext_select_automation cell">
    <?php echo $operatorIn->display('acym_condition[conditions][__numor__][__numand__][acy_group][in]'); ?>
</div>
<div class="intext_select_automation cell">
    <?php
    echo acym_select(
        $groups,
        'acym_condition[conditions][__numor__][__numand__][acy_group][group]',
        null,
        ['class' => 'acym__select']
    );
    ?>
</div>
<?php if ($isJoomla) { ?>
	<div class="cell grid-x medium-3">
        <?php
        echo acym_switch(
            'acym_condition[conditions][__numor__][__numand__][acy_group][subgroup]',
            1,
            acym_translation('ACYM_INCLUDE_SUB_GROUPS')
        );
        ?>
	</div>
<?php } ?>
