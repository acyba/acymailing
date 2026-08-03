<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
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
    ?>
	<button type="submit">
        <?php echo acym_escapeHtml(acym_translation($form->settings['button']['text'])); ?>
	</button>
	<style>
		<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name).' '; ?>.acym__subscription__form__button{
			display: flex;
			justify-content: center;
			align-items: center
		}

		<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name).' '; ?>.acym__subscription__form__button button{
			background-color: <?php echo acym_escapeHtml($form->settings['button']['background_color']); ?>;
			color: <?php echo acym_escapeHtml($form->settings['button']['text_color']); ?>;
			border-width: <?php echo acym_escapeHtml($form->settings['button']['border_size']); ?>px;
			border-style: <?php echo acym_escapeHtml($form->settings['button']['border_type']); ?>;
			border-color: <?php echo acym_escapeHtml($form->settings['button']['border_color']); ?>;
			border-radius: <?php echo acym_escapeHtml($form->settings['button']['border_radius']); ?>px;
			padding: <?php echo acym_escapeHtml($form->settings['button']['size']['height']); ?>px <?php echo acym_escapeHtml($form->settings['button']['size']['width']); ?>px;
		}
	</style>
</div>
