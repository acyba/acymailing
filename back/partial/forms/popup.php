<div class="acym__subscription__form__popup__overlay acym__subscription__form-erase" id="acym_fulldiv_<?php echo $form->form_tag_name; ?>">
	<div class="acym__subscription__form__popup">
		<p class="acym__subscription__form__popup__close">X</p>
        <?php if ($edition) {
            echo '<form action="#" onsubmit="return false;" id="'.$form->form_tag_name.'">';
        } else {
            echo '<form acym-data-id="'.$form->id.'" action="'.$form->form_tag_action.'" id="'.$form->form_tag_name.'" name="'.$form->form_tag_name.'" enctype="multipart/form-data" onsubmit="return submitAcymForm(\'subscribe\',\''.$form->form_tag_name.'\', \'acySubmitSubForm\')">';
        }
        if (in_array($form->style_options['position'], ['image-top', 'image-left'])) include acym_getPartial('forms', 'image');
        echo '<div class="acym__subscription__form__popup__fields-button">';
        include acym_getPartial('forms', 'fields');
        include acym_getPartial('forms', 'button');
        echo '</div>';
        if (in_array($form->style_options['position'], ['image-bottom', 'image-right'])) include acym_getPartial('forms', 'image');
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
</div>
<style>
	<?php echo '#acym_fulldiv_'.$form->form_tag_name; ?>.acym__subscription__form__popup__overlay{
		display: <?php echo $edition ? 'inline' : 'none';?>;
		position: fixed;
		top: 0;
		bottom: 0;
		right: 0;
		left: 0;
		background-color: rgba(200, 200, 200, .5);
		z-index: 999999;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup__close{
		position: absolute;
		top: 10px;
		right: 10px;
		font-weight: bold;
		font-size: 1rem;
		cursor: pointer;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup{
		position: fixed;
		left: 50%;
		transform: translate(-50%, -50%);
		top: 50%;
		padding: <?php echo $form->style_options['padding']['height'];?>px <?php echo $form->style_options['padding']['width'];?>px;
		background-color: <?php echo $form->style_options['background_color'];?>;
		color: <?php echo $form->style_options['text_color'];?> !important;
		z-index: 999999;
		text-align: center;
		display: flex;
		justify-content: center;
		align-items: center
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .responseContainer{
		margin-bottom: 0 !important;
		padding: .4rem !important;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup <?php echo '#'.$form->form_tag_name;?>{
		margin: 0;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .acym__subscription__form__fields, <?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .acym__subscription__form__button{
		display: block;
		width: 100%;
		margin: 1rem 0 !important;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .acym__subscription__form__fields input:not([type="radio"]):not([type="checkbox"]), <?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .acym__subscription__form__fields label{
		display: block;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .acym__subscription__form__fields input[type="radio"], <?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .acym__subscription__form__fields input[type="checkbox"]{
		margin-left: 5px;
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup .acym__subscription__form__fields .acym__subscription__form__lists{
		display: block;
		width: 100%;
		margin: 1rem 10px !important;
	}

	<?php if (in_array($form->style_options['position'], ['image-right', 'image-left'])){?>
	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup <?php echo '#'.$form->form_tag_name;?>{
		display: flex;
		justify-content: center;
		align-items: center
	}

	<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__popup__fields-button, <?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__image{
		display: inline-block;
	}

	<?php }?>
</style>
<?php if (!$edition) { ?>
	<script type="text/javascript">
        function acym_closePopupform<?php echo $form->form_tag_name;?>(element) {
            element.style.display = 'none';

            let exdate = new Date();
            exdate.setDate(exdate.getDate() + 1);
            document.cookie = 'acym_form_<?php echo $form->id;?>=' + Date.now() + ';expires=' + exdate.toUTCString();

        }

        document.querySelector('.acym__subscription__form__popup__overlay').addEventListener('click', function (event) {
            if (event.target.closest('.acym__subscription__form__popup') === null) {
                acym_closePopupform<?php echo $form->form_tag_name;?>(this);
            }
        });
        document.querySelector('.acym__subscription__form__popup__close').addEventListener('click', function (event) {
            acym_closePopupform<?php echo $form->form_tag_name;?>(event.target.closest('.acym__subscription__form__popup__overlay'));
        });

        setTimeout(function () {
            let acym_popupForm = document.querySelector('.acym__subscription__form__popup__overlay');
            if (acym_popupForm !== null) {
                acym_popupForm.style.display = 'inline';
            }
        }, <?php echo $form->delay * 1000;?>);

	</script>
<?php } ?>
<?php if (!$edition) include acym_getPartial('forms', 'cookie'); ?>
