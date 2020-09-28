<div class="acym__subscription__form__fields">
    <?php
    include acym_getPartial('forms', 'recaptcha');
    if ($form->lists_options['display_position'] == 'before') include acym_getPartial('forms', 'lists');
    foreach ($form->fields_options['displayed'] as $field) {
        $size = empty($field->option->size) ? '' : 'width:'.$field->option->size.'px';
        echo $form->fieldClass->displayField($field, $field->default_value, $size, $field->valuesArray, $form->fields_options['display_mode'] == 'outside', true);
    }
    if ($form->lists_options['display_position'] == 'after') include acym_getPartial('forms', 'lists');
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
