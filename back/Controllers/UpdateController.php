<?php

namespace AcyMailing\Controllers;

use AcyMailing\Helpers\HeaderHelper;
use AcyMailing\Core\AcymController;

class UpdateController extends AcymController
{
    public function checkForNewVersion(): void
    {
        $lastLicenseCheck = acym_checkVersion(true);
        $headerHelper = new HeaderHelper();

        acym_sendAjaxResponse(
            '',
            [
                'content' => $headerHelper->checkVersionArea(true),
                'lastcheck' => acym_date(is_null($lastLicenseCheck) ? 'now' : $lastLicenseCheck, 'Y/m/d H:i'),
            ]
        );
    }
}
