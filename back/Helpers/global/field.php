<?php

/**
 * @param array   $options         It is either an array with value => label or an array of objects
 * @param         $name
 * @param null    $selected
 * @param array   $attributes      The html attributes for the inputs
 * @param array   $params          Special parameters
 * @param bool    $frontDisplay
 * @param array   $disabledOptions Array of options that should be disabled. The keys should be the same as the ones from $options (value).
 *                                 The content is an array with the CSS class to apply (disabledClass) and optionally a text to add a tooltip (tooltipTxt)
 *
 * @return string A formatted radio button
 */
function acym_radio($options, $name, $selected = null, $attributes = [], $params = [], $frontDisplay = false, $disabledOptions = [])
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
        $attributes['class'] = '';
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

        $disabled = '';
        $extraClass = '';
        if (!empty($disabledOptions[$value])) {
            $disabled = ' disabled';
            $extraClass = ' '.$disabledOptions[$value]['disabledClass'];
            $attributes['class'] .= $extraClass;
        }

        $formattedAttributes = '';
        foreach ($attributes as $attribute => $val) {
            if ($attribute === 'related') continue;
            $formattedAttributes .= ' '.$attribute.'="'.acym_escape($val).'"';
        }
        if (!empty($params['required'])) {
            $formattedAttributes .= ' required';
            unset($params['required']);
        }

        $elementClass = empty($elementClass) ? '' : ' class="'.$elementClass.'"';

        $currentOption = '';
        if (!$frontDisplay) {
            $currentOption .= '<i data-radio="'.$currentId.'" class="acymicon-radio-button-checked acym_radio_checked'.$extraClass.'"></i>';
            $currentOption .= '<i data-radio="'.$currentId.'" class="acymicon-radio-button-unchecked acym_radio_unchecked'.$extraClass.'"></i>';
        }

        $currentOption .= '<input'.$formattedAttributes.$checked.$disabled.' />';
        $currentOption .= '<label for="'.$currentId.'" id="'.$currentId.'-lbl" class="'.$extraClass.'">'.acym_translation($label).'</label>';

        if (!empty($disabledOptions[$value]['tooltipTxt'])) {
            $currentOption = acym_tooltip(
                [
                    'hoveredText' => $currentOption,
                    'textShownInTooltip' => $disabledOptions[$value]['tooltipTxt'],
                ]
            );
        }

        $return .= $currentOption;

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

