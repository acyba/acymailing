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
