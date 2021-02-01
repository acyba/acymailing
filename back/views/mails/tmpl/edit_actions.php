<?php
$cancelUrl = empty($data['return']) ? '' : $data['return'];
echo acym_cancelButton('ACYM_CANCEL', $cancelUrl);

if (acym_isAdmin() && $data['mail']->editor != 'acyEditor') {
    ?>
	<button type="submit" data-task="test" class="cell large-shrink button-secondary medium-6 button acy_button_submit acym__template__save acy_button_submit">
        <?php echo acym_translation('ACYM_SEND_TEST'); ?>
	</button>
    <?php
}
echo acym_modalInclude(
    '<button type="button" id="acym__template__start-from" class="cell button-secondary button">'.acym_translation('ACYM_START_FROM').'</button>',
    dirname(__FILE__).DS.'choose_template_ajax.php',
    'acym__template__choose__modal',
    $data,
    '',
    '',
    'class="cell large-shrink medium-6"'
);

$attributeSave = empty($data['multilingual']) || $data['editor']->editor == 'html' ? '' : 'acym-data-before="acym_editorWysidMultilingual.storeCurrentValues(true);"';

?>
<button id="apply" <?php echo $attributeSave; ?> type="button" data-task="apply" class="cell large-shrink button-secondary medium-6 button acym__template__save acy_button_submit">
    <?php echo acym_translation('ACYM_SAVE'); ?>
</button>
<button style="display: none;" data-task="apply" class="acy_button_submit" id="data_apply"></button>
<button id="save" <?php echo $attributeSave; ?> type="button" data-task="save" class="cell large-shrink medium-6 button acy_button_submit">
    <?php echo acym_translation('ACYM_SAVE_EXIT'); ?>
</button>
<button style="display: none;" data-task="save" class="acy_button_submit" id="data_save"></button>
