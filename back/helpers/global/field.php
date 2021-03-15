<?php

/**
 * @param array   $options    It is either an array with value => label or an array of objects
 * @param         $name
 * @param null    $selected
 * @param null    $id
 * @param array   $attributes The html attributes for the inputs
 * @param bool    $params     Special parameters
 *
 * @return string A formatted radio button
 */
function acym_radio($options, $name, $selected = null, $attributes = [], $params = [], $frontDisplay = false)
{
    $id = preg_replace(
        '#[^a-zA-Z0-9_]+#mi',
        '_',
        str_replace(
            ['[', ']'],
            ['_', ''],
            empty($params['id']) ? $name : $params['id']
        )
    );

    $objValue = empty($params['objectValue']) ? 'value' : $params['objectValue'];
    $objText = empty($params['objectText']) ? 'text' : $params['objectText'];

    $attributes['type'] = 'radio';
    $attributes['name'] = $name;
    if (empty($params['containerClass'])) $params['containerClass'] = '';

    $return = '<div class="acym_radio_group '.$params['containerClass'].'">';
    $k = 0;
    foreach ($options as $value => $label) {
        if (is_object($label)) {
            if (!empty($label->class)) {
                $attributes['class'] = $label->class;
            }

            $value = $label->$objValue;
            $label = $label->$objText;
        }

        $currentId = empty($params['useIncrement']) ? $id.$value : $id.$k;

        $attributes['value'] = $value;
        $attributes['id'] = $currentId;

        if (isset($attributes['related'][$value])) {
            $attributes['acym-data-related'] = $attributes['related'][$value];
        }

        $checked = (string)$value == (string)$selected ? ' checked="checked"' : '';

        $formattedAttributes = '';
        foreach ($attributes as $attribute => $val) {
            if ($attribute === 'related') continue;
            $formattedAttributes .= ' '.$attribute.'="'.acym_escape($val).'"';
        }
        if (!empty($params['required'])) {
            $formattedAttributes .= ' required';
            unset($params['required']);
        }

        if (!$frontDisplay) {
            $return .= '<i data-radio="'.$currentId.'" class="acymicon-radio_button_checked acym_radio_checked"></i>';
            $return .= '<i data-radio="'.$currentId.'" class="acymicon-radio_button_unchecked acym_radio_unchecked"></i>';
        }
        $return .= '<input'.$formattedAttributes.$checked.' />';
        $return .= '<label for="'.$currentId.'" id="'.$currentId.'-lbl">'.acym_translation($label).'</label>';

        if (!empty($params['pluginMode'])) $return .= '<br />';
        $k++;
    }
    $return .= '</div>';

    return $return;
}

function acym_boolean($name, $selected = null, $id = null, $attributes = [], $yes = 'ACYM_YES', $no = 'ACYM_NO')
{
    $options = [
        '1' => acym_translation($yes),
        '0' => acym_translation($no),
    ];

    $params = ['id' => $id];
    if (!empty($attributes['containerClass'])) {
        $params['containerClass'] = $attributes['containerClass'];
        unset($attributes['containerClass']);
    }

    return acym_radio(
        $options,
        $name,
        $selected ? 1 : 0,
        $attributes,
        $params,
        !acym_isAdmin()
    );
}

function acym_select($data, $name, $selected = null, $attribs = null, $optKey = 'value', $optText = 'text', $idtag = false, $translate = false)
{
    $idtag = str_replace(['[', ']', ' '], '', empty($idtag) ? $name : $idtag);

    $attributes = '';
    if (!empty($attribs)) {
        if (is_array($attribs)) {
            foreach ($attribs as $attribName => $attribValue) {
                if (is_array($attribValue) || is_object($attribValue)) $attribValue = json_encode($attribValue);
                $attribName = str_replace([' ', '"', "'"], '_', $attribName);
                $attributes .= ' '.$attribName.'="'.acym_escape($attribValue).'"';
            }
        } else {
            $attributes = $attribs;
        }
    }

    $dropdown = '<select id="'.acym_escape($idtag).'" name="'.acym_escape($name).'" '.$attributes.'>';

    foreach ($data as $key => $oneOption) {
        $disabled = false;
        if (is_object($oneOption)) {
            $value = $oneOption->$optKey;
            $text = $oneOption->$optText;
            if (isset($oneOption->disable)) {
                $disabled = $oneOption->disable;
            }
        } else {
            $value = $key;
            $text = $oneOption;
        }

        if ($translate) {
            $text = acym_translation($text);
        }

        if (strtolower($value) == '<optgroup>') {
            $dropdown .= '<optgroup label="'.acym_escape($text).'">';
        } elseif (strtolower($value) == '</optgroup>') {
            $dropdown .= '</optgroup>';
        } else {
            $cleanValue = acym_escape($value);
            $cleanText = acym_escape($text);
            $dropdown .= '<option value="'.$cleanValue.'"'.($value == $selected ? ' selected="selected"' : '').($disabled ? ' disabled="disabled"' : '').'>'.$cleanText.'</option>';
        }
    }

    $dropdown .= '</select>';

    return $dropdown;
}

/**
 * @param array  $data     Can be in format $data[value] = text or $data[1] = object
 * @param string $name
 * @param array  $selected
 * @param array  $attribs  All attributes to add to the select in this format $data["class"] = "my_class"
 * @param string $optValue Value name identifier to access by $value = object->$optValue
 * @param string $optText  Text name identifier to access by $text = object->$optText
 * @param bool   $translate
 *
 * @return string
 */
