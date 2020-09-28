<?php

namespace AcyMailing\FrontViews;

use AcyMailing\Libraries\acymView;

class FrontusersViewFrontusers extends acymView
{
    public function __construct()
    {
        global $Itemid;
        $this->Itemid = $Itemid;

        parent::__construct();
    }
}
