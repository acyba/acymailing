<?php
// context verification

/**
 * @param ?mixed $selected
 */
function acym_radio(
    array  $options,
    string $name,
           $selected = null,
    array  $attributes = [],
    array  $params = [],
    bool   $frontDisplay = false,
    array  $disabledOptions = []
): void {
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
    if (empty($params['containerClass'])) {
        $params['containerClass'] = '';
    }

    echo '<div class="acym_radio_group '.acym_escape($params['containerClass']).'">';
    $k = 0;
    foreach ($options as $value => $label) {
        $currentAttributes = $attributes;
        $currentAttributes['class'] = '';
        unset($currentAttributes['related']);

        if (is_object($label)) {
            if (!empty($label->class)) {
                $currentAttributes['class'] = $label->class;
            }

            $value = $label->$objValue;
            $label = $label->$objText;
        }

        $currentId = empty($params['useIncrement']) ? $id.$value : $id.$k;

        $currentAttributes['value'] = $value;
        $currentAttributes['id'] = $currentId;

        if (isset($attributes['related'][$value])) {
            $currentAttributes['acym-data-related'] = $attributes['related'][$value];
        }

        $extraClass = '';
        if (!empty($disabledOptions[$value])) {
            $currentAttributes['disabled'] = 'disabled';
            $extraClass = ' '.$disabledOptions[$value]['disabledClass'];
            $currentAttributes['class'] .= $extraClass;
        }

        if (!empty($params['required'])) {
            $currentAttributes['required'] = 'required';
            unset($params['required']);
        }

        if (!empty($disabledOptions[$value]['tooltipTxt'])) {
            ob_start();
        }

        if (!$frontDisplay) {
            echo '<i data-radio="'.acym_escape($currentId).'" class="acymicon-radio-button-checked acym_radio_checked'.acym_escape($extraClass).'"></i>';
            echo '<i data-radio="'.acym_escape($currentId).'" class="acymicon-radio-button-unchecked acym_radio_unchecked'.acym_escape($extraClass).'"></i>';
        }

        echo '<input ';
        acym_checked((string)$value === (string)$selected);
        foreach ($currentAttributes as $oneAttribute => $oneValue) {
            if (is_array($oneValue) || is_object($oneValue)) {
                $oneValue = json_encode($oneValue);
            } elseif ($oneValue === true) {
                $oneValue = $oneAttribute;
            }

            echo ' '.acym_escape($oneAttribute).'="'.acym_escape($oneValue).'"';
        }
        echo ' />';

        echo '<label for="'.acym_escape($currentId).'" id="'.acym_escape($currentId).'-lbl" class="'.acym_escape($extraClass).'">';
        echo acym_escapeHtmlWithAllowedTags(
            acym_translation($label),
            [
                'input' => [
                    'type' => true,
                    'value' => true,
                    'name' => true,
                    'placeholder' => true,
                ],
            ]
        );
        echo '</label>';

        if (!empty($disabledOptions[$value]['tooltipTxt'])) {
            $currentOption = ob_get_clean();
            acym_tooltip(
                [
                    'hoveredText' => $currentOption,
                    'textShownInTooltip' => $disabledOptions[$value]['tooltipTxt'],
                ]
            );
        }

        if (!empty($params['pluginMode'])) {
            echo '<br />';
        }
        $k++;
    }
    echo '</div>';
}

function acym_boolean(
    string  $name,
    bool    $selected = false,
    ?string $id = null,
    array   $attributes = [],
    string  $yes = 'ACYM_YES',
    string  $no = 'ACYM_NO'
): void {
    $options = [
        '1' => acym_translation($yes),
        '0' => acym_translation($no),
    ];

    $params = ['id' => $id];
    if (!empty($attributes['containerClass'])) {
        $params['containerClass'] = $attributes['containerClass'];
        unset($attributes['containerClass']);
    }

    acym_radio(
        $options,
        $name,
        $selected ? 1 : 0,
        $attributes,
        $params,
        !acym_isAdmin()
    );
}

