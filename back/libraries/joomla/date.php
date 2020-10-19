<?php

function acym_getTimeOffsetCMS()
{
    static $timeoffset = null;
    if ($timeoffset === null) {

        $dateC = JFactory::getDate(
            'now',
            acym_getCMSConfig('offset')
        );
        $timeoffset = $dateC->getOffsetFromGMT(true) * 3600;
    }

    return $timeoffset;
}
