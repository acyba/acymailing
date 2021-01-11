<input type="hidden" name="ctrl" value="frontusers" />
<input type="hidden" name="task" value="notask" />
<input type="hidden" name="page" value="acymailing_front"/>
<input type="hidden" name="action" value="acymailing_frontrouter"/>
<input type="hidden" name="option" value="<?php echo acym_escape(ACYM_COMPONENT); ?>" />
<input type="hidden" name="acy_source" value="<?php echo 'Form ID '.$form->id; ?>">
<input type="hidden" name="acyformname" value="<?php echo $form->form_tag_name; ?>">
<input type="hidden" name="acysubmode" value="form_acym">

<?php
$redirection = $form->redirection_options['after_subscription'];
$ajax = empty($redirection) ? '1' : '0';
?>
<input type="hidden" name="redirect" value="<?php echo $redirection; ?>">
<input type="hidden" name="ajax" value="<?php echo $ajax; ?>">
<input type="hidden" name="confirmation_message" value="<?php echo acym_escape($form->redirection_options['confirmation_message']); ?>">
