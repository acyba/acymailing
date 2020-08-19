<div id="acym_fulldiv_<?php echo $form->form_tag_name; ?>" class="acym__subscription__form__shortcode">
    <?php if ($edition) {
        echo '<form action="#" onsubmit="return false;" id="'.$form->form_tag_name.'">';
    } else {
        echo '<form action="'.$form->form_tag_action.'" id="'.$form->form_tag_name.'" name="'.$form->form_tag_name.'" enctype="multipart/form-data" onsubmit="return submitAcymForm(\'subscribe\',\''.$form->form_tag_name.'\', \'acySubmitSubForm\')">';
    }
    include ACYM_PARTIAL.'forms'.DS.'fields.php';
    include ACYM_PARTIAL.'forms'.DS.'button.php';
    ?>
	<input type="hidden" name="ctrl" value="frontusers" />
	<input type="hidden" name="task" value="notask" />
	<input type="hidden" name="option" value="<?php echo acym_escape(ACYM_COMPONENT); ?>" />
	<input type="hidden" name="ajax" value="1">
	<input type="hidden" name="acy_source" value="<?php echo 'Form ID '.$form->id; ?>">
	<input type="hidden" name="acyformname" value="<?php echo $form->form_tag_name; ?>">
	<input type="hidden" name="acysubmode" value="form_acym">
	</form>
</div>
<style>
	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__shortcode{
		height: <?php echo $form->style_options['size']['height'];?>px;
		width: <?php echo $form->style_options['size']['width'];?>px;
		background-color: <?php echo $form->style_options['background_color'];?>;
		color: <?php echo $form->style_options['text_color'];?> !important;
		padding: .5rem;
		text-align: center;
		display: flex;
		justify-content: center;
		align-items: center;
		margin: 1rem auto;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__shortcode .responseContainer{
		margin-bottom: 0 !important;
		padding: .4rem !important;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__shortcode <?php echo '#'.$form->form_tag_name;?>{
		margin: 0;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__shortcode .acym__subscription__form__fields, <?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__shortcode .acym__subscription__form__button{
		display: block;
		width: 100%;
		margin: 1rem 0 !important;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__shortcode .acym__subscription__form__fields .acym__subscription__form__lists{
		display: block;
		width: 100%;
		margin: 1rem 10px !important;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__shortcode .acym__subscription__form__fields > *:not(style){
		display: block;
	}

	<?php if (in_array($form->style_options['position'], ['image-right', 'image-left'])){?>
	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__shortcode <?php echo '#'.$form->form_tag_name;?>{
		display: flex;
		justify-content: center;
		align-items: center
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__shortcode__fields-button, <?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__image{
		display: inline-block;
	}

	<?php }?>
</style>
