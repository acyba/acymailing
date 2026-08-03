<?php
// context verification
?>
<div id="acym_fulldiv_<?php echo acym_escape($form->form_tag_name); ?>" class="acym__subscription__form__shortcode acym__subscription__form-erase">
    <?php
    if ($edition) {
        echo '<form action="#" onsubmit="return false;" id="'.acym_escape($form->form_tag_name).'">';
    } else {
        echo '<form action="'.acym_escapeUrl($form->form_tag_action).'" id="'.acym_escape($form->form_tag_name).'" name="'.acym_escape(
                $form->form_tag_name
            ).'" enctype="multipart/form-data" onsubmit="return submitAcymForm(\'subscribe\',\''.acym_escape($form->form_tag_name).'\', \'acymSubmitSubForm\')">';
    }
    include acym_getPartial('forms', 'fields');
    include acym_getPartial('forms', 'button');
    include acym_getPartial('forms', 'hidden_params');
    ?>
	</form>
</div>
<style>
	<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name); ?>.acym__subscription__form__shortcode{
		height: <?php echo acym_escapeHtml($form->settings['style']['size']['height']); ?>px;
		max-width: <?php echo acym_escapeHtml($form->settings['style']['size']['width']); ?>px;
		background-color: <?php echo acym_escapeHtml($form->settings['style']['background_color']); ?>;
		color: <?php echo acym_escapeHtml($form->settings['style']['text_color']); ?> !important;
		padding: .5rem;
		text-align: center;
		display: flex;
		justify-content: center;
		align-items: center;
		margin: 1rem auto;
	}

	<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name); ?>.acym__subscription__form__shortcode .responseContainer{
		margin-bottom: 0 !important;
		padding: .4rem !important;
	}

	<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name); ?>.acym__subscription__form__shortcode <?php echo '#'.acym_escapeHtml($form->form_tag_name); ?>{
		margin: 0;
	}

	<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name); ?>.acym__subscription__form__shortcode .acym__subscription__form__fields, <?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name); ?>.acym__subscription__form__shortcode .acym__subscription__form__button{
		display: block;
		width: 100%;
		margin: 1rem 0 !important;
	}

	<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name); ?>.acym__subscription__form__shortcode .acym__subscription__form__fields .acym__subscription__form__lists{
		display: block;
		width: 100%;
		margin: 1rem 10px !important;
	}

	<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name); ?>.acym__subscription__form__shortcode .acym__subscription__form__fields > *:not(style){
		display: block;
	}

	<?php if (!empty($form->settings['style']['position']) && in_array($form->settings['style']['position'], ['image-right', 'image-left'])) { ?>
	<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name); ?>.acym__subscription__form__shortcode <?php echo '#'.acym_escapeHtml($form->form_tag_name); ?>{
		display: flex;
		justify-content: center;
		align-items: center
	}

	<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name).' '; ?>.acym__subscription__form__shortcode__fields-button, <?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name).' '; ?>.acym__subscription__form__image{
		display: inline-block;
	}

	<?php } ?>
</style>
