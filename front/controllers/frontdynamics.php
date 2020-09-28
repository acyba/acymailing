<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Controllers\DynamicsController;

class FrontdynamicsController extends DynamicsController
{

    public function __construct()
    {
        $this->authorizedFrontTasks = ['popup', 'trigger', 'replaceDummy'];
        parent::__construct();
    }
}
