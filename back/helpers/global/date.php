<?php

function acym_replaceDateTags($value)
{
    $replace = ['{year}', '{month}', '{weekday}', '{day}'];
    $replaceBy = [date('Y'), date('m'), date('N'), date('d')];
    $value = str_replace($replace, $replaceBy, $value);

    $results = [];
    if (preg_match_all('#{(year|month|weekday|day)\|(add|remove):([^}]*)}#Uis', $value, $results)) {
        foreach ($results[0] as $i => $oneMatch) {
            $format = str_replace(['year', 'month', 'weekday', 'day'], ['Y', 'm', 'N', 'd'], $results[1][$i]);
            $delay = str_replace(['add', 'remove'], ['+', '-'], $results[2][$i]).intval($results[3][$i]).' '.str_replace('weekday', 'day', $results[1][$i]);
            $value = str_replace($oneMatch, date($format, strtotime($delay)), $value);
        }
    }

    return $value;
}

function acym_dateField($name, $value = '', $class = '', $attributes = '', $relativeDefault = '-')
{
    // Open container
    $result = '<div class="date_rs_selection_popup">';

    // Choice of relative / specific date
    $result .= '<div class="grid-x">';
    $result .= acym_switchFilter(
        [
            'relative' => acym_translation('ACYM_RELATIVE_DATE'),
            'specific' => acym_translation('ACYM_SPECIFIC_DATE'),
        ],
        'relative',
        'switch_'.$name,
        'date_rs_selection'
    );
    $result .= '</div>';

    // Relative date options
    $result .= '<div class="date_rs_selection_choice date_rs_selection_relative grid-x grid-margin-x">
                    <div class="cell small-2">
                        <input type="number" class="relativenumber" value="0">
                    </div>
                    <div class="cell small-5">';
    $result .= acym_select(
        [
            '60' => acym_translation('ACYM_MINUTES'),
            '3600' => acym_translation('ACYM_HOUR'),
            '86400' => acym_translation('ACYM_DAY'),
        ],
        'relative_'.$name,
        null,
        'class="acym__select relativetype"'
    );

    $result .= '</div>
                <div class="cell small-5">';

    $result .= acym_select(
        [
            '-' => acym_translation('ACYM_IN_PAST'),
            '+' => acym_translation('ACYM_IN_FUTURE'),
        ],
        'relativewhen_'.$name,
        $relativeDefault,
        'class="acym__select relativewhen"'
    );
    $result .= '</div>
            </div>';

    // Specific date option
    $result .= '<div class="date_rs_selection_choice date_rs_selection_specific grid-x" style="display: none;">
                    <div class="cell auto"></div>
                    <div class="cell shrink">
                        <input type="text" name="specific_'.acym_escape($name).'" class="acy_date_picker" readonly>
                    </div>
                    <div class="cell auto"></div>
                </div>
                <div class="cell grid-x grid-margin-x">
                    <div class="cell auto"></div>
                    <button type="button" class="cell medium-4 button button-secondary acym__button__clear__time" data-close>'.acym_translation('ACYM_CLEAR').'</button>
                    <button type="button" class="cell medium-4 button acym__button__set__time" data-close>'.acym_translation('ACYM_APPLY').'</button>
                    <div class="cell auto"></div>
                </div>';

    // Close container
    $result .= '</div>';

    // Input in which the value is
    $id = preg_replace('#[^a-z0-9_]#i', '', $name);
    if (is_numeric($value)) {
        $months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

        $replaceValues = [];
        foreach ($months as $oneMonth) {
            $replaceValues[] = substr(acym_translation('ACYM_'.strtoupper($oneMonth)), 0, 3);
        }

        $shownValue = str_replace($months, $replaceValues, acym_date($value, 'd F Y H:i'));
    } else {
        $shownValue = $value;
    }
    $result = '<input data-rs="'.acym_escape($id).'" type="hidden" name="'.acym_escape($name).'" value="'.acym_escape($value).'">'.acym_modal(
            '<input data-open="'.acym_escape($id).'" class="rs_date_field '.$class.'" '.$attributes.' type="text" value="'.acym_escape($shownValue).'" readonly>',
            $result,
            $id,
            '',
            '',
            false,
            false
        );

    return $result;
}

/**
 * Function to display the time based on the database value as int
 */
