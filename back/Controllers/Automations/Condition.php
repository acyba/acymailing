<?php

namespace AcyMailing\Controllers\Automations;

use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\ConditionClass;
use AcyMailing\Classes\StepClass;
use AcyMailing\Helpers\WorkflowHelper;

trait Condition
{
    public function condition(): void
    {
        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }

    }

    private function getSaveConditions(bool $isMassAction = false): array
    {
        $automationID = acym_getVar('int', 'id');
        $conditionId = acym_getVar('int', 'conditionId');
        $condition = acym_getVar('array', 'acym_condition', []);
        $conditionClass = new ConditionClass();

        $stepAutomationId = acym_getVar('int', 'stepAutomationId');

        if (!empty($conditionId)) {
            $condition['id'] = $conditionId;
        }

        $condition['conditions']['type_condition'] = acym_getVar('string', 'type_condition');

        if ($isMassAction) {
            acym_session();
            $_SESSION['massAction']['conditions'] = $condition['conditions'];

            return [];
        }

        $condition['conditions'] = json_encode($condition['conditions']);

        $condition['step_id'] = $stepAutomationId;

        foreach ($condition as $column => $value) {
            acym_secureDBColumn($column);
        }

        //We need an object to save it so we make a object
        $condition = (object)$condition;

        $condition->id = $conditionClass->save($condition);

        return [
            'automationId' => $automationID,
            'stepId' => $stepAutomationId,
            'conditionId' => $condition->id,
        ];
    }

    public function saveExitConditions(): void
    {
        $this->getSaveConditions();

        acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');

        $this->listing();
    }

    public function saveConditions(): void
    {
        $ids = $this->getSaveConditions();

        acym_setVar('id', $ids['automationId']);
        acym_setVar('stepId', $ids['stepId']);
        acym_setVar('conditionId', $ids['conditionId']);
        $this->action();
    }
}
