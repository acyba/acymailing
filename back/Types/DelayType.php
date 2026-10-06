<?php

namespace AcyMailing\Types;

use AcyMailing\Core\AcymObject;

class DelayType extends AcymObject
{
    const TYPE_SECONDS_MINUTES = 0;
    const TYPE_MINUTES_HOURS_DAYS_WEEKS = 1;
    const TYPE_MINUTES_HOURS = 2;
    const TYPE_HOURS_DAYS_WEEKS_MONTHS = 3;
    const TYPE_WEEKS_MONTHS = 4;

    const UNITS = [
        'month' => 2592000,
        'week' => 604800,
        'day' => 86400,
        'hour' => 3600,
        'minute' => 60,
        'second' => 1,
    ];

    const UNITS_TEXT = [
        'second' => 'ACYM_SECONDS',
        'minute' => 'ACYM_MINUTES',
        'hour' => 'ACYM_HOURS',
        'day' => 'ACYM_DAYS',
        'week' => 'ACYM_WEEKS',
        'month' => 'ACYM_MONTHS',
    ];

    const TYPES_UNITS = [
        self::TYPE_SECONDS_MINUTES => ['second', 'minute'],
        self::TYPE_MINUTES_HOURS_DAYS_WEEKS => ['minute', 'hour', 'day', 'week'],
        self::TYPE_MINUTES_HOURS => ['minute', 'hour'],
        self::TYPE_HOURS_DAYS_WEEKS_MONTHS => ['hour', 'day', 'week', 'month'],
        self::TYPE_WEEKS_MONTHS => ['week', 'month'],
    ];

    public function display(
        string $map,
        int    $value,
        int    $type = 1,
        string $inputClass = '',
        string $hiddenInputClass = ''
    ): void {
        static $num = 0;
        $num++;

        $values = [];
        foreach ($this->getUnits($type) as $unit) {
            $values[] = acym_selectOption($unit, self::UNITS_TEXT[$unit]);
        }

        $return = $this->get($value, $type);
        echo '<input class="intext_input acym__delay__value '.acym_escape($inputClass).'" 
                    type="number" 
                    min="0" 
                    id="delayvalue'.acym_escape($num).'" 
                    value="'.acym_escape($return->value).'" /> ';

        acym_select(
            $values,
            'delaytype'.$num,
            $return->type,
            [
                'class' => 'intext_select acym__delay__type',
            ],
            'value',
            'text',
            'delaytype'.$num,
            false,
            true
        );

        echo '<input class="acym__delay__hidden '.acym_escape($hiddenInputClass).'" type="hidden" name="'.acym_escape($map).'" id="delayvar'.acym_escape(
                $num
            ).'" value="'.acym_escape($value).'"/>';
    }

    public function get(int $value, int $type): object
    {
        $units = $this->getUnits($type);

        $return = new \stdClass();
        $return->type = reset($units);
        $return->value = $value;

        foreach (self::UNITS as $unit => $seconds) {
            if (!in_array($unit, $units, true) || $value < $seconds || $value % $seconds !== 0) continue;

            $return->type = $unit;
            $return->value = intdiv($value, $seconds);
            break;
        }

        $return->typeText = acym_translation(self::UNITS_TEXT[$return->type]);

        return $return;
    }

    private function getUnits(int $type): array
    {
        return self::TYPES_UNITS[$type] ?? self::TYPES_UNITS[self::TYPE_MINUTES_HOURS_DAYS_WEEKS];
    }
}