function acym_selectMultiple($data, $name, $selected = [], $attribs = [], $optValue = 'value', $optText = 'text', $translate = false)
{
    if (substr($name, -2) !== '[]') {
        $name .= '[]';
    }

    $attribs['multiple'] = 'multiple';

    $dropdown = '<select name="'.acym_escape($name).'"';
    foreach ($attribs as $attribKey => $attribValue) {
        $dropdown .= ' '.$attribKey.'="'.addslashes($attribValue).'"';
    }
    $dropdown .= '>';

    foreach ($data as $oneDataKey => $oneDataValue) {
        $disabled = '';

        if (is_object($oneDataValue)) {
            $value = $oneDataValue->$optValue;
            $text = $oneDataValue->$optText;

            if (!empty($oneDataValue->disable)) {
                $disabled = ' disabled="disabled"';
            }
        } else {
            $value = $oneDataKey;
            $text = $oneDataValue;
        }

        if ($translate) {
            $text = acym_translation($text);
        }

        if (strtolower($value) == '<optgroup>') {
            $dropdown .= '<optgroup label="'.acym_escape($text).'">';
        } elseif (strtolower($value) == '</optgroup>') {
            $dropdown .= '</optgroup>';
        } else {
            $text = acym_escape($text);
            $dropdown .= '<option value="'.acym_escape($value).'"'.(in_array($value, $selected) ? ' selected="selected"' : '').$disabled.'>'.$text.'</option>';
        }
    }

    $dropdown .= '</select>';

    return $dropdown;
}

function acym_selectOption($value, $text = '', $optKey = 'value', $optText = 'text', $disable = false)
{
    $option = new stdClass();
    $option->$optKey = $value;
    $option->$optText = acym_translation($text);
    $option->disable = $disable;

    return $option;
}

/**
 * @param         $name
 * @param         $value
 * @param null    $label
 * @param array   $attrInput            as [attribute_name=>value]
 * @param string  $labelClass           css class for the label
 * @param string  $switchContainerClass css class for the switch container
 * @param string  $switchClass          css class for the switch
 * @param string  $toggle               show / hide the element with this ID depending on the switch
 * @param boolean $toggleOpen           show on switch or hide
 * @param string  $vModel               to add v-model for vue apps
 * @param bool    $disabled             to disable or not the switch
 * @param string  $disabledMessage      message showed in tooltip if disabled
 *
 * @return string
 */
function acym_switch($name, $value, $label = null, $attrInput = [], $labelClass = 'medium-6 small-9', $switchContainerClass = 'auto', $switchClass = '', $toggle = null, $toggleOpen = true, $vModel = '', $disabled = false, $disabledMessage = '')
{
    static $occurrence = 100;
    $occurrence++;

    $id = acym_escape('switch_'.$occurrence);
    $checked = $value == 1 ? 'checked="checked"' : '';

    $switch = '
    <div class="switch '.acym_escape($switchClass).'">
        <input type="hidden" name="'.acym_escape($name).'" data-switch="'.$id.'" value="'.acym_escape($value).'" '.$vModel;

    if (!empty($toggle)) {
        $switch .= ' data-toggle-switch="'.acym_escape($toggle).'" data-toggle-switch-open="'.($toggleOpen ? 'show' : 'hide').'"';
    }

    foreach ($attrInput as $oneAttributeName => $oneAttributeValue) {
        $switch .= ' '.$oneAttributeName.'="'.acym_escape($oneAttributeValue).'"';
    }
    $switch .= '>';
    $labelSwitchDisabled = !$disabled ? '' : ' disabled';
    $inputSwitchDisabled = !$disabled ? '' : ' disabled="disabled"';
    $disabledTooltip = !$disabled || empty($disabledMessage) ? '' : ' data-acym-tooltip="'.$disabledMessage.'"';
    $switch .= '
        <input class="switch-input" type="checkbox" id="'.$id.'" value="1" '.$checked.$inputSwitchDisabled.'>
        <label class="switch-paddle switch-label'.$labelSwitchDisabled.'" '.$disabledTooltip.' for="'.$id.'">
            <span class="switch-active" aria-hidden="true">1</span>
            <span class="switch-inactive" aria-hidden="true">0</span>
        </label>
    </div>';

    if (!empty($label)) {
        $switch = '<label for="'.$id.'" class="cell '.$labelClass.' switch-label">'.$label.'</label><div class="cell '.$switchContainerClass.'">'.$switch.'</div>';
    }

    return $switch;
}

