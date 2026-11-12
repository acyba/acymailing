<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Controllers\EntitySelectController;

class FrontentityselectController extends EntitySelectController
{
    public function __construct()
    {
        parent::__construct();

        $this->publicFrontTasks = [
            'loadEntityFront',
        ];
    }

    public function loadEntityFront(): void
    {
        if (empty(acym_currentUserId()) || !acym_level(ACYM_ENTERPRISE)) {
            acym_sendAjaxResponse(acym_translation('ACYM_UNAUTHORIZED_ACCESS'), [], false);
        }

        parent::loadEntityFront();
    }
}
