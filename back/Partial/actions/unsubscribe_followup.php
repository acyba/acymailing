<div class="intext_select_automation cell">
    <?php
    echo acym_select(
        $allListFollowups,
        'acym_action[actions][__and__][unsubscribe_followup][followup_id]',
        null,
        [
            'class' => 'acym__select',
            'data-placeholder' => acym_translation(empty($listFollowups) ? 'ACYM_FOLLOWUP_NOT_FOUND' : 'ACYM_SELECT_FOLLOWUP', true),
        ],
        'id',
        'name'
    );
    ?>
</div>
