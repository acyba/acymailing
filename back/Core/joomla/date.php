<?php

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;

function acym_getTimeOffsetCMS(): int
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

function acym_dateTimeCMS(int $time)
{
    return HTMLHelper::_('date', $time, 'Y-m-d H:i:s', null);
}

function acym_getDateTimeFormat(string $default = 'ACYM_DATE_FORMAT_LC2'): string
{
    return acym_translation($default);
}
