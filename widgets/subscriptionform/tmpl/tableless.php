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
                <label for="acylist_'.$myListId.'_'.$formName.'">'.$allLists[$myListId]->name.'</label>
            </div>';
    }
    $listsContent .= '</div>';
}
if ($listPosition == 'before') echo $listsContent;
?>

<div class="acym_form">
    <?php
    foreach ($fields as $field) {
        $fieldDB = empty($field->option->fieldDB) ? '' : json_decode($field->option->fieldDB);
        $field->value = empty($field->value) ? '' : json_decode($field->value);
        $field->option = json_decode($field->option);
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
        $size = empty($field->option->size) ? '' : 'width:'.$field->option->size.'px';
        echo '<div class="onefield fieldacy'.$field->id.' acyfield_'.$field->type.'" id="field_'.$field->id.'">';
        echo $fieldClass->displayField($field, $field->default_value, $size, $valuesArray, $displayOutside, true, $identifiedUser);
        echo '</div>';
    }

    if ($listPosition != 'before') echo $listsContent;

    if (empty($identifiedUser->id) && $config->get('captcha', '') == 1) {
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
<div class="onefield fieldacycaptcha">
    <?php echo acym_translation('ACYM_NO_JAVASCRIPT'); ?>
</div>
</noscript>
<input type="button"
	   class="btn btn-primary button subbutton"
	   value="<?php echo acym_translation($subscribeText, true); ?>"
	   name="Submit"
	   onclick="try{ return submitAcymForm('subscribe','<?php echo $formName; ?>', 'acymSubmitSubForm'); }catch(err){alert('The form could not be submitted '+err);return false;}" />
<?php if ($unsubButton === '2' || ($unsubButton === '1' && !empty($countUnsub))) { ?>
	<span style="display: none;"></span>
	<input type="button"
		   class="btn button unsubbutton"
		   value="<?php echo acym_translation($unsubscribeText, true); ?>"
		   name="Submit"
		   onclick="try{ return submitAcymForm('unsubscribe','<?php echo $formName; ?>', 'acymSubmitSubForm'); }catch(err){alert('The form could not be submitted '+err);return false;}" />
<?php } ?>
</p>
