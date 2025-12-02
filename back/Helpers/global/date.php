<?php

function acym_replaceDateTags(string $value): string
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

function acym_dateField(string $name, $value = '', string $class = '', string $attributes = '', string $relativeDefault = '-'): string
{
    // Open container
    $result = '<div class="grid-x margin-y date_rs_selection_popup">';

    // Choice of relative / specific date
    $result .= '<div class="cell grid-x">';
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
    $result .= '<div class="cell date_rs_selection_choice date_rs_selection_relative grid-x grid-margin-x align-center">
                    <div class="cell medium-4">
                        <input type="number" class="relativenumber" value="0">
                    </div>
                    <div class="cell medium-4">';
    $result .= acym_select(
        [
            '60' => acym_translation('ACYM_MINUTES'),
            '3600' => acym_translation('ACYM_HOUR'),
            '86400' => acym_translation('ACYM_DAY'),
        ],
        'relative_'.$name,
        null,
        ['class' => 'acym__select relativetype']
    );

    $result .= '</div>
                <div class="cell medium-4">';

    $result .= acym_select(
        [
            '-' => acym_translation('ACYM_IN_PAST'),
            '+' => acym_translation('ACYM_IN_FUTURE'),
        ],
        'relativewhen_'.$name,
        $relativeDefault,
        ['class' => 'acym__select relativewhen']
    );
    $result .= '</div>
            </div>';

    // Specific date option
    $result .= '<div class="cell date_rs_selection_choice date_rs_selection_specific grid-x align-center acym_vcenter" style="display: none;">
                    <span class="cell shrink margin-right-1">'.acym_translation('ACYM_CHOOSE_DATE').'</span>
                    <div class="cell shrink">
                        <input type="text" name="specific_'.acym_escape($name).'" class="acy_date_picker" data-acym-translate="0" readonly>
                    </div>
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
    $id = 'acym_'.preg_replace('#[^a-z0-9_]#i', '', $name);
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
            [],
            [],
            false,
            false
        );

    return $result;
}

/**
 * Function to display the time based on the database value as int
 */
function acym_getDate($time = 0, string $format = '%d %B %Y %H:%M')
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

function acym_replaceDate(string $mydate, bool $display = false): string
{
    if (strpos($mydate, '[time]') === false) {
        if (is_numeric($mydate) && $display) {
            return acym_date($mydate, 'Y-m-d H:i:s');
        }

        return $mydate;
    }

    if ($mydate === '[time]' && $display) {
        return acym_translation('ACYM_NOW');
    }

    $mydate = str_replace('[time]', time(), $mydate);
    $operators = ['+', '-'];
    foreach ($operators as $oneOperator) {
        if (strpos($mydate, $oneOperator) === false) continue;

        $dateArray = explode($oneOperator, $mydate);
        if ($oneOperator == '+') {
            if ($display) {
                $mydate = acym_translationSprintf('ACYM_AFTER_DATE', acym_secondsToTime(intval($dateArray[1])));
            } else {
                $mydate = intval($dateArray[0]) + intval($dateArray[1]);
            }
        } elseif ($oneOperator == '-') {
            if ($display) {
                $mydate = acym_translationSprintf('ACYM_BEFORE_DATE', acym_secondsToTime(intval($dateArray[1])));
            } else {
                $mydate = intval($dateArray[0]) - intval($dateArray[1]);
            }
        }
    }

    return $mydate;
}

function acym_secondsToTime(int $seconds): string
{
    $dtF = new \DateTime('@0');
    $dtT = new \DateTime("@$seconds");

    return $dtF->diff($dtT)->format('%a day(s) %h h, %i min');
}

