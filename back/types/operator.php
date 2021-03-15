<?php

namespace AcyMailing\Types;

use AcyMailing\Libraries\acymObject;

class OperatorType extends acymObject
{
    public $values = [];
    public $attributes = [];

    public function __construct()
    {
        parent::__construct();

        $this->values[] = acym_selectOption('=', '=');
        $this->values[] = acym_selectOption('!=', '!=');
        $this->values[] = acym_selectOption('>', '>');
        $this->values[] = acym_selectOption('<', '<');
        $this->values[] = acym_selectOption('>=', '>=');
        $this->values[] = acym_selectOption('<=', '<=');
        $this->values[] = acym_selectOption('BEGINS', 'ACYM_BEGINS_WITH');
        $this->values[] = acym_selectOption('END', 'ACYM_ENDS_WITH');
        $this->values[] = acym_selectOption('CONTAINS', 'ACYM_CONTAINS');
        $this->values[] = acym_selectOption('NOTCONTAINS', 'ACYM_NOT_CONTAINS');
        $this->values[] = acym_selectOption('LIKE', 'LIKE');
        $this->values[] = acym_selectOption('NOT LIKE', 'NOT LIKE');
        $this->values[] = acym_selectOption('REGEXP', 'REGEXP');
        $this->values[] = acym_selectOption('NOT REGEXP', 'NOT REGEXP');
        $this->values[] = acym_selectOption('IS NULL', 'IS NULL');
        $this->values[] = acym_selectOption('IS NOT NULL', 'IS NOT NULL');
    }

    public function display($name, $valueSelected = '', $class = 'acym__select')
    {
        if (empty($this->attributes['class'])) $this->attributes['class'] = $class;

        return acym_select($this->values, $name, $valueSelected, $this->attributes);
    }
}
