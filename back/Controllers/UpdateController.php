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
        $headerHelper = new HeaderHelper();

        acym_sendAjaxResponse(
            '',
            [
                'content' => $headerHelper->checkVersionArea(true),
                'lastcheck' => acym_date('now', 'ACYM_DATE_FORMAT_LC2'),
            ]
        );
    }
}
