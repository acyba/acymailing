<?php

namespace AcyMailing\Views\Automation;

use AcyMailing\Core\AcymView;

class AutomationView extends AcymView
{
    public function __construct()
    {
        parent::__construct();

        $id = acym_getVar('int', 'id');
        $layout = acym_getVar('string', 'layout');
        if (empty($id) && $layout != 'info') {
            $this->steps = [
                'action' => 'ACYM_ACTIONS',
                'filter' => 'ACYM_ACTIONS_TARGETS',
                'summary' => 'ACYM_SUMMARY',
            ];
        } else {
            $this->steps = [
                'info' => 'ACYM_INFORMATION',
                'condition' => 'ACYM_CONDITIONS',
                'action' => 'ACYM_ACTIONS',
                'filter' => 'ACYM_ACTIONS_TARGETS',
                'summary' => 'ACYM_SUMMARY',
            ];
        }
    }
}
