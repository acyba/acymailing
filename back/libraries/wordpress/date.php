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
