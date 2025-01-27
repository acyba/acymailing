<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\MailClass;
use AcyMailing\Helpers\ScenarioHelper;
use AcyMailing\Core\AcymController;
use AcyMailing\Controllers\Scenarios\Listing;
use AcyMailing\Controllers\Scenarios\Edition;
use AcyMailing\Controllers\Scenarios\Performance;

class ScenariosController extends AcymController
{
    use Listing;
    use Edition;
    use Performance;

    private MailClass $mailClass;

    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_SCENARIO')] = acym_completeLink('scenarios');

        $this->loadScripts = [
            'edit_scenario' => ['datepicker'],
            'performances' => ['sankey'],
        ];

        $this->mailClass = new MailClass();
    }
}
