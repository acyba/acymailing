<?php

namespace AcyMailing\Types;

use AcyMailing\Libraries\acymObject;

class DelayType extends acymObject
{
    var $values = [];
    var $num = 0;
    var $onChange = '';

    public function __construct()
    {
        parent::__construct();

        static $i = 0;
        $i++;
        $this->num = $i;

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
        acym_addScript(true, $js);
    }

    /**
     * type : 1 : minutes/hour/days/weeks ; 2 : minutes/hours ; 0 : seconds/minutes ; 3 : hours, day, week, months
     */
    public function display($map, $value, $type = 1)
    {
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
        $delayValue = '<input class="intext_input" onchange="updateDelay'.$this->num.'();'.$this->onChange.'" type="text" id="delayvalue'.$this->num.'" value="'.$return->value.'" /> ';
        $delayVar = '<input type="hidden" name="'.$map.'" id="delayvar'.$this->num.'" value="'.$value.'"/>';

        return $delayValue.acym_select(
                $this->values,
                'delaytype'.$this->num,
                $return->type,
                'class="intext_select" onchange="updateDelay'.$this->num.'();'.$this->onChange.'"',
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
        } else {
            $return->type = 'minute';
        }

        if ($return->value >= 60 && $return->value % 60 == 0) {
            $return->value = (int)$return->value / 60;
            $return->type = 'minute';
            if ($type != 0 && $return->value >= 60 && $return->value % 60 == 0) {
                $return->type = 'hour';
                $return->value = $return->value / 60;
                if ($type != 2 && $return->value >= 24 && $return->value % 24 == 0) {
                    $return->type = 'day';
                    $return->value = $return->value / 24;
                    if ($type >= 3 && $return->value >= 30 && $return->value % 30 == 0) {
                        $return->type = 'month';
                        $return->value = $return->value / 30;
                    } elseif ($return->value >= 7 && $return->value % 7 == 0) {
                        $return->type = 'week';
                        $return->value = $return->value / 7;
                    }
                }
            }
        }

        return $return;
    }
}
