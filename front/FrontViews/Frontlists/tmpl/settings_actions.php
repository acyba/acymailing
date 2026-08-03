<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification

use AcyMailing\Helpers\EntitySelectHelper;

acym_cancelButton();
if (!empty($data['listInformation']->id) && !empty($data['subscribersEntitySelect'])) {
    $entityHelper = new EntitySelectHelper();
    acym_modal(
        acym_translation('ACYM_MANAGE_SUBSCRIBERS'),
        $entityHelper->entitySelect(
            [
                'entity' => 'user',
                'entityParams' => ['join' => 'join_list-'.$data['subscribersEntitySelect']],
                'columnsToDisplay' => $entityHelper->getColumnsForUser('userlist.user_id'),
                'buttonSubmit' => [
                    'text' => acym_translation('ACYM_CONFIRM'),
                    'action' => 'saveSubscribers',
                ],
                'displayedName' => 'subscriber',
            ]
        ),
        'acym__lists__settings__subscribers__entity__modal',
        [],
        ['class' => 'cell medium-6 large-shrink button button-secondary']
    );
}
?>
<button type="submit" data-task="apply" class="cell acy_button_submit button-secondary button medium-6 large-shrink">
    <?php echo acym_escapeHtml(acym_translation('ACYM_SAVE')); ?>
</button>
<button type="submit" data-task="save" class="cell acy_button_submit button medium-6 large-shrink margin-right-0">
    <?php echo acym_escapeHtml(acym_translation('ACYM_SAVE_EXIT')); ?>
</button>
