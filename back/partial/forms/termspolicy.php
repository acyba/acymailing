<?php
$termsURL = acym_getArticleURL(
    $form->termspolicy_options['termscond'],
    0,
    'ACYM_TERMS_CONDITIONS'
);
$privacyURL = acym_getArticleURL(
    $form->termspolicy_options['privacy'],
    0,
    'ACYM_PRIVACY_POLICY'
);

if (empty($termsURL) && empty($privacyURL)) {
    $termslink = '';
} elseif (empty($privacyURL)) {
    $termslink = acym_translationSprintf('ACYM_I_AGREE_TERMS', $termsURL);
} elseif (empty($termsURL)) {
    $termslink = acym_translationSprintf('ACYM_I_AGREE_PRIVACY', $privacyURL);
} else {
    $termslink = acym_translationSprintf('ACYM_I_AGREE_BOTH', $termsURL, $privacyURL);
}

if (!empty($termslink)) {
    echo '<div class="acym__subscription__form__termscond">';
    echo '<div class="onefield fieldacyterms" id="field_terms_'.$form->form_tag_name.'">';
    echo '<label for="mailingdata_terms_'.$form->form_tag_name.'">';
    echo '<input id="mailingdata_terms_'.$form->form_tag_name.'" class="checkbox" type="checkbox" name="terms" title="'.acym_translation('ACYM_TERMS_CONDITIONS').'"/> '.$termslink;
    echo '</label>';
    echo '</div>';
    ?>

	<style>
		.acym__subscription__form__header .acym__subscription__form__termscond,
		.acym__subscription__form__footer .acym__subscription__form__termscond{
			max-width: 250px;
		}

		<?php echo '#acym_fulldiv_'.$form->form_tag_name.' '; ?>.acym__subscription__form__fields .acym__subscription__form__termscond input[type="checkbox"]{
			margin-top: 0 !important;
		}
	</style>
	</div>
<?php } ?>
