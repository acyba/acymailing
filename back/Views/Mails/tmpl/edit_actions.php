<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
$cancelUrl = empty($data['return']) ? '' : $data['return'];
acym_cancelButton('ACYM_CANCEL', $cancelUrl);

if (acym_isAdmin() && $data['mail']->editor != 'acyEditor') {
    ?>
	<button type="submit"
	        data-task="test"
	        class="cell large-shrink button-secondary medium-6 button acy_button_submit acym__template__save acy_button_submit">
        <?php echo acym_escapeHtml(acym_translation('ACYM_SEND_TEST')); ?>
	</button>
    <?php
}

acym_modalInclude(
    '<button type="button" id="acym__template__start-from" class="cell button-secondary button button-full-width">'.acym_escapeHtml(
        acym_translation('ACYM_START_FROM')
    ).'</button>',
    dirname(__FILE__).DS.'choose_template_ajax.php',
    'acym__template__choose__modal',
    $data,
    '',
    ['class' => 'cell large-shrink medium-6 margin-bottom-0']
);

$beforeSave = '';
if ($data['mail']->editor === 'acyEditor') {
    $beforeSave = 'editorApply';
    if (!empty($data['multilingual'])) {
        $beforeSave = 'editorApplyMultilingual';
    }
}
?>
<button id="apply"
        data-before-action="<?php echo acym_escape($beforeSave); ?>"
        type="button"
        data-task="apply"
        class="cell large-shrink button-secondary medium-6 button acym__template__save acy_button_submit">
    <?php echo acym_escapeHtml(acym_translation('ACYM_SAVE')); ?>
</button>
<button style="display: none;"
        data-before-action="<?php echo acym_escape($beforeSave); ?>" data-task="apply" class="acy_button_submit" id="data_apply"></button>
<button id="save"
        data-before-action="<?php echo acym_escape($beforeSave); ?>"
        type="button"
        data-task="save"
        class="cell large-shrink medium-6 button acy_button_submit">
    <?php echo acym_escapeHtml(acym_translation('ACYM_SAVE_EXIT')); ?>
</button>
<button style="display: none;" data-before-action="<?php echo acym_escape($beforeSave); ?>" data-task="save" class="acy_button_submit" id="data_save"></button>
