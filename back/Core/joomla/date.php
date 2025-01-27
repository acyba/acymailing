<?php

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

function acym_getTimeOffsetCMS()
{
    static $timeoffset = null;
    if ($timeoffset === null) {

        $dateC = Factory::getDate(
            'now',
            acym_getCMSConfig('offset')
        );
        $timeoffset = $dateC->getOffsetFromGMT(true) * 3600;
    }

    return $timeoffset;
}

function acym_dateTimeCMS($time)
{
    return HTMLHelper::_('date', $time, 'Y-m-d H:i:s', null);
}

function acym_getDateTimeFormat($default = 'ACYM_DATE_FORMAT_LC2')
{
    return acym_translation($default);
}
