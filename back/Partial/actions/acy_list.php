<?php
// context verification
?>
<div class="intext_select_automation cell">
    <?php
    acym_select(
        $listActions,
        'acym_action[actions][__and__][acy_list][list_actions]',
        null,
        ['class' => 'acym__select'],
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
        $lists,
        'acym_action[actions][__and__][acy_list][list_id]',
        null,
        ['class' => 'acym__select'],
        'value',
        'text',
        null,
        false,
        true
    );
    ?>
</div>
