<div class="acym__subscription__form__button">
    <?php
    $onclick = $edition ? '' : 'onclick="try{ return submitAcymForm(\'subscribe\',\''.$form->form_tag_name.'\', \'acymSubmitSubForm\'); }catch(err){alert(\'The form could not be submitted \'+err);return false;}"';
    ?>
	<button type="button" <?php echo $onclick; ?>><?php echo $form->button_options['text']; ?></button>
	<style>
		<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__button{
			display: flex;
			justify-content: center;
			align-items: center
		}

		<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__button button{
			background-color: <?php echo $form->button_options['background_color'];?>;
			color: <?php echo $form->button_options['text_color'];?>;
			border-width: <?php echo $form->button_options['border_size'];?>px;
			border-style: <?php echo $form->button_options['border_type'];?>;
			border-color: <?php echo $form->button_options['border_color'];?>;
			border-radius: <?php echo $form->button_options['border_radius'];?>px;
			padding: <?php echo $form->button_options['size']['height'];?>px <?php echo $form->button_options['size']['width'];?>px;
		}
	</style>
</div>
