<div class="acym__subscription__form__fields">
    <?php
    include acym_getPartial('forms', 'recaptcha');
    if ($form->lists_options['display_position'] == 'before') {
        if ($form->type == 'popup' && !empty($form->message_options['text']) && $form->message_options['position'] == 'before-lists') {
            echo '<p id="acym__subscription__form__popup-text">'.$form->message_options['text'].'</p>';
        }
        include acym_getPartial('forms', 'lists');
    }
    if ($form->type == 'popup' && !empty($form->message_options['text']) && $form->message_options['position'] == 'before-fields') {
        echo '<p id="acym__subscription__form__popup-text">'.$form->message_options['text'].'</p>';
    }
    foreach ($form->fields_options['displayed'] as $field) {
        $size = empty($field->option->size) ? '' : 'width:'.$field->option->size.'px';
        echo '<div class="onefield fieldacy'.$field->id.' acyfield_'.$field->type.'" id="field_'.$field->id.'">';
        echo $form->fieldClass->displayField($field, $field->default_value, $size, $field->valuesArray, $form->fields_options['display_mode'] == 'outside', true);
        echo '</div>';

        if ($field->id == 2 && $config->get('email_confirmation')) {
            echo $form->fieldClass->setEmailConfirmationField($form->fields_options['display_mode'] == 'outside', $size);
        }
    }
    if ($form->lists_options['display_position'] == 'after') {
        if ($form->type == 'popup' && !empty($form->message_options['text']) && $form->message_options['position'] == 'before-lists') {
            echo '<p id="acym__subscription__form__popup-text">'.$form->message_options['text'].'</p>';
        }
        include acym_getPartial('forms', 'lists');
    }
    include acym_getPartial('forms', 'termspolicy');
    ?>
	<style>
		<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__fields{
			display: flex;
			justify-content: center;
			align-items: center
		}

		<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__fields > *{
			margin: <?php echo in_array($form->type, [$form->formClass->getConstFooter(), $form->formClass->getConstHeader()]) ? 'auto 10px' : '10px auto';?> !important;
		}
	</style>
</div>
