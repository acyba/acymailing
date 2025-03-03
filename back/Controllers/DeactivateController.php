<?php

namespace AcyMailing\Controllers;

use AcyMailing\Helpers\UpdatemeHelper;
use AcyMailing\Core\AcymController;

class DeactivateController extends AcymController
{
    public function saveFeedbackAjax(): void
    {
        $data = [
            'reason' => acym_getVar('string', 'reason', ''),
            'comment' => acym_getVar('string', 'otherReason', ''),
            'email' => acym_getVar('string', 'email', ''),
        ];

        UpdatemeHelper::call('public/feedback', 'POST', $data);
        exit;
    }
}