/**
 * @param ?mixed $selected
 */
function acym_select(
    array   $data,
    string  $name,
            $selected = null,
    ?array  $attribs = null,
    string  $optKey = 'value',
    string  $optText = 'text',
    ?string $idtag = null,
    bool    $translate = false,
    bool    $display = false
): string {
    if (empty($attribs)) {
        $attribs = [];
    }

    $attribs['name'] = $name;
    $attribs['id'] = str_replace(['[', ']', ' '], '', empty($idtag) ? $name : $idtag);

    if (!$display) {
        ob_start();
    }

    echo '<select ';
    foreach ($attribs as $oneAttribute => $oneValue) {
        if (is_array($oneValue) || is_object($oneValue)) {
            $oneValue = json_encode($oneValue);
        } elseif ($oneValue === true) {
            $oneValue = $oneAttribute;
        }

        echo ' '.acym_escape($oneAttribute).'="'.acym_escape($oneValue).'"';
    }
    echo '>';

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

        if (strtolower($value) === '<optgroup>') {
            echo '<optgroup label="'.acym_escape($text).'">';
        } elseif (strtolower($value) === '</optgroup>') {
            echo '</optgroup>';
        } else {
            echo '<option value="'.acym_escape($value).'"';
            acym_selected(strval($value) === strval($selected));
            acym_disabled($disabled);
            echo '>'.acym_escapeHtml($text).'</option>';
        }
    }

    echo '</select>';

    if (!$display) {
        return ob_get_clean();
    }

    return '';
}

function acym_selectMultiple(
    array  $data,
    string $name,
    array  $selected = [],
    array  $attribs = [],
    string $optValue = 'value',
    string $optText = 'text',
    bool   $display = false
): string {
    if (substr($name, -2) !== '[]') {
        $name .= '[]';
    }
    $baseName = substr($name, 0, -2);

    $attribs['name'] = $name;
    $attribs['multiple'] = 'multiple';

    if (!$display) {
        ob_start();
    }

    echo '<input type="hidden" name="'.acym_escape($baseName).'" value="">';
    echo '<select ';
    foreach ($attribs as $oneAttribute => $oneValue) {
        if (is_array($oneValue) || is_object($oneValue)) {
            $oneValue = json_encode($oneValue);
        } elseif ($oneValue === true) {
            $oneValue = $oneAttribute;
        }

        echo ' '.acym_escape($oneAttribute).'="'.acym_escape($oneValue).'"';
    }
    echo '>';

    foreach ($data as $oneDataKey => $oneDataValue) {
        if (is_object($oneDataValue)) {
            $value = $oneDataValue->$optValue;
            $text = $oneDataValue->$optText;
        } else {
            $value = $oneDataKey;
            $text = $oneDataValue;
        }

        if (strtolower($value) === '<optgroup>') {
            echo '<optgroup label="'.acym_escape($text).'">';
        } elseif (strtolower($value) === '</optgroup>') {
            echo '</optgroup>';
        } else {
            echo '<option value="'.acym_escape($value).'"';
            acym_selected(in_array($value, $selected));
            acym_disabled(is_object($oneDataValue) && !empty($oneDataValue->disable));
            echo '>'.acym_escapeHtml($text).'</option>';
        }
    }

    echo '</select>';

    if (!$display) {
        return ob_get_clean();
    }

    return '';
}

/**
 * @param mixed $value
 */
function acym_selectOption(
    $value,
    string $text = '',
    string $optKey = 'value',
    string $optText = 'text',
    bool $disable = false
): object {
    $option = new stdClass();
    $option->$optKey = $value;
    $option->$optText = acym_translation($text);
    $option->disable = $disable;

    return $option;
}

/**
 * @param string|array $options The string name or the new options in an array
 * @param mixed        $value
 */