function acym_displayDateFormat(string $format, string $name = 'date', string $default = '', array $attributes = [], bool $returnHtml = true): string
{
    if (empty($attributes)) {
        $attributes = [
            'class' => 'acym__custom__fields__select__form acym__select',
        ];
    }

    $return = [];
    if ($returnHtml) {
        $return[] = '<div class="cell grid-x grid-margin-x">';
    }

    $days = [
        '' => acym_translation('ACYM_DAY'),
    ];
    for ($i = 1; $i <= 31; $i++) {
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

    $year = [
        '' => acym_translation('ACYM_YEAR'),
    ];
    for ($i = 1900; $i <= ((int)acym_date('now', 'Y') + 10); $i++) {
        $year[$i] = $i;
    }

    $formatToDisplay = explode('%', $format);
    $defaultDate = empty($default) ? [] : explode('-', $default);

    unset($formatToDisplay[0]);
    foreach ($formatToDisplay as $one) {
        if ($one === 'd') {
            if ($returnHtml) {
                $return[] = '<div class="medium-3 margin-left-0 cell">'.acym_select(
                        $days,
                        $name,
                        empty($defaultDate[2]) || $defaultDate[2] === '00' ? '' : $defaultDate[2],
                        $attributes,
                        'value',
                        'text',
                        $name.'-'.$one
                    ).'</div>';
            } elseif (!empty($defaultDate[2]) && $defaultDate[2] !== '00') {
                $return[] = $defaultDate[2];
            }
        }

        if ($one === 'm') {
            if ($returnHtml) {
                $return[] = '<div class="medium-5 cell">'.acym_select(
                        $month,
                        $name,
                        empty($defaultDate[1]) || $defaultDate[1] === '00' ? '' : $defaultDate[1],
                        $attributes,
                        'value',
                        'text',
                        $name.'-'.$one
                    ).'</div>';
            } elseif (!empty($defaultDate[1]) && $defaultDate[1] !== '00') {
                $return[] = $month[$defaultDate[1]];
            }
        }

        if ($one === 'y') {
            if ($returnHtml) {
                $return[] = '<div class="medium-4 margin-right-0 cell">'.acym_select(
                        $year,
                        $name,
                        empty($defaultDate[0]) || $defaultDate[0] === '0000' ? '' : $defaultDate[0],
                        $attributes,
                        'value',
                        'text',
                        $name.'-'.$one
                    ).'</div>';
            } elseif (!empty($defaultDate[0]) && $defaultDate[0] !== '0000') {
                $return[] = $defaultDate[0];
            }
        }
    }

    if ($returnHtml) {
        $return[] = '</div>';

        return implode('', $return);
    } else {
        return implode(' ', $return);
    }
}

function acym_getTimeFromUTCDate(?string $date): int
{
    // Accept null/empty inputs gracefully and return 0 when no valid date is provided to avoid error.
    if (empty($date)) {
        return 0;
    }

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return 0;
    }

    return $timestamp + date('Z');
}

function acym_getTimeFromCMSDate($date)
{
    return acym_getTimeFromUTCDate($date) - acym_getTimeOffsetCMS();
}

function acym_getTime($date)
{
    return acym_getTimeFromCMSDate($date);
}

function acym_date($time = 'now', $format = null, bool $useTz = true, bool $translate = true): string
{
    if ($time === 'now') {
        $time = time();
    }

    if (is_numeric($time)) {
        $time = acym_dateTimeCMS($time);
    }

    if (!$format || (strpos($format, 'ACYM_DATE_FORMAT') !== false && acym_translation($format) == $format)) {
        $format = 'ACYM_DATE_FORMAT_LC1';
    }
    if (strpos($format, 'ACYM_DATE') !== false) {
        $format = acym_translation($format);
    }

    //Don't use timezone
    if ($useTz === false) {
        $date = new DateTime($time);

        if ($translate) {
            return acym_translateDate($date->format($format));
        } else {
            return $date->format($format);
        }
    } else {
        //We replace the . with : WordPress give format like UTC+5.45, but we want something like UTC+5:45
        $cmsOffset = str_replace('.', ':', acym_getCMSConfig('offset'));

        $timezone = new DateTimeZone($cmsOffset);

        if (!is_numeric($cmsOffset)) {
            $cmsOffset = $timezone->getOffset(new DateTime());
        }

        if ($translate) {
            return acym_translateDate(date($format, strtotime($time) + $cmsOffset));
        } else {
            return date($format, strtotime($time) + $cmsOffset);
        }
    }
}

function acym_translateDate(string $date): string
{
    $map = [
        'January' => 'ACYM_JANUARY',
        'February' => 'ACYM_FEBRUARY',
        'March' => 'ACYM_MARCH',
        'April' => 'ACYM_APRIL',
        'May' => 'ACYM_MAY',
        'June' => 'ACYM_JUNE',
        'July' => 'ACYM_JULY',
        'August' => 'ACYM_AUGUST',
        'September' => 'ACYM_SEPTEMBER',
        'October' => 'ACYM_OCTOBER',
        'November' => 'ACYM_NOVEMBER',
        'December' => 'ACYM_DECEMBER',
        'Monday' => 'ACYM_MONDAY',
        'Tuesday' => 'ACYM_TUESDAY',
        'Wednesday' => 'ACYM_WEDNESDAY',
        'Thursday' => 'ACYM_THURSDAY',
        'Friday' => 'ACYM_FRIDAY',
        'Saturday' => 'ACYM_SATURDAY',
        'Sunday' => 'ACYM_SUNDAY',
    ];

    foreach ($map as $english => $translationKey) {
        $translation = acym_translation($translationKey);
        if ($translation === $translationKey) {
            continue;
        }

        $date = preg_replace('#'.preg_quote($english).'#i', $translation, $date);
        $date = preg_replace('#'.preg_quote(substr($english, 0, 3)).'#i', mb_substr($translation, 0, 3), $date);
    }

    return $date;
}

function acym_isDateValid($date): bool
{
    return $date !== '0000-00-00 00:00:00' && !empty($date);
}
