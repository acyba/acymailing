<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.
// context verification
?>
<div id="acym_fulldiv_<?php echo acym_escape($form->form_tag_name); ?>" class="acym__subscription__form__footer acym__subscription__form-erase">
    <?php
    if ($edition) {
        echo '<form action="#" onsubmit="return false;" id="'.acym_escape($form->form_tag_name).'">';
    } else {
        $cookieExpiration = empty($form->settings['cookie']['cookie_expiration']) ? '1' : $form->settings['cookie']['cookie_expiration'];
        echo '<form acym-data-id="'.acym_escape($form->id).'" 
			acym-data-cookie="'.intval($cookieExpiration).'" 
			action="'.acym_escapeUrl($form->form_tag_action).'" 
			id="'.acym_escape($form->form_tag_name).'" 
			name="'.acym_escape($form->form_tag_name).'" 
			enctype="multipart/form-data" 
			onsubmit="return submitAcymForm(\'subscribe\',\''.acym_escape($form->form_tag_name).'\', \'acymSubmitSubForm\')">';
    }
    $files = [
        0 => $form->settings['style']['position'] == 'button-right' ? 'fields' : 'button',
        1 => $form->settings['style']['position'] == 'button-right' ? 'button' : 'fields',
    ];

    include acym_getPartial('forms', $files[0]);
    include acym_getPartial('forms', $files[1]);
    include acym_getPartial('forms', 'hidden_params');

    echo '</form>';
    ?>
</div>
<style>
	<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name); ?>.acym__subscription__form__footer{
		position: fixed;
		bottom: 0;
		right: 0;
		left: 0;
		height: <?php echo acym_escapeHtml($form->settings['style']['size']['height']); ?>px;
		background-color: <?php echo acym_escapeHtml($form->settings['style']['background_color']); ?>;
		color: <?php echo acym_escapeHtml($form->settings['style']['text_color']); ?> !important;
		padding: .5rem;
		z-index: 999999;
		text-align: center;
		display: flex;
		justify-content: center;
		align-items: center
	}

	<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name); ?>.acym__subscription__form__footer .responseContainer{
		margin-bottom: 0 !important;
		padding: .4rem !important;
	}

	<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name); ?>.acym__subscription__form__footer <?php echo '#'.acym_escapeHtml($form->form_tag_name); ?>{
		margin: 0;
		display: flex;
		justify-content: center;
		align-items: center
	}

	<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name); ?>.acym__subscription__form__footer .acym__subscription__form__fields, <?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name); ?>.acym__subscription__form__footer .acym__subscription__form__button{
		display: flex;
		justify-content: center;
		align-items: center
	}

	<?php echo '#acym_fulldiv_'.acym_escapeHtml($form->form_tag_name); ?>
	.acym__users__creation__fields__title{
		margin: 0.5rem
	}
</style>
<?php if (!$edition) include acym_getPartial('forms', 'cookie'); ?>
