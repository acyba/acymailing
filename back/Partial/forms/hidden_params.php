<input type="hidden" name="ctrl" value="frontusers" />
<input type="hidden" name="task" value="notask" />
<input type="hidden" name="page" value="acymailing_front" />
<input type="hidden" name="option" value="<?php echo acym_escape(ACYM_COMPONENT); ?>" />
<input type="hidden" name="acy_source" value="Form ID <?php echo intval($form->id); ?>">
<input type="hidden" name="acyformname" value="<?php echo acym_escape($form->form_tag_name); ?>">
<input type="hidden" name="acymformtype" value="<?php echo acym_escape($form->type); ?>">
<input type="hidden" name="acysubmode" value="form_acym">

<?php
$redirection = $form->settings['redirection']['after_subscription'];
$ajax = empty($redirection) ? '1' : '0';

$confirmationMessage = '';
if (!empty($form->settings['redirection']['confirmation_message'])) {
	$confirmationMessage = $form->settings['redirection']['confirmation_message'];
}
$currentLanguageTag = acym_getLanguageTag();
if (acym_isMultilingual() && !empty($form->settings['redirection']['langConfirm'][$currentLanguageTag])) {
	$confirmationMessage = $form->settings['redirection']['langConfirm'][$currentLanguageTag];
}
?>
<input type="hidden" name="redirect" value="<?php echo acym_escapeUrl($redirection); ?>">
<input type="hidden" name="ajax" value="<?php echo acym_escape($ajax); ?>">
<input type="hidden"
	   name="confirmation_message"
	   value="<?php echo acym_escape($confirmationMessage); ?>">
