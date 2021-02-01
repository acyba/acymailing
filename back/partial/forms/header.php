<div id="acym_fulldiv_<?php echo $form->form_tag_name; ?>" class="acym__subscription__form__header acym__subscription__form-erase full-width">
    <?php
    if ($edition) {
        echo '<form action="#" onsubmit="return false;" id="'.$form->form_tag_name.'">';
    } else {
        $cookieExpirationAttr = empty($form->cookie['cookie_expiration']) ? 'acym-data-cookie="1"' : 'acym-data-cookie="'.$form->cookie['cookie_expiration'].'"';
        echo '<form acym-data-id="'.$form->id.'" '.$cookieExpirationAttr.' action="'.$form->form_tag_action.'" id="'.$form->form_tag_name.'" name="'.$form->form_tag_name.'" enctype="multipart/form-data" onsubmit="return submitAcymForm(\'subscribe\',\''.$form->form_tag_name.'\', \'acymSubmitSubForm\')">';
    }
    $files = [
        0 => $form->style_options['position'] == 'button-right' ? 'fields' : 'button',
        1 => $form->style_options['position'] == 'button-right' ? 'button' : 'fields',
    ];
    include acym_getPartial('forms', $files[0]);
    include acym_getPartial('forms', $files[1]);
    include acym_getPartial('forms', 'hidden_params');
    ?>
	</form>
</div>
<style>
	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__header{
	<?php echo !empty($form->style_options['size']['width'])? 'width: '.$form->style_options['size']['width'].'%': '';?>;
		height: <?php echo $form->style_options['size']['height'];?>px;
		background-color: <?php echo $form->style_options['background_color'];?>;
		color: <?php echo $form->style_options['text_color'];?> !important;
		padding: .5rem;
		z-index: 999999;
		text-align: center;
		display: flex;
		justify-content: center;
		align-items: center;
		margin-left: auto;
		margin-right: auto;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__header .responseContainer{
		margin-bottom: 0 !important;
		padding: .4rem !important;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__header <?php echo '#'.$form->form_tag_name;?>{
		margin: 0;
		display: flex;
		justify-content: center;
		align-items: center
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__header .acym__subscription__form__fields, <?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__header .acym__subscription__form__button{
		display: flex;
		justify-content: center;
		align-items: center
	}
</style>
<?php if (!$edition) { ?>
	<script type="text/javascript">
        document.body.prepend(document.querySelector('#acym_fulldiv_<?php echo $form->form_tag_name; ?>'));
	</script>
    <?php include acym_getPartial('forms', 'cookie');
} ?>
