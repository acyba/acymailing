<div class="acym__subscription__form__lists">
    <?php
    foreach ($form->lists_options['displayed'] as $listId) {
        if (!empty($form->lists_options['automatic_subscribe']) && in_array($listId, $form->lists_options['automatic_subscribe'])) continue;
        $checked = !empty($form->lists_options['checked']) && in_array($listId, $form->lists_options['checked']) ? 'checked' : '';
        echo '<label><input type="checkbox" value="'.$listId.'" name="subscription[]" '.$checked.'>'.$form->lists[$listId].'</label>';
    }

    $hiddenLists = empty($form->lists_options['automatic_subscribe']) ? '' : implode(',', $form->lists_options['automatic_subscribe']);
    echo '<input type="hidden" name="hiddenlists" value="'.$hiddenLists.'">';
    ?>
	<style>
		<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__fields .acym__subscription__form__lists{
			display: inline-block;
			width: auto;
			margin: 0 20px;
			text-align: left;
		}

		<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__fields .acym__subscription__form__lists label{
			display: inline-block;
			margin-right: 10px;
			width: auto;
		}

		<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__fields .acym__subscription__form__lists input[type="checkbox"]{
			margin-top: 0 !important;
			margin-right: 5px;
		}
	</style>
</div>
