<div id="acym_fulldiv_<?php echo $form->form_tag_name; ?>" class="acym__subscription__form__footer acym__subscription__form-erase">
    <?php if ($edition) {
        echo '<form action="#" onsubmit="return false;" id="'.$form->form_tag_name.'">';
    } else {
        $cookieExpirationAttr = empty($form->cookie['cookie_expiration']) ? 'acym-data-cookie="1"' : 'acym-data-cookie="'.$form->cookie['cookie_expiration'].'"';
        echo '<form acym-data-id="'.$form->id.'" '.$cookieExpirationAttr.' action="'.$form->form_tag_action.'" id="'.$form->form_tag_name.'" name="'.$form->form_tag_name.'" enctype="multipart/form-data" onsubmit="return submitAcymForm(\'subscribe\',\''.$form->form_tag_name.'\', \'acymSubmitSubForm\')">';
    }
    $files = [
        0 => $form->style_options['position'] == 'button-right' ? 'fields' : 'button',
        1 => $form->style_options['position'] == 'button-right' ? 'button' : 'fields',
    ];
    include ACYM_PARTIAL.'forms'.DS.$files[0].'.php';
    include ACYM_PARTIAL.'forms'.DS.$files[1].'.php'; ?>
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
	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__footer{
		position: fixed;
		bottom: 0;
		right: 0;
		left: 0;
		height: <?php echo $form->style_options['size']['height'];?>px;
		background-color: <?php echo $form->style_options['background_color'];?>;
		color: <?php echo $form->style_options['text_color'];?> !important;
		padding: .5rem;
		z-index: 999999;
		text-align: center;
		display: flex;
		justify-content: center;
		align-items: center
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__footer .responseContainer{
		margin-bottom: 0 !important;
		padding: .4rem !important;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__footer <?php echo '#'.$form->form_tag_name;?>{
		margin: 0;
		display: flex;
		justify-content: center;
		align-items: center
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__footer .acym__subscription__form__fields, <?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__footer .acym__subscription__form__button{
		display: flex;
		justify-content: center;
		align-items: center
	}
</style>
<?php if (!$edition) include acym_getPartial('forms', 'cookie'); ?>
