<?php
include ACYM_CONTROLLER.'entitySelect.php';

class FrontentityselectController extends EntitySelectController
{
    public function __construct()
    {
        parent::__construct();
        $this->authorizedFrontTasks = [
            'loadEntityFront',
            'loadEntityBack',
            'loadEntitySelect',
            'getEntityNumber',
        ];
    }
}
