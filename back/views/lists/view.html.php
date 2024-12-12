<?php

namespace AcyMailing\Views;

use AcyMailing\Controllers\ListsController;
use AcyMailing\Libraries\acymView;

/**
 * Class ListsViewLists
 */
class ListsViewLists extends acymView
{

    public $disableTabs = [];

    public function __construct()
    {
        parent::__construct();

        $this->tabs = [
            ListsController::LIST_EDITION_TABS_GENERAL => 'ACYM_GENERAL',
            ListsController::LIST_EDITION_TABS_SUBSCRIBERS => 'ACYM_SUBSCRIBERS',
            ListsController::LIST_EDITION_TABS_UNSUBSCRIPTIONS => 'ACYM_UNSUBSCRIPTIONS',
        ];

        $this->disableTabs = [
            ListsController::LIST_EDITION_TABS_SUBSCRIBERS,
            ListsController::LIST_EDITION_TABS_UNSUBSCRIPTIONS,
        ];
    }
}
