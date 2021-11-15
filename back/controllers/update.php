<?php

namespace AcyMailing\Controllers;

use AcyMailing\Helpers\HeaderHelper;
use AcyMailing\Libraries\acymController;

class UpdateController extends acymController
{
    public function checkForNewVersion()
    {
        $lastLicenseCheck = acym_checkVersion(true);
        $headerHelper = new HeaderHelper();

        acym_sendAjaxResponse(
            '',
            [
                'content' => $headerHelper->checkVersionArea(),
                'lastcheck' => acym_date($lastLicenseCheck, 'Y/m/d H:i'),
            ]
        );
    }
}
