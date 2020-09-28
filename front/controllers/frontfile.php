<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Controllers\FileController;

class FrontfileController extends FileController
{
    public function __construct()
    {
        $this->authorizedFrontTasks = ['select'];
        parent::__construct();
    }
}
