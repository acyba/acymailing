<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Controllers\FileController;

class FrontfileController extends FileController
{
    public function __construct()
    {
        parent::__construct();

        $this->allowedTasks = [
            'index.php?option=com_acym&view=frontcampaigns&layout=campaigns' => [
                'select',
            ],
        ];
    }
}
