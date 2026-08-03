<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View file, its variables are local to the include scope, not true globals.

defined('ABSPATH') || die('Restricted Access');

// Prepare list display
use AcyMailing\Helpers\CaptchaHelper;
use AcyMailing\Helpers\SecurityHelper;

if (!function_exists('acymailing_displayWidgetLists')) {
    function acymailing_displayWidgetLists($allLists, $visibleLists, $checkedLists, $formName)
    {
        if (empty($visibleLists)) {
            return;
        }
        ?>

		<div class="acym_lists">
            <?php foreach ($visibleLists as $myListId) { ?>
				<div class="onelist">
					<input type="checkbox"
					       class="acym_checkbox"
					       name="subscription[]"
					       id="acylist_<?php echo esc_attr($myListId.'_'.$formName); ?>"
                        <?php checked(in_array($myListId, $checkedLists)); ?>
						   value="<?php echo esc_attr($myListId); ?>" />
					<label for="acylist_<?php echo esc_attr($myListId.'_'.$formName); ?>">
                        <?php echo esc_html(!empty($allLists[$myListId]->display_name) ? $allLists[$myListId]->display_name : $allLists[$myListId]->name); ?>
					</label>
				</div>
            <?php } ?>
		</div>
        <?php
    }
}
if ($listPosition === 'before') {
    acymailing_displayWidgetLists($allLists, $visibleLists, $checkedLists, $formName);
}
?>

<div class="acym_form">
    <?php
    foreach ($fields as $field) {
        $field->option = !empty($field->option) ? json_decode($field->option) : new stdClass();
        $fieldDB = empty($field->option->fieldDB) ? '' : json_decode($field->option->fieldDB);
        $field->value = empty($field->value) ? '' : json_decode($field->value);
        $valuesArray = [];
        if (!empty($field->value)) {
            foreach ($field->value as $value) {
                $valueTmp = new stdClass();
                $valueTmp->text = $value->title;
                $valueTmp->value = $value->value;
                if ($value->disabled == 'y') $valueTmp->disable = true;
                $valuesArray[$value->value] = $valueTmp;
            }
        }
        if (!empty($fieldDB) && !empty($fieldDB->value)) {
            $fromDB = $fieldClass->getValueFromDB($fieldDB);
            foreach ($fromDB as $value) {
                $valuesArray[$value->value] = $value->title;
            }
        }
        echo '<div class="onefield fieldacy'.esc_attr($field->id).' acyfield_'.esc_attr($field->type).'" id="field_'.esc_attr($field->id).'">';
        $fieldClass->displayField($field, $field->default_value, $valuesArray, $displayOutside, true, $identifiedUser);
        echo '</div>';

        if ($field->id == 2 && $config->get('email_confirmation')) {
            $fieldClass->setEmailConfirmationField($displayOutside, $field);
        }
    }

    if ($listPosition !== 'before') {
        acymailing_displayWidgetLists($allLists, $visibleLists, $checkedLists, $formName);
    }

    if (empty($identifiedUser->id) && $config->get('captcha', 'none') !== 'none' && acym_level(ACYM_ESSENTIAL)) {
        echo '<div class="onefield fieldacycaptcha" id="field_captcha_'.esc_attr($formName).'">';
        $captcha = new CaptchaHelper();
        $captcha->display($formName);
        echo '</div>';
    }

    if (!empty($termslink)) {
        echo '<div class="onefield fieldacyterms" id="field_terms_'.esc_attr($formName).'">';
        echo '<label for="mailingdata_terms_'.esc_attr($formName).'">';
        echo '<input id="mailingdata_terms_'.esc_attr($formName).'" class="checkbox" type="checkbox" name="terms" title="'.esc_attr(
                acym_translation(
                    'ACYM_TERMS_CONDITIONS'
                )
            ).'"/> ';
        echo wp_kses(
            $termslink,
            SecurityHelper::ALLOWED_HTML_TERMS
        );
        echo '</label>';
        echo '</div>';
    }

    if (!empty($showTrackingConsent) && empty($identifiedUser->id)) {
        echo '<div class="onefield fieldacytracking" id="field_tracking_'.esc_attr($formName).'">';
        echo '<label for="mailingdata_tracking_'.esc_attr($formName).'">';
        echo '<input type="hidden" name="user[tracking]" value="0"/>';
        echo '<input id="mailingdata_tracking_'.esc_attr($formName).'" class="acym_checkbox" type="checkbox" name="user[tracking]" value="1"/> '.esc_html(
                acym_translation('ACYM_TRACKING_CONSENT')
            );
        echo '</label>';
        echo '</div>';
    }
    ?>
</div>

<p class="acysubbuttons">
	<noscript><?php echo esc_html(acym_translation('ACYM_NO_JAVASCRIPT')); ?></noscript>
    <?php
    $onclickSubscribe = 'try{ return submitAcymForm("subscribe","'.$formName.'", "acymSubmitSubForm"); }catch(err){alert("The form could not be submitted "+err);return false;}';
    $onclickUnsubscribe = 'try{ return submitAcymForm("unsubscribe","'.$formName.'", "acymSubmitSubForm"); }catch(err){alert("The form could not be submitted "+err);return false;}';

    if ($disableButtons) {
        $onclickSubscribe = 'return false;';
        $onclickUnsubscribe = 'return false;';
    }
    ?>
	<button type="submit"
	        class="btn btn-primary button subbutton"
	        onclick="<?php echo esc_attr($onclickSubscribe); ?>"><?php echo esc_html(acym_translation($subscribeText)); ?>
	</button>
    <?php if ($unsubButton === '2' || ($unsubButton === '1' && !empty($countUnsub))) { ?>
		<button type="submit"
		        class="btn button unsubbutton"
		        onclick="<?php echo esc_attr($onclickUnsubscribe); ?>"><?php echo esc_html(acym_translation($unsubscribeText)); ?>
		</button>
    <?php } ?>
</p>
