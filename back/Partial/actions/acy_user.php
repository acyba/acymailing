<div class="intext_select_automation cell">
    <?php
    echo acym_select(
        $userActions,
        'acym_action[actions][__and__][acy_user][action]',
        null,
        ['class' => 'acym__select']
    )
    ?>
</div>