function acym_switch(
    $options,
    $value = '',
    ?string $label = null,
    array $attrInput = [],
    string $labelClass = 'medium-6 small-9',
    string $switchContainerClass = 'auto',
    string $switchClass = '',
    ?string $toggle = null
): void {
    static $occurrence = 100;
    $occurrence++;

    $id = 'switch_'.$occurrence;

    //TODO remove old parameters in January 2027 (only used in WC, Hika, VM and ultimate member plugins, which were updated in July 2026
    if (!is_array($options)) {
        $options = [
            'name' => $options,
            'value' => $value,
            'label' => $label,
            'attributes' => $attrInput,
            'labelClass' => $labelClass,
            'switchContainerClass' => $switchContainerClass,
            'switchClass' => $switchClass,
            'toggle' => $toggle,
            'toggleOpen' => true,
            'disabled' => false,
        ];
    } else {
        $options['attributes'] = $options['attributes'] ?? [];
        $options['labelClass'] = $options['labelClass'] ?? 'medium-6 small-9';
        $options['switchContainerClass'] = $options['switchContainerClass'] ?? 'auto';
        $options['switchClass'] = $options['switchClass'] ?? '';
        $options['toggleOpen'] = $options['toggleOpen'] ?? true;
        $options['disabled'] = $options['disabled'] ?? false;
    }

    $checkboxAriaLabel = '';
    if (!empty($options['attributes']['aria-label'])) {
        $checkboxAriaLabel = $options['attributes']['aria-label'];
        unset($options['attributes']['aria-label']);
    }

    $options['attributes']['name'] = $options['name'];
    $options['attributes']['data-switch'] = $id;
    $options['attributes']['value'] = $options['value'];

    if (!empty($options['toggle'])) {
        $options['attributes']['data-toggle-switch'] = $options['toggle'];
        $options['attributes']['data-toggle-switch-open'] = $options['toggleOpen'] ? 'show' : 'hide';
    }

    if (!empty($options['label']) || !empty($options['tooltip'])) {
        echo '<label for="'.acym_escape($id).'" class="cell '.acym_escape($options['labelClass']).' switch-label">';
        if (!empty($options['label'])) {
            echo acym_escapeHtml($options['label']);
        }

        if (!empty($options['tooltip'])) {
            acym_tooltip($options['tooltip']);
        }

        if (!empty($options['tip'])) {
            acym_info($options['tip']);
        }
        echo '</label>';
        echo '<div class="cell '.acym_escape($options['switchContainerClass']).'">';
    }

    $labelSwitchDisabled = !$options['disabled'] ? '' : ' disabled';
    ?>

	<div class="switch <?php echo acym_escape($options['switchClass']); ?>">
		<input type="hidden"
            <?php
            foreach ($options['attributes'] as $oneAttribute => $oneValue) {
                if (is_array($oneValue) || is_object($oneValue)) {
                    $oneValue = json_encode($oneValue);
                } elseif ($oneValue === true) {
                    $oneValue = $oneAttribute;
                }

                echo ' '.acym_escape($oneAttribute).'="'.acym_escape($oneValue).'"';
            }
            ?>
		>

		<input class="switch-input"
		       type="checkbox"
		       id="<?php echo acym_escape($id); ?>"
		       value="1"
		       tabindex="0"
		       role="switch"
            <?php
            acym_checked($options['value'] == 1);
            acym_disabled($options['disabled']);
            if (!empty($checkboxAriaLabel)) {
                echo ' aria-label="'.acym_escape($checkboxAriaLabel).'"';
            }
            ?>
		>
		<label class="switch-paddle switch-label <?php echo acym_escape($labelSwitchDisabled); ?>" for="<?php echo acym_escape($id); ?>">
			<span class="switch-active" aria-hidden="true">1</span>
			<span class="switch-inactive" aria-hidden="true">0</span>
		</label>
	</div>

    <?php
    if (!empty($options['label']) || !empty($options['tooltip'])) {
        echo '</div>';
    }
}