function acym_select($data, $name, $selected = null, $attribs = null, $optKey = 'value', $optText = 'text', $idtag = false, $translate = false): string
{
    $idtag = str_replace(['[', ']', ' '], '', empty($idtag) ? $name : $idtag);

    $attributes = '';
    if (!empty($attribs)) {
        if (is_array($attribs)) {
            foreach ($attribs as $attribName => $attribValue) {
                if (is_array($attribValue) || is_object($attribValue)) {
                    $attribValue = json_encode($attribValue);
                }
                $attribName = str_replace([' ', '"', "'"], '_', $attribName);
                $attributes .= ' '.$attribName;
                if ($attribValue !== true) {
                    $attributes .= '="'.acym_escape($attribValue).'"';
                }
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
            $dropdown .= '<option value="'.$cleanValue.'"'.(strval($value) === strval($selected) ? ' selected="selected"' : '').($disabled ? ' disabled="disabled"'
                    : '').'>'.$cleanText.'</option>';
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
function acym_switch(
    $name,
    $value,
    $label = null,
    $attrInput = [],
    $labelClass = 'medium-6 small-9',
    $switchContainerClass = 'auto',
    $switchClass = '',
    $toggle = null,
    $toggleOpen = true,
    $vModel = '',
    $disabled = false,
    $disabledMessage = ''
) {
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

/**
 * Add a text to display/hide a zone
 *
 * @param string $toggle id of the element to toggle display
 * @param string $text   text to display on the toggle button
 * @param string $class  optional custom class
 *
 * @return string
 */
function acym_showMore($toggle, $text = 'ACYM_SHOW_MORE', $class = '')
{
    $showMore = '<div class="showmore '.$class.'" data-toggle-showmore="'.$toggle.'">';
    $showMore .= '<label>'.acym_translation($text).'<i class="acymicon-keyboard-arrow-down"></i></label>';
    $showMore .= '</div>';

    return $showMore;
}

function acym_generateCountryNumber($name, $defaultvalue = '')
{
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

    return acym_select(
        $countryCodeForSelect,
        $name,
        empty($defaultvalue) ? '' : $defaultvalue,
        ['class' => 'acym__select__country acym__select']
    );
}

function acym_cancelButton(string $text = 'ACYM_CANCEL', string $url = '', string $class = 'button medium-6 large-shrink'): string
{
    if (empty($url)) {
        $url = acym_completeLink(acym_getVar('cmd', 'ctrl').'&task=listing');
    }

    return '<a href="'.$url.'" class="cell '.$class.' acym__button__cancel">'.acym_escape(acym_translation($text)).'</a>';
}

/**
 * Options:
 *    classContainer      => Classes applied on the main container
 *    hoveredText         => Text shown on the page
 *    classText           => Classes applied to the hovered text container
 *    titleShownInTooltip => Title added above the tooltip text
 *    textShownInTooltip  => Text shown inside the tooltip box
 *    link                => URL to redirect on click to the hovered text
 *    classLink           => Classes applied on the link element if any
 */
function acym_tooltip(array $options): string
{
    if (!isset($options['classContainer'])) $options['classContainer'] = '';
    if (!isset($options['classText'])) $options['classText'] = '';
    if (!isset($options['titleShownInTooltip'])) $options['titleShownInTooltip'] = '';
    if (!isset($options['textShownInTooltip'])) $options['textShownInTooltip'] = '';
    if (!isset($options['hoveredText'])) $options['hoveredText'] = '';
    if (!isset($options['classLink'])) $options['classLink'] = '';

    if (!empty($options['link'])) {
        $options['hoveredText'] = '<a href="'.$options['link'].'" title="'.acym_escape($options['titleShownInTooltip']).'" target="_blank" class="'.acym_escape(
                $options['classLink']
            ).'">'.$options['hoveredText'].'</a>';
    }

    if (!empty($options['titleShownInTooltip'])) {
        $options['titleShownInTooltip'] = '<span class="acym__tooltip__title">'.$options['titleShownInTooltip'].'</span>';
    }

    $tooltip = '<span class="acym__tooltip '.$options['classContainer'].'">';
    $tooltip .= '<span class="acym__tooltip__text '.$options['classText'].'">';
    $tooltip .= $options['titleShownInTooltip'].$options['textShownInTooltip'].'</span>'.$options['hoveredText'].'</span>';

    return $tooltip;
}

function acym_info($options, $class = '', $containerClass = '', $classText = '', $warningInfo = false): string
{
    if (!is_array($options)) {
        $options = [
            'textShownInTooltip' => $options,
            'classContainer' => $containerClass,
            'classIcon' => $class,
            'classText' => $classText,
            'isWarning' => $warningInfo,
        ];
    }

    if (!isset($options['textShownInTooltip'])) $options['textShownInTooltip'] = '';
    if (!isset($options['classContainer'])) $options['classContainer'] = '';
    if (!isset($options['classText'])) $options['classText'] = '';
    if (!isset($options['classIcon'])) $options['classIcon'] = '';
    if (!isset($options['isWarning'])) $options['isWarning'] = false;

    $classWarning = $options['isWarning'] ? 'acym__tooltip__info__warning' : '';

    return acym_tooltip(
        [
            'hoveredText' => '<span class="acym__tooltip__info__container '.$options['classIcon'].'"><i class="acym__tooltip__info__icon acymicon-info-circle '.$classWarning.'"></i></span>',
            'textShownInTooltip' => acym_translation($options['textShownInTooltip']),
            'classContainer' => 'acym__tooltip__info '.$options['classContainer'],
            'classText' => $options['classText'],
        ]
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
    $display .= acym_tooltip(
        [
            'hoveredText' => '<i class="'.$classSortOrder.' acym__listing__ordering__sort-order" aria-hidden="true"></i>',
            'textShownInTooltip' => $tooltipText,
        ]
    );

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
        $class = 'acym__filter__status ';
        if ($value == $selected) {
            $class .= ' font-bold acym__status__select';
        }

        $extraIcon = '';
        if (!empty($text[2]) && 'pending' == $text[2]) {
            $extraIcon = ' <i class="acymicon-exclamation-triangle acym__color__orange" style="font-size: 15px;"></i>';
        }

        if (is_null($text[1])) {
            $disabled = '';
            $userCount = '';
        } else {
            $disabled = empty($text[1]) ? ' disabled' : '';
            $userCount = ' ('.$text[1].')';
        }
        $filterStatus .= '<button type="button" acym-data-status="'.acym_escape($value).'" class="'.acym_escape($class).'"'.$disabled.'>';
        $filterStatus .= acym_translation($text[0]).$extraIcon.'<span class="acym__filter__status__number">'.$userCount.'</span></button>';
    }

    return $filterStatus;
}

function acym_filterSearch($search, $name, $placeholder = 'ACYM_SEARCH', $showClearBtn = true, $additionnalClasses = '')
{
    $searchField = '<div class="input-group acym__search-area '.$additionnalClasses.'">
        <div class="input-group-button">
            <button class="button acym__search__button"><i class="acymicon-search"></i></button>
        </div>
        <input class="input-group-field acym__search-field" type="text" name="'.acym_escape($name).'" placeholder="'.acym_escape(
            acym_translation($placeholder)
        ).'" value="'.acym_escape($search).'">';
    if ($showClearBtn) {
        $searchField .= '<span class="acym__search-clear"><i class="acymicon-close"></i></span>';
    }
    $searchField .= '</div>';

    return $searchField;
}

function acym_displayParam($type, $value, $name, $params = [])
{
    if (!include_once ACYM_FRONT.'Params'.DS.$type.'.php') return '';

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

/**
 * @param string $text         Displayed text
 * @param string $link         Link to go to
 * @param bool   $displayIcon  Display or not the icon
 * @param bool   $openInNewTab Open or not in a new tab
 * @param array  $classesA     Custom classes to add to a tag
 *
 * @return string
 */
function acym_externalLink($text, $link, $displayIcon = true, $openInNewTab = true, $classesA = [])
{
    $target = $openInNewTab ? 'target="_blank"' : '';
    $link = 'href="'.$link.'"';
    $icon = $displayIcon ? ' <i class="acymicon-external-link"></i>' : '';
    $translatedText = acym_translation($text);
    $classesA[] = 'acym__external__link';
    $classesAHtml = 'class="'.implode(' ', $classesA).'"';

    return '<a '.$target.' '.$link.' '.$classesAHtml.'>'.$translatedText.$icon.'</a>';
}
