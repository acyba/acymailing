<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Controllers\ConfigurationController;

class FrontconfigurationController extends ConfigurationController
{
    public function __construct()
    {
        parent::__construct();

        $this->allowedTasks = [
            'index.php?option=com_acym&view=frontlists&layout=listing' => [
                'getAjax',
            ],
            'index.php?option=com_acym&view=frontcampaigns&layout=campaigns' => [
                'getAjax',
            ],
        ];
    }
}
