<?php

namespace AcyMailing\Controllers\Automations;

use AcyMailing\Classes\ActionClass;
use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\ConditionClass;
use AcyMailing\Classes\StepClass;
use AcyMailing\Helpers\WorkflowHelper;

trait Summary
{
    public function summary(): void
    {

        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }
    }

    public function activeAutomation(): void
    {
        $automationClass = new AutomationClass();
        $automation = $automationClass->getOneById(acym_getVar('int', 'id'));
        $automation->active = 1;
        $automationId = $automationClass->save($automation);
        if (!empty($automationId)) {
            acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'));
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
        }
        $this->listing();
    }
}
