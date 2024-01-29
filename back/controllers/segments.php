<?php

namespace AcyMailing\Controllers;

use AcyMailing\Libraries\acymController;
use AcyMailing\Controllers\Segments\Listing;
use AcyMailing\Controllers\Segments\Edition;
use AcyMailing\Controllers\Segments\Campaign;

class SegmentsController extends acymController
{
    use Listing;
    use Edition;
    use Campaign;

    const FLAG_USERS = -1;
    const FLAG_EXPORT_USERS = -2;
    const FLAG_COUNT = -3;

    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_SEGMENTS')] = acym_completeLink('segments');
        $this->loadScripts = [
            'edit' => ['datepicker', 'vue-applications' => ['modal_users_summary']],
        ];
    }
}
