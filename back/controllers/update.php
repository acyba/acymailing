<?php

namespace AcyMailing\Controllers;

use AcyMailing\Helpers\HeaderHelper;
use AcyMailing\Libraries\acymController;

class UpdateController extends acymController
{
    //Function called in Ajax that's why we exit
    public function checkForNewVersion()
    {
        $lastlicensecheck = acym_checkVersion(true);

        $headerHelper = new HeaderHelper();
        $myAcyArea = $headerHelper->checkVersionArea();

        echo json_encode(['content' => $myAcyArea, 'lastcheck' => acym_date($lastlicensecheck, 'Y/m/d H:i')]);
        exit;
    }
}
