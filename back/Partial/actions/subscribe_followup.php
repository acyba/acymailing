<div class="intext_select_automation cell">
    <?php
    echo acym_select(
        $allListFollowups,
        'acym_action[actions][__and__][subscribe_followup][followup_id]',
        null,
        [
            'class' => 'acym__select',
            'data-placeholder' => (!empty($listFollowups) ? acym_translation('ACYM_SELECT_FOLLOWUP', true) : acym_translation('ACYM_FOLLOWUP_NOT_FOUND', true)),
        ],
        'id',
        'name'
    );
    ?>
</div>
