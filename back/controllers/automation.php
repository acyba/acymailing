<?php

namespace AcyMailing\Controllers;

use AcyMailing\Libraries\acymController;
use AcyMailing\Controllers\Automations\Listing;
use AcyMailing\Controllers\Automations\Info;
use AcyMailing\Controllers\Automations\Condition;
use AcyMailing\Controllers\Automations\Filter;
use AcyMailing\Controllers\Automations\Action;
use AcyMailing\Controllers\Automations\Summary;
use AcyMailing\Controllers\Automations\MassAction;

class AutomationController extends acymController
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

    public function switches($matches)
    {
        return '__numand__'.$matches[1].$matches[2].'__numand__'.$matches[3].'__numand__'.$matches[4].'__numand__'.$matches[5];
    }
}
