<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Controllers\EntitySelectController;

class FrontentityselectController extends EntitySelectController
{
    public function __construct()
    {
        parent::__construct();

        $this->publicFrontTasks = [
            'loadEntityFront',
        ];
    }
}
