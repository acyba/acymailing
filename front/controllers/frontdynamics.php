<?php
include ACYM_CONTROLLER.'dynamics.php';

class FrontdynamicsController extends DynamicsController
{

    public function __construct()
    {
        $this->authorizedFrontTasks = ['popup', 'trigger', 'replaceDummy'];
        parent::__construct();
    }
}