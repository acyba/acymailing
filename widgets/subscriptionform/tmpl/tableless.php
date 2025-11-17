<?php

// Prepare list display
use AcyMailing\Helpers\CaptchaHelper;

$listsContent = '';
if (!empty($visibleLists)) {
    $listsContent .= '<div class="acym_lists">';
    foreach ($visibleLists as $myListId) {
        $check = '';
        if (in_array($myListId, $checkedLists)) {
            $check = 'checked="checked"';
        }

        $listsContent .= '
            <div class="onelist">
            	<input type="checkbox" class="acym_checkbox" name="subscription[]" id="acylist_'.$myListId.'_'.$formName.'" '.$check.' value="'.$myListId.'"/>
                <label for="acylist_'.$myListId.'_'.$formName.'">'.(!empty($allLists[$myListId]->display_name) ? $allLists[$myListId]->display_name : $allLists[$myListId]->name).'</label>
            </div>';
    }
    $listsContent .= '</div>';
}
if ($listPosition == 'before') echo $listsContent;
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
        echo '<div class="onefield fieldacy'.$field->id.' acyfield_'.$field->type.'" id="field_'.$field->id.'">';
        echo $fieldClass->displayField($field, $field->default_value, $valuesArray, $displayOutside, true, $identifiedUser);
        echo '</div>';

        if ($field->id == 2 && $config->get('email_confirmation')) {
            echo $fieldClass->setEmailConfirmationField($displayOutside, $field);
        }
    }

    if ($listPosition != 'before') echo $listsContent;

    if (empty($identifiedUser->id) && $config->get('captcha', 'none') !== 'none' && acym_level(ACYM_ESSENTIAL)) {
        echo '<div class="onefield fieldacycaptcha" id="field_captcha_'.$formName.'">';
        $captcha = new CaptchaHelper();
        echo $captcha->display($formName);
        echo '</div>';
    }

    if (!empty($termslink)) {
        echo '<div class="onefield fieldacyterms" id="field_terms_'.$formName.'">';
        echo '<label for="mailingdata_terms_'.$formName.'">';
        echo '<input id="mailingdata_terms_'.$formName.'" class="checkbox" type="checkbox" name="terms" title="'.acym_translation('ACYM_TERMS_CONDITIONS').'"/> '.$termslink;
        echo '</label>';
        echo '</div>';
    }
    ?>
</div>

<p class="acysubbuttons">
	<noscript>
        <?php echo acym_translation('ACYM_NO_JAVASCRIPT'); ?>
	</noscript>
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
			onclick="<?php echo acym_escape($onclickSubscribe); ?>">
        <?php echo acym_escape(acym_translation($subscribeText)); ?>
	</button>
    <?php if ($unsubButton === '2' || ($unsubButton === '1' && !empty($countUnsub))) { ?>
		<button type="submit"
				class="btn button unsubbutton"
				onclick="<?php echo acym_escape($onclickUnsubscribe); ?>">
            <?php echo acym_escape(acym_translation($unsubscribeText)); ?>
		</button>
    <?php } ?>
</p>