function acym_showMore(string $toggle, string $text = 'ACYM_SHOW_MORE', string $class = ''): void
{
    ?>
	<div class="showmore <?php echo acym_escape($class); ?>" data-toggle-showmore="<?php echo acym_escape($toggle); ?>">
		<label>
            <?php echo acym_escapeHtml(acym_translation($text)); ?><i class="acymicon-keyboard-arrow-down"></i>
		</label>
	</div>
    <?php
}

function acym_generateCountryNumber(string $name, string $defaultvalue = ''): void
{
    $country = [
        '' => acym_translation('ACYM_PHONE_NOCOUNTRY'),
        '93' => 'Afghanistan',
        '355' => 'Albania',
        '213' => 'Algeria',
        '1684' => 'American Samoa',
        '376' => 'Andorra',
        '244' => 'Angola',
        '1264' => 'Anguilla',
        '672' => 'Antarctica',
        '1268' => 'Antigua & Barbuda',
        '54' => 'Argentina',
        '374' => 'Armenia',
        '297' => 'Aruba',
        '247' => 'Ascension Island',
        '61' => 'Australia',
        '43' => 'Austria',
        '994' => 'Azerbaijan',
        '1242' => 'Bahamas',
        '973' => 'Bahrain',
        '880' => 'Bangladesh',
        '1246' => 'Barbados',
        '375' => 'Belarus',
        '32' => 'Belgium',
        '501' => 'Belize',
        '229' => 'Benin',
        '1441' => 'Bermuda',
        '975' => 'Bhutan',
        '591' => 'Bolivia',
        '387' => 'Bosnia/Herzegovina',
        '267' => 'Botswana',
        '55' => 'Brazil',
        '1284' => 'British Virgin Islands',
        '673' => 'Brunei',
        '359' => 'Bulgaria',
        '226' => 'Burkina Faso',
        '257' => 'Burundi',
        '855' => 'Cambodia',
        '237' => 'Cameroon',
        '1' => 'Canada/USA',
        '238' => 'Cape Verde Islands',
        '1345' => 'Cayman Islands',
        '236' => 'Central African Republic',
        '235' => 'Chad Republic',
        '56' => 'Chile',
        '86' => 'China',
        '6724' => 'Christmas Island',
        '6722' => 'Cocos Keeling Island',
        '57' => 'Colombia',
        '269' => 'Comoros',
        '243' => 'Congo Democratic Republic',
        '242' => 'Congo, Republic of',
        '682' => 'Cook Islands',
        '506' => 'Costa Rica',
        '225' => 'Cote D\'Ivoire',
        '385' => 'Croatia',
        '53' => 'Cuba',
        '357' => 'Cyprus',
        '420' => 'Czech Republic',
        '45' => 'Denmark',
        '253' => 'Djibouti',
        '1767' => 'Dominica',
        '1809' => 'Dominican Republic',
        '593' => 'Ecuador',
        '20' => 'Egypt',
        '503' => 'El Salvador',
        '240' => 'Equatorial Guinea',
        '291' => 'Eritrea',
        '372' => 'Estonia',
        '251' => 'Ethiopia',
        '500' => 'Falkland Islands',
        '298' => 'Faroe Island',
        '679' => 'Fiji Islands',
        '358' => 'Finland',
        '33' => 'France',
        '596' => 'French Antilles/Martinique',
        '594' => 'French Guiana',
        '689' => 'French Polynesia',
        '241' => 'Gabon Republic',
        '220' => 'Gambia',
        '995' => 'Georgia',
        '49' => 'Germany',
        '233' => 'Ghana',
        '350' => 'Gibraltar',
        '30' => 'Greece',
        '299' => 'Greenland',
        '1473' => 'Grenada',
        '590' => 'Guadeloupe',
        '1671' => 'Guam',
        '502' => 'Guatemala',
        '224' => 'Guinea Republic',
        '245' => 'Guinea-Bissau',
        '592' => 'Guyana',
        '509' => 'Haiti',
        '504' => 'Honduras',
        '852' => 'Hong Kong',
        '36' => 'Hungary',
        '354' => 'Iceland',
        '91' => 'India',
        '62' => 'Indonesia',
        '964' => 'Iraq',
        '98' => 'Iran',
        '353' => 'Ireland',
        '972' => 'Israel',
        '39' => 'Italy',
        '1876' => 'Jamaica',
        '81' => 'Japan',
        '962' => 'Jordan',
        '254' => 'Kenya',
        '686' => 'Kiribati',
        '3774' => 'Kosovo',
        '965' => 'Kuwait',
        '996' => 'Kyrgyzstan',
        '856' => 'Laos',
        '371' => 'Latvia',
        '961' => 'Lebanon',
        '266' => 'Lesotho',
        '231' => 'Liberia',
        '218' => 'Libya',
        '423' => 'Liechtenstein',
        '370' => 'Lithuania',
        '352' => 'Luxembourg',
        '853' => 'Macau',
        '389' => 'Macedonia',
        '261' => 'Madagascar',
        '265' => 'Malawi',
        '60' => 'Malaysia',
        '960' => 'Maldives',
        '223' => 'Mali Republic',
        '356' => 'Malta',
        '692' => 'Marshall Islands',
        '222' => 'Mauritania',
        '230' => 'Mauritius',
        '52' => 'Mexico',
        '691' => 'Micronesia',
        '373' => 'Moldova',
        '377' => 'Monaco',
        '976' => 'Mongolia',
        '382' => 'Montenegro',
        '1664' => 'Montserrat',
        '212' => 'Morocco',
        '258' => 'Mozambique',
        '95' => 'Myanmar (Burma)',
        '264' => 'Namibia',
        '674' => 'Nauru',
        '977' => 'Nepal',
        '31' => 'Netherlands',
        '599' => 'Netherlands Antilles',
        '687' => 'New Caledonia',
        '64' => 'New Zealand',
        '505' => 'Nicaragua',
        '227' => 'Niger Republic',
        '234' => 'Nigeria',
        '683' => 'Niue Island',
        '6723' => 'Norfolk',
        '850' => 'North Korea',
        '47' => 'Norway',
        '968' => 'Oman Dem Republic',
        '92' => 'Pakistan',
        '680' => 'Palau Republic',
        '970' => 'Palestine',
        '507' => 'Panama',
        '675' => 'Papua New Guinea',
        '595' => 'Paraguay',
        '51' => 'Peru',
        '63' => 'Philippines',
        '48' => 'Poland',
        '351' => 'Portugal',
        '1787' => 'Puerto Rico',
        '974' => 'Qatar',
        '262' => 'Reunion Island',
        '40' => 'Romania',
        '7' => 'Russia',
        '250' => 'Rwanda Republic',
        '1670' => 'Saipan/Mariannas',
        '378' => 'San Marino',
        '239' => 'Sao Tome/Principe',
        '966' => 'Saudi Arabia',
        '221' => 'Senegal',
        '381' => 'Serbia',
        '248' => 'Seychelles Island',
        '232' => 'Sierra Leone',
        '65' => 'Singapore',
        '421' => 'Slovakia',
        '386' => 'Slovenia',
        '677' => 'Solomon Islands',
        '252' => 'Somalia Republic',
        '685' => 'Somoa',
        '27' => 'South Africa',
        '82' => 'South Korea',
        '34' => 'Spain',
        '94' => 'Sri Lanka',
        '290' => 'St. Helena',
        '1869' => 'St. Kitts',
        '1758' => 'St. Lucia',
        '508' => 'St. Pierre',
        '1784' => 'St. Vincent',
        '249' => 'Sudan',
        '597' => 'Suriname',
        '268' => 'Swaziland',
        '46' => 'Sweden',
        '41' => 'Switzerland',
        '963' => 'Syria',
        '886' => 'Taiwan',
        '992' => 'Tajikistan',
        '255' => 'Tanzania',
        '66' => 'Thailand',
        '228' => 'Togo Republic',
        '690' => 'Tokelau',
        '676' => 'Tonga Islands',
        '1868' => 'Trinidad & Tobago',
        '216' => 'Tunisia',
        '90' => 'Turkey',
        '993' => 'Turkmenistan',
        '1649' => 'Turks & Caicos Island',
        '688' => 'Tuvalu',
        '256' => 'Uganda',
        '380' => 'Ukraine',
        '971' => 'United Arab Emirates',
        '44' => 'United Kingdom',
        '598' => 'Uruguay',
        '1 ' => 'USA/Canada',
        '998' => 'Uzbekistan',
        '678' => 'Vanuatu',
        '3966' => 'Vatican City',
        '58' => 'Venezuela',
        '84' => 'Vietnam',
        '1340' => 'Virgin Islands (US)',
        '681' => 'Wallis/Futuna Islands',
        '967' => 'Yemen Arab Republic',
        '260' => 'Zambia',
        '263' => 'Zimbabwe',
    ];

    $countryCodeForSelect = [];

    foreach ($country as $key => $one) {
        $countryCodeForSelect[$key] = '+'.$key.' ('.$one.')';
    }

    acym_select(
        $countryCodeForSelect,
        $name,
        empty($defaultvalue) ? '' : $defaultvalue,
        [
            'class' => 'acym__select__country acym__select',
            'autocomplete' => 'tel-country-code',
        ],
        'value',
        'text',
        null,
        false,
        true
    );
}