function acym_generateCountryNumber($name, $defaultvalue = '')
{
    //We keep it if we want to change the dropdown
    $flagPosition = [];
    $flagPosition['93'] = ['x' => -48, 'y' => 0];
    $flagPosition['355'] = ['x' => -96, 'y' => 0];
    $flagPosition['213'] = ['x' => -160, 'y' => -33];
    $flagPosition['1684'] = ['x' => -176, 'y' => 0];
    $flagPosition['376'] = ['x' => -16, 'y' => 0];
    $flagPosition['244'] = ['x' => -144, 'y' => 0];
    $flagPosition['1264'] = ['x' => -80, 'y' => 0];
    $flagPosition['672'] = ['x' => 0, 'y' => -176]; //antartica
    $flagPosition['1268'] = ['x' => -64, 'y' => 0];
    $flagPosition['54'] = ['x' => -160, 'y' => 0];
    $flagPosition['374'] = ['x' => -112, 'y' => 0];
    $flagPosition['297'] = ['x' => -224, 'y' => 0];
    $flagPosition['247'] = ['x' => -16, 'y' => -176]; //ascenscion island
    $flagPosition['61'] = ['x' => -208, 'y' => 0];
    $flagPosition['43'] = ['x' => -192, 'y' => 0];
    $flagPosition['994'] = ['x' => -240, 'y' => 0];
    $flagPosition['1242'] = ['x' => -208, 'y' => -11];
    $flagPosition['973'] = ['x' => -96, 'y' => -11];
    $flagPosition['880'] = ['x' => -32, 'y' => -11];
    $flagPosition['1246'] = ['x' => -16, 'y' => -11];
    $flagPosition['375'] = ['x' => -16, 'y' => -22];
    $flagPosition['32'] = ['x' => -48, 'y' => -11];
    $flagPosition['501'] = ['x' => -32, 'y' => -22];
    $flagPosition['229'] = ['x' => -128, 'y' => -11];
    $flagPosition['1441'] = ['x' => -144, 'y' => -11];
    $flagPosition['975'] = ['x' => -224, 'y' => -11];
    $flagPosition['591'] = ['x' => -176, 'y' => -11];
    $flagPosition['387'] = ['x' => 0, 'y' => -11];
    $flagPosition['267'] = ['x' => 0, 'y' => -22];
    $flagPosition['55'] = ['x' => -192, 'y' => -11];
    $flagPosition['1284'] = ['x' => -240, 'y' => -154];
    $flagPosition['673'] = ['x' => -160, 'y' => -11];
    $flagPosition['359'] = ['x' => -80, 'y' => -11];
    $flagPosition['226'] = ['x' => -64, 'y' => -11];
    $flagPosition['257'] = ['x' => -112, 'y' => -11];
    $flagPosition['855'] = ['x' => -64, 'y' => -77];
    $flagPosition['237'] = ['x' => -192, 'y' => -22];
    $flagPosition['1'] = ['x' => -48, 'y' => -22];
    $flagPosition['238'] = ['x' => -16, 'y' => -33];
    $flagPosition['1345'] = ['x' => -192, 'y' => -77];
    $flagPosition['236'] = ['x' => -96, 'y' => -22];
    $flagPosition['235'] = ['x' => -112, 'y' => -143];
    $flagPosition['56'] = ['x' => -176, 'y' => -22];
    $flagPosition['86'] = ['x' => -208, 'y' => -22];
    $flagPosition['6724'] = ['x' => -32, 'y' => -176]; //christmas island
    $flagPosition['6722'] = ['x' => -48, 'y' => -176]; //coco keeling island
    $flagPosition['57'] = ['x' => -224, 'y' => -22];
    $flagPosition['269'] = ['x' => -96, 'y' => -77];
    $flagPosition['243'] = ['x' => -80, 'y' => -22];
    $flagPosition['242'] = ['x' => -112, 'y' => -22];
    $flagPosition['682'] = ['x' => -160, 'y' => -22];
    $flagPosition['506'] = ['x' => -240, 'y' => -22];
    $flagPosition['225'] = ['x' => -144, 'y' => -22];
    $flagPosition['385'] = ['x' => 0, 'y' => -66];
    $flagPosition['53'] = ['x' => 0, 'y' => -33];
    $flagPosition['357'] = ['x' => -48, 'y' => -33];
    $flagPosition['420'] = ['x' => -64, 'y' => -33];
    $flagPosition['45'] = ['x' => -112, 'y' => -33];
    $flagPosition['253'] = ['x' => -96, 'y' => -33];
    $flagPosition['1767'] = ['x' => -128, 'y' => -33];
    $flagPosition['1809'] = ['x' => -144, 'y' => -33];
    $flagPosition['593'] = ['x' => -176, 'y' => -33];
    $flagPosition['20'] = ['x' => -208, 'y' => -33];
    $flagPosition['503'] = ['x' => -32, 'y' => -143];
    $flagPosition['240'] = ['x' => -96, 'y' => -55];
    $flagPosition['291'] = ['x' => 0, 'y' => -44];
    $flagPosition['372'] = ['x' => -192, 'y' => -33];
    $flagPosition['251'] = ['x' => -32, 'y' => -44];
    $flagPosition['500'] = ['x' => -96, 'y' => -44];
    $flagPosition['298'] = ['x' => -128, 'y' => -44];
    $flagPosition['679'] = ['x' => -80, 'y' => -44];
    $flagPosition['358'] = ['x' => -64, 'y' => -44];
    $flagPosition['33'] = ['x' => -144, 'y' => -44];
    $flagPosition['596'] = ['x' => -80, 'y' => -99];
    $flagPosition['594'] = ['x' => -128, 'y' => -176]; //french guiana
    $flagPosition['689'] = ['x' => -224, 'y' => -110];
    $flagPosition['241'] = ['x' => -160, 'y' => -44];
    $flagPosition['220'] = ['x' => -48, 'y' => -55];
    $flagPosition['995'] = ['x' => -208, 'y' => -44];
    $flagPosition['49'] = ['x' => -80, 'y' => -33];
    $flagPosition['233'] = ['x' => 0, 'y' => -55];
    $flagPosition['350'] = ['x' => -16, 'y' => -55];
    $flagPosition['30'] = ['x' => -112, 'y' => -55];
    $flagPosition['299'] = ['x' => -32, 'y' => -55];
    $flagPosition['1473'] = ['x' => -192, 'y' => -44];
    $flagPosition['590'] = ['x' => -80, 'y' => -55];
    $flagPosition['1671'] = ['x' => -160, 'y' => -55];
    $flagPosition['502'] = ['x' => -144, 'y' => -55];
    $flagPosition['224'] = ['x' => -64, 'y' => -55];
    $flagPosition['245'] = ['x' => -176, 'y' => -55];
    $flagPosition['592'] = ['x' => -192, 'y' => -55];
    $flagPosition['509'] = ['x' => -16, 'y' => -66];
    $flagPosition['504'] = ['x' => -240, 'y' => -55];
    $flagPosition['852'] = ['x' => -208, 'y' => -55];
    $flagPosition['36'] = ['x' => -32, 'y' => -66];
    $flagPosition['354'] = ['x' => -192, 'y' => -66];
    $flagPosition['91'] = ['x' => -128, 'y' => -66];
    $flagPosition['62'] = ['x' => -64, 'y' => -66];
    $flagPosition['964'] = ['x' => -160, 'y' => -66];
    $flagPosition['98'] = ['x' => -176, 'y' => -66];
    $flagPosition['353'] = ['x' => -80, 'y' => -66];
    $flagPosition['972'] = ['x' => -96, 'y' => -66];
    $flagPosition['39'] = ['x' => -208, 'y' => -66];
    $flagPosition['1876'] = ['x' => -240, 'y' => -66];
    $flagPosition['81'] = ['x' => -16, 'y' => -77];
    $flagPosition['962'] = ['x' => 0, 'y' => -77];
    $flagPosition['254'] = ['x' => -32, 'y' => -77];
    $flagPosition['686'] = ['x' => -80, 'y' => -77];
    $flagPosition['3774'] = ['x' => -64, 'y' => -176]; //kosovo
    $flagPosition['965'] = ['x' => -176, 'y' => -77];
    $flagPosition['996'] = ['x' => -48, 'y' => -77];
    $flagPosition['856'] = ['x' => -224, 'y' => -77];
    $flagPosition['371'] = ['x' => -112, 'y' => -88];
    $flagPosition['961'] = ['x' => -240, 'y' => -77];
    $flagPosition['266'] = ['x' => -64, 'y' => -88];
    $flagPosition['231'] = ['x' => -48, 'y' => -88];
    $flagPosition['218'] = ['x' => -128, 'y' => -88];
    $flagPosition['423'] = ['x' => -16, 'y' => -88];
    $flagPosition['370'] = ['x' => -80, 'y' => -88];
    $flagPosition['352'] = ['x' => -96, 'y' => -88];
    $flagPosition['853'] = ['x' => -48, 'y' => -99];
    $flagPosition['389'] = ['x' => -240, 'y' => -88];
    $flagPosition['261'] = ['x' => -208, 'y' => -88];
    $flagPosition['265'] = ['x' => -176, 'y' => -99];
    $flagPosition['60'] = ['x' => -208, 'y' => -99];
    $flagPosition['960'] = ['x' => -160, 'y' => -99];
    $flagPosition['223'] = ['x' => 0, 'y' => -99];
    $flagPosition['356'] = ['x' => -128, 'y' => -99];
    $flagPosition['692'] = ['x' => -224, 'y' => -88];
    $flagPosition['222'] = ['x' => -96, 'y' => -99];
    $flagPosition['230'] = ['x' => -144, 'y' => -99];
    $flagPosition['52'] = ['x' => -192, 'y' => -99];
    $flagPosition['691'] = ['x' => -112, 'y' => -44];
    $flagPosition['373'] = ['x' => -176, 'y' => -88];
    $flagPosition['377'] = ['x' => -160, 'y' => -88];
    $flagPosition['976'] = ['x' => -32, 'y' => -99];
    $flagPosition['382'] = ['x' => -192, 'y' => -88];
    $flagPosition['1664'] = ['x' => -112, 'y' => -99];
    $flagPosition['212'] = ['x' => -144, 'y' => -88];
    $flagPosition['258'] = ['x' => -224, 'y' => -99];
    $flagPosition['95'] = ['x' => -16, 'y' => -99];
    $flagPosition['264'] = ['x' => -240, 'y' => -99];
    $flagPosition['674'] = ['x' => -128, 'y' => -110];
    $flagPosition['977'] = ['x' => -112, 'y' => -110];
    $flagPosition['31'] = ['x' => -80, 'y' => -110];
    $flagPosition['599'] = ['x' => -128, 'y' => 0];
    $flagPosition['687'] = ['x' => 0, 'y' => -110];
    $flagPosition['64'] = ['x' => -160, 'y' => -110];
    $flagPosition['505'] = ['x' => -64, 'y' => -110];
    $flagPosition['227'] = ['x' => -16, 'y' => -110];
    $flagPosition['234'] = ['x' => -48, 'y' => -110];
    $flagPosition['683'] = ['x' => -144, 'y' => -110];
    $flagPosition['6723'] = ['x' => -32, 'y' => -110];
    $flagPosition['850'] = ['x' => -128, 'y' => -77];
    $flagPosition['47'] = ['x' => -96, 'y' => -110];
    $flagPosition['968'] = ['x' => -176, 'y' => -110];
    $flagPosition['92'] = ['x' => -16, 'y' => -121];
    $flagPosition['680'] = ['x' => -80, 'y' => -176]; //palau
    $flagPosition['970'] = ['x' => -96, 'y' => -121];
    $flagPosition['507'] = ['x' => -192, 'y' => -110];
    $flagPosition['675'] = ['x' => -240, 'y' => -110];
    $flagPosition['595'] = ['x' => -144, 'y' => -121];
    $flagPosition['51'] = ['x' => -208, 'y' => -110];
    $flagPosition['63'] = ['x' => 0, 'y' => -121];
    $flagPosition['48'] = ['x' => -32, 'y' => -121];
    $flagPosition['351'] = ['x' => -112, 'y' => -121];
    $flagPosition['1787'] = ['x' => -80, 'y' => -121];
    $flagPosition['974'] = ['x' => -160, 'y' => -121];
    $flagPosition['262'] = ['x' => -144, 'y' => -176]; //reunion island
    $flagPosition['40'] = ['x' => -192, 'y' => -121];
    $flagPosition['7'] = ['x' => -224, 'y' => -121];
    $flagPosition['250'] = ['x' => -240, 'y' => -121];
    $flagPosition['1670'] = ['x' => -96, 'y' => -176]; //marianne
    $flagPosition['378'] = ['x' => -176, 'y' => -132];
    $flagPosition['239'] = ['x' => -16, 'y' => -143];
    $flagPosition['966'] = ['x' => 0, 'y' => -132];
    $flagPosition['221'] = ['x' => -192, 'y' => -132];
    $flagPosition['381'] = ['x' => -208, 'y' => -121];
    $flagPosition['248'] = ['x' => -32, 'y' => -132];
    $flagPosition['232'] = ['x' => -160, 'y' => -132];
    $flagPosition['65'] = ['x' => -96, 'y' => -132];
    $flagPosition['421'] = ['x' => -144, 'y' => -132];
    $flagPosition['386'] = ['x' => -128, 'y' => -132];
    $flagPosition['677'] = ['x' => -16, 'y' => -132];
    $flagPosition['252'] = ['x' => -208, 'y' => -132];
    $flagPosition['685'] = ['x' => -112, 'y' => -176]; //somoa
    $flagPosition['27'] = ['x' => -128, 'y' => -165];
    $flagPosition['82'] = ['x' => -144, 'y' => -77];
    $flagPosition['34'] = ['x' => -16, 'y' => -44];
    $flagPosition['94'] = ['x' => -32, 'y' => -88];
    $flagPosition['290'] = ['x' => -112, 'y' => -132];
    $flagPosition['1869'] = ['x' => -112, 'y' => -77];
    $flagPosition['1758'] = ['x' => 0, 'y' => -88];
    $flagPosition['508'] = ['x' => -48, 'y' => -121];
    $flagPosition['1784'] = ['x' => -208, 'y' => -154];
    $flagPosition['249'] = ['x' => -64, 'y' => -132];
    $flagPosition['597'] = ['x' => -240, 'y' => -132];
    $flagPosition['268'] = ['x' => -80, 'y' => -143];
    $flagPosition['46'] = ['x' => -80, 'y' => -132];
    $flagPosition['41'] = ['x' => -128, 'y' => -22];
    $flagPosition['963'] = ['x' => -64, 'y' => -143];
    $flagPosition['886'] = ['x' => -64, 'y' => -154];
    $flagPosition['992'] = ['x' => -176, 'y' => -143];
    $flagPosition['255'] = ['x' => -80, 'y' => -154];
    $flagPosition['66'] = ['x' => -160, 'y' => -143];
    $flagPosition['228'] = ['x' => -144, 'y' => -143];
    $flagPosition['690'] = ['x' => -192, 'y' => -143];
    $flagPosition['676'] = ['x' => 0, 'y' => -154];
    $flagPosition['1868'] = ['x' => -32, 'y' => -154];
    $flagPosition['216'] = ['x' => -240, 'y' => -143];
    $flagPosition['90'] = ['x' => -16, 'y' => -154];
    $flagPosition['993'] = ['x' => -224, 'y' => -143];
    $flagPosition['1649'] = ['x' => -96, 'y' => -143];
    $flagPosition['688'] = ['x' => -48, 'y' => -154];
    $flagPosition['256'] = ['x' => -112, 'y' => -154];
    $flagPosition['380'] = ['x' => -96, 'y' => -154];
    $flagPosition['971'] = ['x' => -32, 'y' => 0];
    $flagPosition['44'] = ['x' => -176, 'y' => -44];
    $flagPosition['598'] = ['x' => -160, 'y' => -154];
    $flagPosition['1 '] = ['x' => -144, 'y' => -154];
    $flagPosition['998'] = ['x' => -176, 'y' => -154];
    $flagPosition['678'] = ['x' => -32, 'y' => -165];
    $flagPosition['3966'] = ['x' => -192, 'y' => -154];
    $flagPosition['58'] = ['x' => -224, 'y' => -154];
    $flagPosition['84'] = ['x' => -16, 'y' => -165];
    $flagPosition['1340'] = ['x' => 0, 'y' => -165];
    $flagPosition['681'] = ['x' => -64, 'y' => -165];
    $flagPosition['967'] = ['x' => -96, 'y' => -165];
    $flagPosition['260'] = ['x' => -160, 'y' => -165];
    $flagPosition['263'] = ['x' => -176, 'y' => -165];
    $flagPosition[''] = ['x' => -160, 'y' => -176];


    //Display a dropdown with all country values...
    $country = [];
    $country['93'] = 'Afghanistan';
    $country['355'] = 'Albania';
    $country['213'] = 'Algeria';
    $country['1684'] = 'American Samoa';
    $country['376'] = 'Andorra';
    $country['244'] = 'Angola';
    $country['1264'] = 'Anguilla';
    $country['672'] = 'Antarctica';
    $country['1268'] = 'Antigua & Barbuda';
    $country['54'] = 'Argentina';
    $country['374'] = 'Armenia';
    $country['297'] = 'Aruba';
    $country['247'] = 'Ascension Island';
    $country['61'] = 'Australia';
    $country['43'] = 'Austria';
    $country['994'] = 'Azerbaijan';
    $country['1242'] = 'Bahamas';
    $country['973'] = 'Bahrain';
    $country['880'] = 'Bangladesh';
    $country['1246'] = 'Barbados';
    $country['375'] = 'Belarus';
    $country['32'] = 'Belgium';
    $country['501'] = 'Belize';
    $country['229'] = 'Benin';
    $country['1441'] = 'Bermuda';
    $country['975'] = 'Bhutan';
    $country['591'] = 'Bolivia';
    $country['387'] = 'Bosnia/Herzegovina';
    $country['267'] = 'Botswana';
    $country['55'] = 'Brazil';
    $country['1284'] = 'British Virgin Islands';
    $country['673'] = 'Brunei';
    $country['359'] = 'Bulgaria';
    $country['226'] = 'Burkina Faso';
    $country['257'] = 'Burundi';
    $country['855'] = 'Cambodia';
    $country['237'] = 'Cameroon';
    $country['1'] = 'Canada/USA';
    $country['238'] = 'Cape Verde Islands';
    $country['1345'] = 'Cayman Islands';
    $country['236'] = 'Central African Republic';
    $country['235'] = 'Chad Republic';
    $country['56'] = 'Chile';
    $country['86'] = 'China';
    $country['6724'] = 'Christmas Island';
    $country['6722'] = 'Cocos Keeling Island';
    $country['57'] = 'Colombia';
    $country['269'] = 'Comoros';
    $country['243'] = 'Congo Democratic Republic';
    $country['242'] = 'Congo, Republic of';
    $country['682'] = 'Cook Islands';
    $country['506'] = 'Costa Rica';
    $country['225'] = 'Cote D\'Ivoire';
    $country['385'] = 'Croatia';
    $country['53'] = 'Cuba';
    $country['357'] = 'Cyprus';
    $country['420'] = 'Czech Republic';
    $country['45'] = 'Denmark';
    $country['253'] = 'Djibouti';
    $country['1767'] = 'Dominica';
    $country['1809'] = 'Dominican Republic';
    $country['593'] = 'Ecuador';
    $country['20'] = 'Egypt';
    $country['503'] = 'El Salvador';
    $country['240'] = 'Equatorial Guinea';
    $country['291'] = 'Eritrea';
    $country['372'] = 'Estonia';
    $country['251'] = 'Ethiopia';
    $country['500'] = 'Falkland Islands';
    $country['298'] = 'Faroe Island';
    $country['679'] = 'Fiji Islands';
    $country['358'] = 'Finland';
    $country['33'] = 'France';
    $country['596'] = 'French Antilles/Martinique';
    $country['594'] = 'French Guiana';
    $country['689'] = 'French Polynesia';
    $country['241'] = 'Gabon Republic';
    $country['220'] = 'Gambia';
    $country['995'] = 'Georgia';
    $country['49'] = 'Germany';
    $country['233'] = 'Ghana';
    $country['350'] = 'Gibraltar';
    $country['30'] = 'Greece';
    $country['299'] = 'Greenland';
    $country['1473'] = 'Grenada';
    $country['590'] = 'Guadeloupe';
    $country['1671'] = 'Guam';
    $country['502'] = 'Guatemala';
    $country['224'] = 'Guinea Republic';
    $country['245'] = 'Guinea-Bissau';
    $country['592'] = 'Guyana';
    $country['509'] = 'Haiti';
    $country['504'] = 'Honduras';
    $country['852'] = 'Hong Kong';
    $country['36'] = 'Hungary';
    $country['354'] = 'Iceland';
    $country['91'] = 'India';
    $country['62'] = 'Indonesia';
    $country['964'] = 'Iraq';
    $country['98'] = 'Iran';
    $country['353'] = 'Ireland';
    $country['972'] = 'Israel';
    $country['39'] = 'Italy';
    $country['1876'] = 'Jamaica';
    $country['81'] = 'Japan';
    $country['962'] = 'Jordan';
    $country['254'] = 'Kenya';
    $country['686'] = 'Kiribati';
    $country['3774'] = 'Kosovo';
    $country['965'] = 'Kuwait';
    $country['996'] = 'Kyrgyzstan';
    $country['856'] = 'Laos';
    $country['371'] = 'Latvia';
    $country['961'] = 'Lebanon';
    $country['266'] = 'Lesotho';
    $country['231'] = 'Liberia';
    $country['218'] = 'Libya';
    $country['423'] = 'Liechtenstein';
    $country['370'] = 'Lithuania';
    $country['352'] = 'Luxembourg';
    $country['853'] = 'Macau';
    $country['389'] = 'Macedonia';
    $country['261'] = 'Madagascar';
    $country['265'] = 'Malawi';
    $country['60'] = 'Malaysia';
    $country['960'] = 'Maldives';
    $country['223'] = 'Mali Republic';
    $country['356'] = 'Malta';
    $country['692'] = 'Marshall Islands';
    $country['222'] = 'Mauritania';
    $country['230'] = 'Mauritius';
    $country['52'] = 'Mexico';
    $country['691'] = 'Micronesia';
    $country['373'] = 'Moldova';
    $country['377'] = 'Monaco';
    $country['976'] = 'Mongolia';
    $country['382'] = 'Montenegro';
    $country['1664'] = 'Montserrat';
    $country['212'] = 'Morocco';
    $country['258'] = 'Mozambique';
    $country['95'] = 'Myanmar (Burma)';
    $country['264'] = 'Namibia';
    $country['674'] = 'Nauru';
    $country['977'] = 'Nepal';
    $country['31'] = 'Netherlands';
    $country['599'] = 'Netherlands Antilles';
    $country['687'] = 'New Caledonia';
    $country['64'] = 'New Zealand';
    $country['505'] = 'Nicaragua';
    $country['227'] = 'Niger Republic';
    $country['234'] = 'Nigeria';
    $country['683'] = 'Niue Island';
    $country['6723'] = 'Norfolk';
    $country['850'] = 'North Korea';
    $country['47'] = 'Norway';
    $country['968'] = 'Oman Dem Republic';
    $country['92'] = 'Pakistan';
    $country['680'] = 'Palau Republic';
    $country['970'] = 'Palestine';
    $country['507'] = 'Panama';
    $country['675'] = 'Papua New Guinea';
    $country['595'] = 'Paraguay';
    $country['51'] = 'Peru';
    $country['63'] = 'Philippines';
    $country['48'] = 'Poland';
    $country['351'] = 'Portugal';
    $country['1787'] = 'Puerto Rico';
    $country['974'] = 'Qatar';
    $country['262'] = 'Reunion Island';
    $country['40'] = 'Romania';
    $country['7'] = 'Russia';
    $country['250'] = 'Rwanda Republic';
    $country['1670'] = 'Saipan/Mariannas';
    $country['378'] = 'San Marino';
    $country['239'] = 'Sao Tome/Principe';
    $country['966'] = 'Saudi Arabia';
    $country['221'] = 'Senegal';
    $country['381'] = 'Serbia';
    $country['248'] = 'Seychelles Island';
    $country['232'] = 'Sierra Leone';
    $country['65'] = 'Singapore';
    $country['421'] = 'Slovakia';
    $country['386'] = 'Slovenia';
    $country['677'] = 'Solomon Islands';
    $country['252'] = 'Somalia Republic';
    $country['685'] = 'Somoa';
    $country['27'] = 'South Africa';
    $country['82'] = 'South Korea';
    $country['34'] = 'Spain';
    $country['94'] = 'Sri Lanka';
    $country['290'] = 'St. Helena';
    $country['1869'] = 'St. Kitts';
    $country['1758'] = 'St. Lucia';
    $country['508'] = 'St. Pierre';
    $country['1784'] = 'St. Vincent';
    $country['249'] = 'Sudan';
    $country['597'] = 'Suriname';
    $country['268'] = 'Swaziland';
    $country['46'] = 'Sweden';
    $country['41'] = 'Switzerland';
    $country['963'] = 'Syria';
    $country['886'] = 'Taiwan';
    $country['992'] = 'Tajikistan';
    $country['255'] = 'Tanzania';
    $country['66'] = 'Thailand';
    $country['228'] = 'Togo Republic';
    $country['690'] = 'Tokelau';
    $country['676'] = 'Tonga Islands';
    $country['1868'] = 'Trinidad & Tobago';
    $country['216'] = 'Tunisia';
    $country['90'] = 'Turkey';
    $country['993'] = 'Turkmenistan';
    $country['1649'] = 'Turks & Caicos Island';
    $country['688'] = 'Tuvalu';
    $country['256'] = 'Uganda';
    $country['380'] = 'Ukraine';
    $country['971'] = 'United Arab Emirates';
    $country['44'] = 'United Kingdom';
    $country['598'] = 'Uruguay';
    //We add a space to be able to add it twice in the dropdown
    $country['1 '] = 'USA/Canada';
    $country['998'] = 'Uzbekistan';
    $country['678'] = 'Vanuatu';
    $country['3966'] = 'Vatican City';
    $country['58'] = 'Venezuela';
    $country['84'] = 'Vietnam';
    $country['1340'] = 'Virgin Islands (US)';
    $country['681'] = 'Wallis/Futuna Islands';
    $country['967'] = 'Yemen Arab Republic';
    $country['260'] = 'Zambia';
    $country['263'] = 'Zimbabwe';
    $country[''] = acym_translation('ACYM_PHONE_NOCOUNTRY');

    $countryCodeForSelect = [];

    foreach ($country as $key => $one) {
        $countryCodeForSelect[$key] = '+'.$key.' ('.$one.')';
    }

    return acym_select($countryCodeForSelect, $name, empty($defaultvalue) ? '' : $defaultvalue, 'class="acym__select__country acym__select"', 'value', 'text');
}

