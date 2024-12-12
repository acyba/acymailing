<?php

namespace AcyMailing\FrontViews;

use AcyMailing\Controllers\ListsController;
use AcyMailing\Libraries\acymView;

class FrontlistsViewFrontlists extends acymView
{
    public $disableTabs = [];

    public function __construct()
    {
        global $Itemid;
        $this->Itemid = $Itemid;

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
