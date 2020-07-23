<?php

class operatorinType extends acymObject
{
    var $values = [];
    var $class = 'acym__select';
    var $extra = '';

    public function __construct()
    {
        parent::__construct();

        $this->values[] = acym_selectOption('in', 'ACYM_IN');
        $this->values[] = acym_selectOption('not-in', 'ACYM_NOT_IN');
    }

    public function display($name, $valueSelected = '')
    {
        return acym_select($this->values, $name, $valueSelected, $this->extra.' class="'.$this->class.'"');
    }
}