function acym_cancelButton($text = 'ACYM_CANCEL', $url = '', $class = 'button medium-6 large-shrink')
{
    if (empty($url)) $url = acym_completeLink(acym_getVar('cmd', 'ctrl').'&task=listing');

    return '<a href="'.$url.'" class="cell '.$class.' acym__button__cancel">'.acym_translation($text).'</a>';
}

function acym_tooltip($hoveredText, $textShownInTooltip, $classContainer = '', $titleShownInTooltip = '', $link = '', $classText = '')
{
    if (!empty($link)) {
        $hoveredText = '<a href="'.$link.'" title="'.acym_escape($titleShownInTooltip).'" target="_blank">'.$hoveredText.'</a>';
    }

    if (!empty($titleShownInTooltip)) {
        $titleShownInTooltip = '<span class="acym__tooltip__title">'.$titleShownInTooltip.'</span>';
    }

    return '<span class="acym__tooltip '.$classContainer.'"><span class="acym__tooltip__text '.$classText.'">'.$titleShownInTooltip.$textShownInTooltip.'</span>'.$hoveredText.'</span>';
}

function acym_info($tooltipText, $class = '', $containerClass = '', $classText = '', $warningInfo = false)
{
    $classWarning = $warningInfo ? 'acym__tooltip__info__warning' : '';

    return acym_tooltip(
        '<span class="acym__tooltip__info__container '.$class.'"><i class="acym__tooltip__info__icon acymicon-info-circle '.$classWarning.'"></i></span>',
        acym_translation($tooltipText),
        'acym__tooltip__info '.$containerClass,
        '',
        '',
        $classText
    );
}

