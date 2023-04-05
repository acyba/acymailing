<?php

namespace AcyMailing\Controllers;

use AcyMailing\Libraries\acymController;
use AcyMailing\Controllers\Dashboard\Listing;
use AcyMailing\Controllers\Dashboard\Walkthrough;
use AcyMailing\Controllers\Dashboard\Migration;

class DashboardController extends acymController
{
    var $errorMailer;

    use Listing;
    use Walkthrough;
    use Migration;

    public function __construct()
    {
        parent::__construct();

        $this->loadScripts = [
            'walk_through' => ['editor-wysid'],
        ];
    }

    public function upgrade()
    {
        acym_setVar('layout', 'upgrade');

        $version = acym_getVar('string', 'version', 'enterprise');

        $data = ['version' => $version];

        parent::display($data);
    }

    public function features()
    {
        if (!file_exists(ACYM_NEW_FEATURES_SPLASHSCREEN)) {
            $this->listing();

            return;
        }

        ob_start();
        include ACYM_NEW_FEATURES_SPLASHSCREEN;
        $data = [
            'content' => ob_get_clean(),
        ];

        if (!@unlink(ACYM_NEW_FEATURES_SPLASHSCREEN)) {
            $this->listing();

            return;
        }

        acym_setVar('layout', 'features');

        parent::display($data);
    }

    public function acychecker()
    {
        acym_setVar('layout', 'acychecker');

        parent::display();
    }
}
