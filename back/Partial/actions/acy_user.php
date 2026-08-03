<?php
// context verification
?>
<div class="intext_select_automation cell">
    <?php
    acym_select(
        $userActions,
        'acym_action[actions][__and__][acy_user][action]',
        null,
        ['class' => 'acym__select'],
        'value',
        'text',
        null,
        false,
        true
    )
    ?>
</div>