function acym_getDate($time = 0, $format = '%d %B %Y %H:%M')
{
    if (empty($time)) return '';

    if (is_numeric($format)) {
        $format = acym_translation('ACYM_DATE_FORMAT_LC'.$format);
    }

    $format = str_replace(
        ['%A', '%d', '%B', '%m', '%Y', '%y', '%H', '%M', '%S', '%a', '%I', '%p', '%w'],
        ['l', 'd', 'F', 'm', 'Y', 'y', 'H', 'i', 's', 'D', 'h', 'a', 'w'],
        $format
    );

    //Not sure why but sometimes it fails... so lets try to catch the error...
    try {
        return acym_date($time, $format, false);
    } catch (Exception $e) {
        return date($format, $time);
    }
}

function acym_replaceDate($mydate, $display = false)
{
    if (strpos($mydate, '[time]') === false) {
        if (is_numeric($mydate) && $display) return acym_date($mydate, 'Y-m-d H:i:s');

        return $mydate;
    }

    if ($mydate == '[time]' && $display) return acym_translation('ACYM_NOW');

    $mydate = str_replace('[time]', time(), $mydate);
    $operators = ['+', '-'];
    foreach ($operators as $oneOperator) {
        if (strpos($mydate, $oneOperator) === false) continue;

        $dateArray = explode($oneOperator, $mydate);
        if ($oneOperator == '+') {
            if ($display) {
                $mydate = acym_translation_sprintf('ACYM_AFTER_DATE', acym_secondsToTime(intval($dateArray[1])));
            } else {
                $mydate = intval($dateArray[0]) + intval($dateArray[1]);
            }
        } elseif ($oneOperator == '-') {
            if ($display) {
                $mydate = acym_translation_sprintf('ACYM_BEFORE_DATE', acym_secondsToTime(intval($dateArray[1])));
            } else {
                $mydate = intval($dateArray[0]) - intval($dateArray[1]);
            }
        }
    }

    return $mydate;
}

function acym_secondsToTime($seconds)
{
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");

    return $dtF->diff($dtT)->format('%a day(s) %h h, %i min');
}

function acym_displayDateFormat($format, $name = 'date', $default = '', $attributes = '')
{
    $attributes = empty($attributes) ? 'class="acym__custom__fields__select__form acym__select"' : $attributes;
    $return = '<div class="cell grid-x grid-margin-x">';
    $days = ['' => acym_translation('ACYM_DAY')];
    for ($i = 1 ; $i <= 31 ; $i++) {
        $days[$i < 10 ? '0'.$i : $i] = $i < 10 ? '0'.$i : $i;
    }
    $month = [
        '' => acym_translation('ACYM_MONTH'),
        '01' => acym_translation('ACYM_JANUARY'),
        '02' => acym_translation('ACYM_FEBRUARY'),
        '03' => acym_translation('ACYM_MARCH'),
        '04' => acym_translation('ACYM_APRIL'),
        '05' => acym_translation('ACYM_MAY'),
        '06' => acym_translation('ACYM_JUNE'),
        '07' => acym_translation('ACYM_JULY'),
        '08' => acym_translation('ACYM_AUGUST'),
        '09' => acym_translation('ACYM_SEPTEMBER'),
        '10' => acym_translation('ACYM_OCTOBER'),
        '11' => acym_translation('ACYM_NOVEMBER'),
        '12' => acym_translation('ACYM_DECEMBER'),
    ];
    $year = ['' => acym_translation('ACYM_YEAR')];
    for ($i = 1900 ; $i <= (acym_date('now', 'Y') + 10) ; $i++) {
        $year[$i] = $i;
    }
    $formatToDisplay = explode('%', $format);
    $defaultDate = empty($default) ? '' : explode('/', $default);

    $i = 0;
    unset($formatToDisplay[0]);
    foreach ($formatToDisplay as $one) {
        if ($one == 'd') {
            $return .= '<div class="medium-3 cell">'.acym_select($days, $name, empty($default) ? '' : $defaultDate[$i], $attributes, 'value', 'text', $name.'-'.$one).'</div>';
        }
        if ($one == 'm') {
            $return .= '<div class="medium-5 cell">'.acym_select($month, $name, empty($default) ? '' : $defaultDate[$i], $attributes, 'value', 'text', $name.'-'.$one).'</div>';
        }
        if ($one == 'y') {
            $return .= '<div class="medium-4 cell">'.acym_select($year, $name, empty($default) ? '' : $defaultDate[$i], $attributes, 'value', 'text', $name.'-'.$one).'</div>';
        }
        $i++;
    }

    $return .= '</div>';

    return $return;
}

function acym_getTimeFromUTCDate($date)
{
    return strtotime($date) + date('Z');
}

function acym_getTimeFromCMSDate($date)
{
    return acym_getTimeFromUTCDate($date) - acym_getTimeOffsetCMS();
}

function acym_getTime($date)
{
    return acym_getTimeFromCMSDate($date);
}
