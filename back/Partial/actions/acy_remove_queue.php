<div class="intext_select_automation">
    <?php
    echo acym_select(
        [],
        'acym_action[actions][__and__][acy_remove_queue][mail_id]',
        null,
        [
            'class' => 'acym_select2_ajax',
            'data-min' => 0,
            'data-placeholder' => acym_translation('ACYM_SELECT_AN_EMAIL'),
            'data-params' => $ajaxParams,
        ]
    );
    ?>
</div>
