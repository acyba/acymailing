<?php

namespace AcyMailing\Views\Scenarios;

use AcyMailing\Core\AcymView;

class ScenariosView extends AcymView
{
    public function __construct()
    {
        parent::__construct();

        $this->steps = [
            'editScenario' => 'ACYM_DESIGN',
            'performances' => 'ACYM_PERFORMANCES',
        ];
    }
}
