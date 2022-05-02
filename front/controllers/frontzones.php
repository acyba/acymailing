<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Controllers\ZonesController;

class FrontzonesController extends ZonesController
{

    public function __construct()
    {
        $this->authorizedFrontTasks = ['getForInsertion'];
        parent::__construct();
    }
}