function acym_cancelButton(string $text = 'ACYM_CANCEL', string $url = '', string $class = 'button medium-6 large-shrink'): void
{
    if (empty($url)) {
        $url = acym_completeLink(acym_getVar('cmd', 'ctrl').'&task=listing');
    }

    echo '<a href="'.acym_escapeUrl($url).'" class="cell '.acym_escape($class).' acym__button__cancel">'.acym_escapeHtml(acym_translation($text)).'</a>';
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
function acym_tooltip(array $options): void
{
    if (!isset($options['classContainer'])) $options['classContainer'] = '';
    if (!isset($options['classText'])) $options['classText'] = '';
    if (!isset($options['titleShownInTooltip'])) $options['titleShownInTooltip'] = '';
    if (!isset($options['textShownInTooltip'])) $options['textShownInTooltip'] = '';
    if (!isset($options['hoveredText'])) $options['hoveredText'] = '';
    if (!isset($options['classLink'])) $options['classLink'] = '';

    echo '<span class="acym__tooltip '.acym_escape($options['classContainer']).'">';
    echo '<span class="acym__tooltip__text '.acym_escape($options['classText']).'">';

    if (!empty($options['titleShownInTooltip'])) {
        echo '<span class="acym__tooltip__title">';
    }
    echo acym_escapeHtml($options['titleShownInTooltip']);
    if (!empty($options['titleShownInTooltip'])) {
        echo '</span>';
    }

    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Text escaped by caller, too many possible inputs.
    echo $options['textShownInTooltip'];
    echo '</span>';

    if (!empty($options['link'])) {
        echo '<a 
                href="'.acym_escapeUrl($options['link']).'" 
                title="'.acym_escape($options['titleShownInTooltip']).'" 
                target="_blank" 
                class="'.acym_escape($options['classLink']).'">';
    }
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Text escaped by caller, too many possible inputs.
    echo $options['hoveredText'];
    if (!empty($options['link'])) {
        echo '</a>';
    }
    echo '</span>';
}

/**
 * Options:
 *    textShownInTooltip => Text shown inside the tooltip box
 *    classContainer     => Classes applied on the main container
 *    classText          => Classes applied to the hovered icon container
 *    classIcon          => Classes applied to the hovered icon
 *    isWarning          => Is the tooltip icon should be more visible
 *    classLink          => Classes applied on the link element if any
 */
function acym_info(array $options): void
{
    if (!isset($options['textShownInTooltip'])) $options['textShownInTooltip'] = '';
    if (!isset($options['classContainer'])) $options['classContainer'] = '';
    if (!isset($options['classText'])) $options['classText'] = '';
    if (!isset($options['classIcon'])) $options['classIcon'] = '';
    if (!isset($options['isWarning'])) $options['isWarning'] = false;

    $classWarning = $options['isWarning'] ? 'acym__tooltip__info__warning' : '';

    acym_tooltip(
        [
            'hoveredText' => '<span class="acym__tooltip__info__container '.$options['classIcon'].'"><i class="acym__tooltip__info__icon acymicon-info-circle '.acym_escape(
                    $classWarning
                ).'"></i></span>',
            'textShownInTooltip' => acym_translation($options['textShownInTooltip']),
            'classContainer' => 'acym__tooltip__info '.$options['classContainer'],
            'classText' => $options['classText'],
        ]
    );
}

function acym_sortBy(array $options, string $listing, string $default = '', string $defaultSortOrdering = 'desc'): void
{
    $default = empty($default) ? reset($options) : $default;

    $selected = acym_getVar('string', $listing.'_ordering', $default);
    $orderingSortOrder = acym_getVar('string', $listing.'_ordering_sort_order', $defaultSortOrdering);
    $classSortOrder = $orderingSortOrder === 'asc' ? 'acymicon-sort-amount-asc' : 'acymicon-sort-amount-desc';

    echo '<span class="acym__color__dark-gray">'.acym_escapeHtml(acym_translation('ACYM_SORT_BY')).'</span>';
    acym_select(
        $options,
        $listing.'_ordering',
        $selected,
        [
            'id' => 'acym__listing__ordering',
            'class' => 'acym__select acym__select__sort',
        ],
        'value',
        'text',
        null,
        false,
        true
    );

    $tooltipText = $orderingSortOrder === 'asc' ? acym_translation('ACYM_SORT_ASC') : acym_translation('ACYM_SORT_DESC');
    acym_tooltip(
        [
            'hoveredText' => '<i class="'.$classSortOrder.' acym__listing__ordering__sort-order" aria-hidden="true"></i>',
            'textShownInTooltip' => $tooltipText,
        ]
    );

    echo '<input type="hidden" id="acym__listing__ordering__sort-order--input" name="'.acym_escape($listing).'_ordering_sort_order" value="'.acym_escape($orderingSortOrder).'">';
}

function acym_checkbox(
    array  $values,
    string $name,
    array  $selected = [],
    string $label = '',
    string $parentClass = '',
    string $labelClass = '',
    array  $dataAttr = []
): void {
    echo '<div class="'.acym_escape($parentClass).'"><div class="cell acym__label '.acym_escape($labelClass).'">'.acym_escapeHtml($label).'</div><div class="cell auto grid-x">';
    foreach ($values as $key => $value) {
        ?>
		<label class="cell grid-x margin-top-1">
			<input type="checkbox"
			       name="<?php echo acym_escape($name); ?>"
			       value="<?php echo acym_escape($key); ?>"
                <?php acym_checked(in_array($key, $selected)); ?>
                <?php
                if (!empty($dataAttr[$key])) {
                    echo 'data-attr="'.acym_escape($dataAttr[$key]).'"';
                }
                ?>>
            <?php echo $value; ?>
		</label>
        <?php
    }
    echo '</div></div>';
}

function acym_switchFilter(array $switchOptions, string $selected, string $name, string $addClass = ''): string
{
    $return = '<input type="hidden" id="acym__type-template-'.acym_escape($name).'" name="'.acym_escape($name).'" value="'.acym_escape($selected).'">';
    foreach ($switchOptions as $value => $text) {
        $class = 'button button-secondary acym__type__choosen cell small-6 xlarge-auto large-shrink';
        if ($value === $selected) {
            $class .= ' is-active';
        }
        $class .= ' '.$addClass;
        $return .= '<button class="'.acym_escape($class).'" type="button" data-type="'.acym_escape($value).'">'.acym_escapeHtml(acym_translation($text)).'</button>';
    }

    return $return;
}

function acym_filterStatus(array $options, string $selected, string $name): void
{
    echo '<input type="hidden" id="acym_filter_status" name="'.acym_escape($name).'" value="'.acym_escape($selected).'"/>';

    foreach ($options as $value => $text) {
        ?>
		<button type="button"
		        acym-data-status="<?php echo acym_escape($value); ?>"
		        class="acym__filter__status<?php echo $value === $selected ? ' font-bold acym__status__select' : ''; ?>"
            <?php acym_disabled(!is_null($text[1]) && empty($text[1])); ?>
		>
            <?php echo acym_escapeHtml(acym_translation($text[0])); ?>
            <?php
            if (!empty($text[2]) && 'pending' == $text[2]) {
                echo ' <i class="acymicon-exclamation-triangle acym__color__orange" style="font-size: 15px;"></i>';
            }

            if (!is_null($text[1])) {
                echo '<span class="acym__filter__status__number"> ('.acym_escapeHtml($text[1]).')</span>';
            }
            ?>
		</button>
        <?php
    }
}

function acym_filterSearch(
    string $search,
    string $name,
    string $placeholder = 'ACYM_SEARCH',
    bool   $showClearBtn = true,
    string $additionalClasses = ''
): void {
    ?>
	<div class="input-group acym__search-area <?php echo acym_escape($additionalClasses); ?>">
		<div class="input-group-button">
			<button class="button acym__search__button"><i class="acymicon-search"></i></button>
		</div>
		<input class="input-group-field acym__search-field"
		       type="text"
		       name="<?php echo acym_escape($name); ?>"
		       placeholder="<?php echo acym_escape(acym_translation($placeholder)); ?>"
		       value="<?php echo acym_escape($search); ?>">
        <?php if ($showClearBtn) { ?>
			<span class="acym__search-clear"><i class="acymicon-close"></i></span>
        <?php } ?>
	</div>
    <?php
}

/**
 * @param mixed $value
 */
function acym_displayParam(string $type, $value, string $name): void
{
    if (!include_once ACYM_FRONT.'Params'.DS.$type.'.php') {
        return;
    }

    $class = 'JFormField'.ucfirst($type);

    $field = new $class();
    $field->value = $value;
    $field->name = $name;

    echo $field->getInput();
}

function acym_externalLink(
    string $text,
    string $link,
    bool   $displayIcon = true,
    bool   $openInNewTab = true,
    array  $classesA = []
): void {
    $classesA[] = 'acym__external__link';
    ?>
	<a
			href="<?php echo acym_escapeUrl($link); ?>"
			class="<?php echo acym_escape(implode(' ', $classesA)); ?>"
        <?php
        if ($openInNewTab) {
            echo 'target="_blank"';
        }
        ?>
	>
        <?php echo acym_escapeHtml(acym_translation($text)); ?>
        <?php
        if ($displayIcon) {
            echo ' <i class="acymicon-external-link"></i>';
        }
        ?>
	</a>
    <?php
}

function acym_getFormattedAttributes(array $attributes): string
{
    $params = '';
    foreach ($attributes as $oneAttribute => $oneValue) {
        $params .= ' '.$oneAttribute;

        if ($oneValue !== true) {
            if (is_array($oneValue) || is_object($oneValue)) {
                $oneValue = json_encode($oneValue);
            }
            $params .= '="'.acym_escape($oneValue).'"';
        }
    }

    return $params;
}
