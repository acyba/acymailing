<div class="acym__subscription__form__button">
    <?php
    if (empty($form->settings['button']['text'])) {
        $form->settings['button']['text'] = 'ACYM_SUBSCRIBE';
    }

    if (acym_isMultilingual()) {
        $defaultLanguage = $this->config->get('multilingual_default');
        $currentLanguageTag = acym_getLanguageTag();

        if (!empty($form->settings['button']['lang'][$currentLanguageTag])) {
            $form->settings['button']['text'] = $form->settings['button']['lang'][$currentLanguageTag];
        } elseif (!empty($form->settings['button']['lang'][$defaultLanguage])) {
            $form->settings['button']['text'] = $form->settings['button']['lang'][$defaultLanguage];
        }
    }

    $onclick = $edition ? ''
        : 'onclick="try{ return submitAcymForm(\'subscribe\',\''.$form->form_tag_name.'\', \'acymSubmitSubForm\'); }catch(err){alert(\'The form could not be submitted \'+err);return false;}"';
    ?>
	<button type="button" <?php echo $onclick; ?>>
        <?php echo $button_value = acym_translation($form->settings['button']['text']); ?>
	</button>
	<style>
		<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__button{
			display: flex;
			justify-content: center;
			align-items: center
		}

		<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__button button{
			background-color: <?php echo $form->settings['button']['background_color']; ?>;
			color: <?php echo $form->settings['button']['text_color']; ?>;
			border-width: <?php echo $form->settings['button']['border_size']; ?>px;
			border-style: <?php echo $form->settings['button']['border_type']; ?>;
			border-color: <?php echo $form->settings['button']['border_color']; ?>;
			border-radius: <?php echo $form->settings['button']['border_radius']; ?>px;
			padding: <?php echo $form->settings['button']['size']['height']; ?>px <?php echo $form->settings['button']['size']['width']; ?>px;
		}
	</style>
</div>
