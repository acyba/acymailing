<?php
include ACYM_CONTROLLER.'file.php';

class FrontfileController extends FileController
{
    public function __construct()
    {
        $this->authorizedFrontTasks = ['select'];
        parent::__construct();
    }
}