/**
 * @param array  $options             as dbcolumnname => Displayed value
 * @param        $listing
 * @param string $default             if not set, the first value of $options will be set as default
 * @param string $defaultSortOrdering if not set, it will be desc
 *
 * @return string
 */
function acym_sortBy($options, $listing, $default = '', $defaultSortOrdering = 'desc')
{
    $default = empty($default) ? reset($options) : $default;

    $selected = acym_getVar('string', $listing.'_ordering', $default);
    $orderingSortOrder = acym_getVar('string', $listing.'_ordering_sort_order', $defaultSortOrdering);
    $classSortOrder = $orderingSortOrder == 'asc' ? 'acymicon-sort-amount-asc' : 'acymicon-sort-amount-desc';

    $display = '<span class="acym__color__dark-gray">'.acym_translation('ACYM_SORT_BY').'</span>';
    $display .= acym_select(
        $options,
        $listing.'_ordering',
        $selected,
        [
            'id' => 'acym__listing__ordering',
            'class' => 'acym__select acym__select__sort',
        ]
    );

    $tooltipText = $orderingSortOrder == 'asc' ? acym_translation('ACYM_SORT_ASC') : acym_translation('ACYM_SORT_DESC');
    $display .= acym_tooltip('<i class="'.$classSortOrder.' acym__listing__ordering__sort-order" aria-hidden="true"></i>', $tooltipText);

    $display .= '<input type="hidden" id="acym__listing__ordering__sort-order--input" name="'.$listing.'_ordering_sort_order" value="'.$orderingSortOrder.'">';

    return $display;
}

