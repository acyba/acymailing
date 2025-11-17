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

    public function display(string $map, int $value, int $type = 1, string $inputClass = '', string $hiddenInputClass = ''): string
    {
        static $num = 0;
        $num++;

        $js = '
        function updateDelay'.$num.'(){
            delayvar = window.document.getElementById("delayvar'.$num.'");
            delaytype = window.document.getElementById("delaytype'.$num.'").value;
            delayvalue = window.document.getElementById("delayvalue'.$num.'");
            realValue = delayvalue.value;
            if(delaytype === "minute"){ realValue = realValue*60; }
            if(delaytype === "hour"){ realValue = realValue*3600; }
            if(delaytype === "day"){ realValue = realValue*86400; }
            if(delaytype === "week"){ realValue = realValue*604800; }
            if(delaytype === "month"){ realValue = realValue*2592000; }
            delayvar.value = realValue;
            delayvar.dispatchEvent(new Event("change"));
        }';
        $updateFunction = 'updateDelay'.$num.'();';
        acym_addScript(true, $js);

        $values = [];

        if ($type === self::TYPE_SECONDS_MINUTES) {
            $values[] = acym_selectOption('second', 'ACYM_SECONDS');
            $values[] = acym_selectOption('minute', 'ACYM_MINUTES');
        } elseif ($type === self::TYPE_MINUTES_HOURS_DAYS_WEEKS) {
            $values[] = acym_selectOption('minute', 'ACYM_MINUTES');
            $values[] = acym_selectOption('hour', 'ACYM_HOURS');
            $values[] = acym_selectOption('day', 'ACYM_DAYS');
            $values[] = acym_selectOption('week', 'ACYM_WEEKS');
        } elseif ($type === self::TYPE_MINUTES_HOURS) {
            $values[] = acym_selectOption('minute', 'ACYM_MINUTES');
            $values[] = acym_selectOption('hour', 'ACYM_HOURS');
        } elseif ($type === self::TYPE_HOURS_DAYS_WEEKS_MONTHS) {
            $values[] = acym_selectOption('hour', 'ACYM_HOURS');
            $values[] = acym_selectOption('day', 'ACYM_DAYS');
            $values[] = acym_selectOption('week', 'ACYM_WEEKS');
            $values[] = acym_selectOption('month', 'ACYM_MONTHS');
        } elseif ($type === self::TYPE_WEEKS_MONTHS) {
            $values[] = acym_selectOption('week', 'ACYM_WEEKS');
            $values[] = acym_selectOption('month', 'ACYM_MONTHS');
        }

        $return = $this->get($value, $type);
        $delayValue = '<input class="intext_input '.acym_escape($inputClass).'" 
                            onchange="'.$updateFunction.'" 
                            type="number" 
                            min="0" 
                            id="delayvalue'.$num.'" 
                            value="'.acym_escape($return->value).'" /> ';
        $delayVar = '<input class="'.acym_escape($hiddenInputClass).'" type="hidden" name="'.acym_escape($map).'" id="delayvar'.$num.'" value="'.$value.'"/>';

        return $delayValue.acym_select(
                $values,
                'delaytype'.$num,
                $return->type,
                [
                    'class' => 'intext_select',
                    'onchange' => $updateFunction,
                ],
                'value',
                'text',
                'delaytype'.$num
            ).$delayVar;
    }

    public function get(int $value, int $type): object
    {

        $return = new \stdClass();

        $return->value = $value;
        if ($type === 0) {
            $return->type = 'second';
            $return->typeText = acym_translation('ACYM_SECONDS');
        } else {
            $return->type = 'minute';
            $return->typeText = acym_translation('ACYM_MINUTES');
        }

        if ($return->value >= 60 && $return->value % 60 == 0) {
            $return->value = (int)$return->value / 60;
            $return->type = 'minute';
            $return->typeText = acym_translation('ACYM_MINUTES');
            if ($type !== 0 && $return->value >= 60 && $return->value % 60 == 0) {
                $return->type = 'hour';
                $return->typeText = acym_translation('ACYM_HOURS');
                $return->value = $return->value / 60;
                if ($type !== 2 && $return->value >= 24 && $return->value % 24 == 0) {
                    $return->type = 'day';
                    $return->typeText = acym_translation('ACYM_DAYS');
                    $return->value = $return->value / 24;
                    if ($type >= 3 && $return->value >= 30 && $return->value % 30 == 0) {
                        $return->type = 'month';
                        $return->typeText = acym_translation('ACYM_MONTHS');
                        $return->value = $return->value / 30;
                    } elseif ($return->value >= 7 && $return->value % 7 == 0) {
                        $return->type = 'week';
                        $return->typeText = acym_translation('ACYM_WEEKS');
                        $return->value = $return->value / 7;
                    }
                }
            }
        }

        return $return;
    }
}
