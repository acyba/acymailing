<?php

namespace AcyMailing\Controllers;

use AcyMailing\Libraries\acymController;

class DeactivateController extends acymController
{
    //Function called in Ajax that's why we exit
    public function saveFeedback()
    {
        $reason = acym_getVar('string', 'reason', '');
        $otherReason = acym_getVar('string', 'otherReason', '');

        $url = ACYM_FEEDBACK_URL.'saveFeedback';
        acym_makeCurlCall($url, ['reason' => $reason, 'otherReason' => $otherReason]);
        exit;
    }
}