function acym_checkbox($values, $name, $selected = [], $label = '', $parentClass = '', $labelClass = '', $dataAttr = '')
{
    echo '<div class="'.$parentClass.'"><div class="cell acym__label '.$labelClass.'">'.$label.'</div><div class="cell auto grid-x">';
    foreach ($values as $key => $value) {
        $dtAttr = '';
        if (!empty($dataAttr[$key])) $dtAttr = 'data-attr="'.$dataAttr[$key].'"';
        echo '<label class="cell grid-x margin-top-1"><input type="checkbox" name="'.$name.'" value="'.$key.'" '.(in_array(
                $key,
                $selected
            ) ? 'checked' : '').' '.$dtAttr.'>'.$value.'</label>';
    }
    echo '</div></div>';
}

function acym_switchFilter($switchOptions, $selected, $name, $addClass = '')
{
    $return = '<input type="hidden" id="acym__type-template-'.$name.'" name="'.$name.'" value="'.$selected.'">';
    foreach ($switchOptions as $value => $text) {
        $class = 'button button-secondary acym__type__choosen cell small-6 xlarge-auto large-shrink';
        if ($value == $selected) {
            $class .= ' is-active';
        }
        $class .= ' '.$addClass;
        $return .= '<button class="'.acym_escape($class).'" type="button" data-type="'.acym_escape($value).'">'.acym_translation($text).'</button>';
    }

    return $return;
}

