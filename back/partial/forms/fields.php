<div class="acym__subscription__form__fields">
    <?php
    $config = acym_config();
    if ($form->settings['lists']['display_position'] === 'before') {
        if ($form->type === $form->formClass::SUB_FORM_TYPE_POPUP && !empty($form->settings['message']['text']) && $form->settings['message']['position'] === 'before-lists') {
            echo '<p id="acym__subscription__form__popup-text">'.acym_translation($form->settings['message']['text']).'</p>';
        }
        include acym_getPartial('forms', 'lists');
    }
    if ($form->type === $form->formClass::SUB_FORM_TYPE_POPUP && !empty($form->settings['message']['text']) && $form->settings['message']['position'] === 'before-fields') {
        echo '<p id="acym__subscription__form__popup-text">'.acym_translation($form->settings['message']['text']).'</p>';
    }
    foreach ($form->settings['fields']['displayed'] as $field) {
        $size = empty($field->option->size) ? '' : 'width:'.$field->option->size.'px';
        echo '<div class="onefield fieldacy'.$field->id.' acyfield_'.$field->type.'" id="field_'.$field->id.'">';
        echo $form->fieldClass->displayField($field, $field->default_value, $size, $field->valuesArray, $form->settings['fields']['display_mode'] === 'outside', true);
        echo '</div>';

        if ($field->id == 2 && $config->get('email_confirmation')) {
            echo $form->fieldClass->setEmailConfirmationField($form->settings['fields']['display_mode'] === 'outside', $size);
        }
    }
    if ($form->settings['lists']['display_position'] === 'after') {
        if ($form->type === $form->formClass::SUB_FORM_TYPE_POPUP && !empty($form->settings['message']['text']) && $form->settings['message']['position'] === 'before-lists') {
            echo '<p id="acym__subscription__form__popup-text">'.acym_translation($form->settings['message']['text']).'</p>';
        }
        include acym_getPartial('forms', 'lists');
    }
    include acym_getPartial('forms', 'termspolicy');
    include acym_getPartial('forms', 'recaptcha');
    ?>
	<style>
		<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__fields{
			display: flex;
			justify-content: center;
			align-items: center
		}

		<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__fields > *{
			margin: <?php echo in_array($form->type, [$form->formClass::SUB_FORM_TYPE_FOOTER, $form->formClass::SUB_FORM_TYPE_HEADER]) ? 'auto 10px' : '10px auto';?> !important;
		}
	</style>
</div>
