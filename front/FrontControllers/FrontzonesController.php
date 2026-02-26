<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Controllers\ZonesController;

class FrontzonesController extends ZonesController
{
    public function __construct()
    {
        parent::__construct();

        $this->menuAlias = [
            'index.php?option=com_acym&view=frontcampaigns&layout=listing' => 'index.php?option=com_acym&view=frontcampaigns&layout=campaigns',
        ];
        $this->allowedTasks = [
            'index.php?option=com_acym&view=frontlists&layout=listing' => [
                'getForInsertion',
            ],
            'index.php?option=com_acym&view=frontcampaigns&layout=campaigns' => [
                'getForInsertion',
            ],
        ];
    }
}
