<?php

namespace AcyMailing\Types;

use AcyMailing\Libraries\acymObject;

class OperatorinType extends acymObject
{
    public $values = [];
    public $attributes = [];

    public function __construct()
    {
        parent::__construct();

        $this->values[] = acym_selectOption('in', 'ACYM_IN');
        $this->values[] = acym_selectOption('not-in', 'ACYM_NOT_IN');
    }

    public function display($name, $valueSelected = '')
    {
        $operatorType = new OperatorType();
        $operatorType->values = $this->values;
        $operatorType->attributes = $this->attributes;

        return $operatorType->display($name, $valueSelected);
    }
}
