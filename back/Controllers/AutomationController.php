<?php

namespace AcyMailing\Controllers;

use AcyMailing\Core\AcymController;
use AcyMailing\Controllers\Automations\Listing;
use AcyMailing\Controllers\Automations\Info;
use AcyMailing\Controllers\Automations\Condition;
use AcyMailing\Controllers\Automations\Filter;
use AcyMailing\Controllers\Automations\Action;
use AcyMailing\Controllers\Automations\Summary;
use AcyMailing\Controllers\Automations\MassAction;

class AutomationController extends AcymController
{
    use Listing;
    use Info;
    use Condition;
    use Filter;
    use Action;
    use Summary;
    use MassAction;

    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_AUTOMATIONS')] = acym_completeLink('automation');
        $this->loadScripts = [
            'info' => ['datepicker'],
            'condition' => ['datepicker'],
            'action' => ['datepicker'],
            'filter' => ['datepicker', 'vue-applications' => ['modal_users_summary']],
        ];
        acym_setVar('edition', '1');
    }
}
