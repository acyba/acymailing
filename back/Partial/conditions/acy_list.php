<div class="intext_select_automation cell">
    <?php
    echo acym_select(
        $list['type'],
        'acym_condition[conditions][__numor__][__numand__][acy_list][action]',
        null,
        ['class' => 'intext_select_automation acym__select']
    );
    ?>
</div>
<div class="intext_select_automation cell">
    <?php
    echo acym_select(
        $list['lists'],
        'acym_condition[conditions][__numor__][__numand__][acy_list][list]',
        null,
        ['class' => 'intext_select_automation acym__select']
    );
    ?>
</div>
<br>
<div class="cell grid-x grid-margin-x">
    <?php echo acym_dateField('acym_condition[conditions][__numor__][__numand__][acy_list][date-min]'); ?>
	<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>
	<div class="intext_select_automation">
        <?php
        echo acym_select(
            $list['date'],
            'acym_condition[conditions][__numor__][__numand__][acy_list][date-type]',
            null,
            ['class' => 'intext_select_automation acym__select cell']
        );
        ?>
	</div>
	<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>
    <?php echo acym_dateField('acym_condition[conditions][__numor__][__numand__][acy_list][date-max]'); ?>
</div>
