<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Controllers\ConfigurationController;

class FrontconfigurationController extends ConfigurationController
{
    public function __construct()
    {
        parent::__construct();

        $this->menuAlias = [
            'index.php?option=com_acym&view=frontcampaigns&layout=listing' => 'index.php?option=com_acym&view=frontcampaigns&layout=campaigns',
        ];
    }
}
