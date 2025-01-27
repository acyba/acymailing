<?php

namespace AcyMailing\Controllers;

use AcyMailing\Core\AcymController;
use AcyMailing\Controllers\Dashboard\Listing;
use AcyMailing\Controllers\Dashboard\Walkthrough;
use AcyMailing\Controllers\Dashboard\Migration;

class DashboardController extends AcymController
{
    var $errorMailer;

    use Listing;
    use Walkthrough;
    use Migration;

    public function __construct()
    {
        parent::__construct();

        $this->loadScripts = [
            'features' => ['vue-applications' => ['splashscreen']],
            'step_editor' => ['editor-wysid'],
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
        if (!file_exists(ACYM_NEW_FEATURES_SPLASHSCREEN_JSON)) {
            $this->listing();

            return;
        }

        $splashJson = acym_fileGetContent(ACYM_NEW_FEATURES_SPLASHSCREEN_JSON);
        $version = json_decode($splashJson);
        if (version_compare($this->config->get('previous_version', '{__VERSION__}'), $version->max_version, '>=')) {
            @unlink(ACYM_NEW_FEATURES_SPLASHSCREEN_JSON);
            $this->listing();

            return;
        }

        ob_start();
        include ACYM_NEW_FEATURES_SPLASHSCREEN;
        $data = [
            'content' => ob_get_clean(),
        ];

        if (!@unlink(ACYM_NEW_FEATURES_SPLASHSCREEN_JSON)) {
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
