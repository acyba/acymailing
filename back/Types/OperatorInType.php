<?php

namespace AcyMailing\Types;

use AcyMailing\Core\AcymObject;

class OperatorInType extends AcymObject
{
    public function display(string $name): string
    {
        $operatorType = new OperatorType();
        $operatorType->values = [
            acym_selectOption('in', 'ACYM_IN'),
            acym_selectOption('not-in', 'ACYM_NOT_IN'),
        ];

        return $operatorType->display($name);
    }
}
