<?php

namespace AcyMailing\Controllers;

use AcyMailing\Helpers\HeaderHelper;
use AcyMailing\Core\AcymController;
use AcyMailing\Helpers\UpdatemeHelper;

class UpdateController extends AcymController
{
    public function checkForNewVersion(): void
    {
        UpdatemeHelper::getLicenseInfo(true);
        ob_start();
        $headerHelper = new HeaderHelper();
        $headerHelper->displayVersionArea(true);
        $versionArea = ob_get_clean();

        acym_sendAjaxResponse(
            '',
            [
                'content' => $versionArea,
                'lastcheck' => acym_date('now', 'ACYM_DATE_FORMAT_LC2'),
            ]
        );
    }
}
