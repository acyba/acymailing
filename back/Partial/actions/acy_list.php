<div class="intext_select_automation cell">
    <?php
    echo acym_select(
        $listActions,
        'acym_action[actions][__and__][acy_list][list_actions]',
        null,
        ['class' => 'acym__select']
    );
    ?>
</div>
<div class="intext_select_automation cell">
    <?php
    echo acym_select(
        $lists,
        'acym_action[actions][__and__][acy_list][list_id]',
        null,
        ['class' => 'acym__select']
    );
    ?>
</div>
