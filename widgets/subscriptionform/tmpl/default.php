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

		<table class="acym_lists">
            <?php foreach ($visibleLists as $myListId) { ?>
				<tr>
					<td>
						<input type="checkbox"
						       class="acym_checkbox"
						       name="subscription[]"
						       id="acylist_<?php echo esc_attr($myListId.'_'.$formName); ?>"
                            <?php checked(in_array($myListId, $checkedLists)); ?>
							   value="<?php echo esc_attr($myListId); ?>" />
						<label for="acylist_<?php echo esc_attr($myListId.'_'.$formName); ?>">
                            <?php echo esc_html(!empty($allLists[$myListId]->display_name) ? $allLists[$myListId]->display_name : $allLists[$myListId]->name); ?>
						</label>
					</td>
				</tr>
            <?php } ?>
		</table>
        <?php
    }
}
if ($listPosition === 'before') {
    acymailing_displayWidgetLists($allLists, $visibleLists, $checkedLists, $formName);
}
?>

<table class="acym_form">
	<tr>
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

            echo '<td class="onefield acyfield_'.esc_attr($field->id).' acyfield_'.esc_attr($field->type).'">';
            $fieldClass->displayField($field, $field->default_value, $valuesArray, $displayOutside, true, $identifiedUser);
            echo '</td>';
            if (!$displayInline) {
                echo '</tr><tr>';
            }

            if ($field->id == 2 && $config->get('email_confirmation')) {
                $fieldClass->setEmailConfirmationField($displayOutside, $field, 'td', $displayInline);
            }
        }

        if ($listPosition !== 'before') {
            echo '<td>';
            acymailing_displayWidgetLists($allLists, $visibleLists, $checkedLists, $formName);
            echo '</td>';
            if (!$displayInline) {
                echo '</tr><tr>';
            }
        }

        if (empty($identifiedUser->id) && $config->get('captcha', 'none') !== 'none' && acym_level(ACYM_ESSENTIAL)) {
            echo '<td class="captchakeymodule" '.($displayOutside && !$displayInline ? 'colspan="2"' : '').'>';
            $captcha = new CaptchaHelper();
            $captcha->display($formName);
            echo '</td>';
            if (!$displayInline) {
                echo '</tr><tr>';
            }
        }

        if (!empty($termslink)) {
            echo '<td class="acyterms" '.($displayOutside && !$displayInline ? 'colspan="2"' : '').'>';
            echo '<input id="mailingdata_terms_'.esc_attr($formName).'" class="checkbox" type="checkbox" name="terms" title="'.esc_attr(
                    acym_translation('ACYM_TERMS_CONDITIONS')
                ).'"/> ';
            echo wp_kses(
                $termslink,
                SecurityHelper::ALLOWED_HTML_TERMS
            );
            echo '</td>';
            if (!$displayInline) {
                echo '</tr><tr>';
            }
        }

        if (!empty($showTrackingConsent)) {
            $trackingChecked = (int)($identifiedUser->tracking ?? 0) === 1;
            echo '<td class="acytracking" '.($displayOutside && !$displayInline ? 'colspan="2"' : '').'>';
            echo '<input type="hidden" name="user[tracking]" value="0"/>';
            echo '<input id="mailingdata_tracking_'.esc_attr($formName).'" class="checkbox" type="checkbox" name="user[tracking]" value="1" '.acym_checked($trackingChecked, true, false).'/> '.esc_html(
                    acym_translation('ACYM_TRACKING_CONSENT')
                );
            echo '</td>';
            if (!$displayInline) {
                echo '</tr><tr>';
            }
        }
        ?>

		<td <?php if ($displayOutside && !$displayInline) echo 'colspan="2"'; ?> class="acysubbuttons">
			<noscript><?php echo esc_html(acym_translation('ACYM_NO_JAVASCRIPT')); ?></noscript>
            <?php
            $onclickSubscribe = 'try{ return submitAcymForm("subscribe","'.$formName.'"); }catch(err){alert("The form could not be submitted "+err);return false;}';
            $onclickUnsubscribe = 'try{ return submitAcymForm("unsubscribe","'.$formName.'"); }catch(err){alert("The form could not be submitted "+err);return false;}';
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
		</td>
	</tr>
</table>
