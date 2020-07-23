<?php

class updateController extends acymController
{
    //Function called in Ajax that's why we exit
    public function checkForNewVersion()
    {
        $lastlicensecheck = acym_checkVersion(true);

        $headerHelper = acym_get('helper.header');
        $myAcyArea = $headerHelper->checkVersionArea();

        echo json_encode(['content' => $myAcyArea, 'lastcheck' => acym_date($lastlicensecheck, 'Y/m/d H:i')]);
        exit;
    }
}