function acym_filterStatus($options, $selected, $name)
{
    $filterStatus = '<input type="hidden" id="acym_filter_status" name="'.acym_escape($name).'" value="'.acym_escape($selected).'"/>';

    foreach ($options as $value => $text) {
        $class = 'acym__filter__status clear button secondary';
        if ($value == $selected) {
            $class .= ' font-bold acym__status__select';
        }
        $disabled = empty($text[1]) ? ' disabled' : '';
        $extraIcon = '';
        if (!empty($text[2]) && 'pending' == $text[2]) {
            $extraIcon = ' <i class="acymicon-exclamation-triangle acym__color__orange" style="font-size: 15px;"></i>';
        }
        $filterStatus .= '<button type="button" status="'.acym_escape($value).'" class="'.acym_escape($class).'"'.$disabled.'>';
        $filterStatus .= acym_translation($text[0]).$extraIcon.' ('.$text[1].')</button>';
    }

    return $filterStatus;
}

function acym_filterSearch($search, $name, $placeholder = 'ACYM_SEARCH', $showClearBtn = true, $additionnalClasses = '')
{
    $searchField = '<div class="input-group acym__search-area '.$additionnalClasses.'">
        <input class="input-group-field acym__search-field" type="text" name="'.acym_escape($name).'" placeholder="'.acym_escape(
            acym_translation($placeholder)
        ).'" value="'.acym_escape($search).'">
        <div class="input-group-button">
            <button class="button acym__search__button"><i class="acymicon-search"></i></button>
        </div>';
    if ($showClearBtn) {
        $searchField .= '<span class="acym__search-clear"><i class="acymicon-close"></i></span>';
    }
    $searchField .= '</div>';

    return $searchField;
}

function acym_displayParam($type, $value, $name, $params = [])
{
    if (!include_once ACYM_FRONT.'params'.DS.$type.'.php') return '';

    $class = 'JFormField'.ucfirst($type);

    $field = new $class();
    $field->value = $value;
    $field->name = $name;

    if (!empty($params)) {
        foreach ($params as $param => $val) {
            $field->$param = $val;
        }
    }

    return $field->getInput();
}
