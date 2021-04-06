<?php

namespace AcyMailing\Controllers;

use AcyMailing\Libraries\acymController;

class GoproController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_GOPRO')] = acym_completeLink('gopro');
    }

    public function listing()
    {
        acym_setVar('layout', 'gopro');
        parent::display();
    }
}