<?php

namespace AcyMailing\Views;

use AcyMailing\Libraries\acymView;

/**
 * Class UsersViewUsers
 */
class StatsViewStats extends acymView
{
    var $tabs = [];

    public function __construct()
    {
        parent::__construct();

        $this->tabs = [
            'globalStats' => 'ACYM_OVERVIEW',
        ];
    }

    public function isMailSelected($mailId)
    {
    }
}
