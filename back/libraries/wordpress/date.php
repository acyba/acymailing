<?php

function acym_getTimeOffsetCMS()
{
    static $timeoffset = null;
    if ($timeoffset === null) {
        $timeoffset = acym_getCMSConfig('offset');

        if (!is_numeric($timeoffset)) {
            $timezone = new DateTimeZone($timeoffset);
            $timeoffset = $timezone->getOffset(new DateTime());
        }
    }

    return $timeoffset;
}

function acym_dateTimeCMS($time)
{
    return date('Y-m-d H:i:s', $time);
}

function acym_getDateTimeFormat($default = '')
{
    $noTimeDate = [
        'ACYM_DATE_FORMAT_LC1',
        'ACYM_DATE_FORMAT_LC3',
        'ACYM_DATE_FORMAT_LC4',
        'ACYM_DATE_FORMAT_LC5',
    ];

    $dateFormat = get_option('date_format', 'Y-m-d');

    $timeFormat = in_array($default, $noTimeDate) ? '' : ' '.get_option('time_format', 'H:i');

    return $dateFormat.$timeFormat;
}
