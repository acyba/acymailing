<?php

namespace AcyMailing\Views\Stats;

use AcyMailing\Core\AcymView;

/**
 * Class UsersViewUsers
 */
class StatsView extends AcymView
{
    public function __construct()
    {
        parent::__construct();

        $this->tabs = [
            'globalStats' => 'ACYM_OVERVIEW',
        ];
    }

    public function isMailSelected($mailId, $clickMap)
    {
    }
}
