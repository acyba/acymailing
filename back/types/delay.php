<?php

namespace AcyMailing\Types;

use AcyMailing\Libraries\acymObject;

class DelayType extends acymObject
{
    var $values = [];
    var $num = 0;
    var $onChange = '';

    /**
     * type : 1 : minutes/hour/days/weeks ; 2 : minutes/hours ; 0 : seconds/minutes ; 3 : hours, day, week, months
     */
    public function display($map, $value, $type = 1, $num = '', $inputClass = '')
    {
        static $i = 0;
        $i++;
        $this->num = $i;

        if (empty($num)) {
            $js = '
            function updateDelay'.$this->num.'(){
                delayvar = window.document.getElementById("delayvar'.$this->num.'");
                delaytype = window.document.getElementById("delaytype'.$this->num.'").value;
                delayvalue = window.document.getElementById("delayvalue'.$this->num.'");
                realValue = delayvalue.value;
                if(delaytype == "minute"){ realValue = realValue*60; }
                if(delaytype == "hour"){ realValue = realValue*3600; }
                if(delaytype == "day"){ realValue = realValue*86400; }
                if(delaytype == "week"){ realValue = realValue*604800; }
                if(delaytype == "month"){ realValue = realValue*2592000; }
                delayvar.value = realValue;
            }';
            $updateFunction = 'updateDelay'.$this->num.'();';
        } else {
            $js = '
            function updateDelayNum(num){
                delayvar = window.document.getElementById("delayvar"+num);
                delaytype = window.document.getElementById("delaytype"+num).value;
                delayvalue = window.document.getElementById("delayvalue"+num);
                realValue = delayvalue.value;
                if(delaytype == "minute"){ realValue = realValue*60; }
                if(delaytype == "hour"){ realValue = realValue*3600; }
                if(delaytype == "day"){ realValue = realValue*86400; }
                if(delaytype == "week"){ realValue = realValue*604800; }
                if(delaytype == "month"){ realValue = realValue*2592000; }
                delayvar.value = realValue;
            }';
            $updateFunction = 'updateDelayNum(\''.$num.'\');';
            $this->num = $num;
        }
        acym_addScript(true, $js);

        $this->values = [];

        if ($type == 0) {
            $this->values[] = acym_selectOption('second', 'ACYM_SECONDS');
            $this->values[] = acym_selectOption('minute', 'ACYM_MINUTES');
        } elseif ($type == 1) {
            $this->values[] = acym_selectOption('minute', 'ACYM_MINUTES');
            $this->values[] = acym_selectOption('hour', 'ACYM_HOURS');
            $this->values[] = acym_selectOption('day', 'ACYM_DAYS');
            $this->values[] = acym_selectOption('week', 'ACYM_WEEKS');
        } elseif ($type == 2) {
            $this->values[] = acym_selectOption('minute', 'ACYM_MINUTES');
            $this->values[] = acym_selectOption('hour', 'ACYM_HOURS');
        } elseif ($type == 3) {
            $this->values[] = acym_selectOption('hour', 'ACYM_HOURS');
            $this->values[] = acym_selectOption('day', 'ACYM_DAYS');
            $this->values[] = acym_selectOption('week', 'ACYM_WEEKS');
            $this->values[] = acym_selectOption('month', 'ACYM_MONTHS');
        } elseif ($type == 4) {
            $this->values[] = acym_selectOption('week', 'ACYM_WEEKS');
            $this->values[] = acym_selectOption('month', 'ACYM_MONTHS');
        }


        $return = $this->get($value, $type);
        $delayValue = '<input class="intext_input '.$inputClass.'" onchange="'.$updateFunction.$this->onChange.'" type="number" id="delayvalue'.$this->num.'" value="'.$return->value.'" /> ';
        $delayVar = '<input type="hidden" name="'.$map.'" id="delayvar'.$this->num.'" value="'.$value.'"/>';

        return $delayValue.acym_select(
                $this->values,
                'delaytype'.$this->num,
                $return->type,
                'class="intext_select" onchange="'.$updateFunction.$this->onChange.'"',
                'value',
                'text',
                'delaytype'.$this->num
            ).$delayVar;
    }

    public function get($value, $type)
    {

        $return = new \stdClass();

        $return->value = $value;
        if ($type == 0) {
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
            if ($type != 0 && $return->value >= 60 && $return->value % 60 == 0) {
                $return->type = 'hour';
                $return->typeText = acym_translation('ACYM_HOURS');
                $return->value = $return->value / 60;
                if ($type != 2 && $return->value >= 24 && $return->value % 24 == 0) {
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
