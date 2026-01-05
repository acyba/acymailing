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

        //__START__enterprise_
        acym_setVar('layout', 'condition');
        acym_setVar('layout', 'condition');
        $id = acym_getVar('int', 'id');
        $stepId = acym_getVar('int', 'stepId');
        $automationClass = new AutomationClass();
        $stepClass = new StepClass();
        $conditionClass = new ConditionClass();
        $workflowHelper = new WorkflowHelper();

        $conditionObject = new \stdClass();
        $step = new \stdClass();

        if (!empty($id)) {
            $automation = $automationClass->getOneById($id);
            $this->breadcrumb[acym_translation($automation->name)] = acym_completeLink('automation&task=edit&step=condition&id='.$automation->id);

            $steps = $stepClass->getStepsByAutomationId($id);
            if (!empty($steps)) {
                $step = $steps[0];
                $conditions = $conditionClass->getConditionsByStepId($step->id);
                if (!empty($conditions)) $conditionObject = $conditions[0];
            }
        } else {
            $automation = new \stdClass();
            $this->breadcrumb[acym_translation('ACYM_NEW_MASS_ACTION')] = acym_completeLink('automation&task=edit&step=condition');

            $conditionObject->conditions = json_encode($_SESSION['massAction']['conditions']);
        }

        if (empty($conditionObject->conditions)) $conditionObject->conditions = '[]';

        $currentConditions = empty($conditionObject->conditions) ? [] : json_decode($conditionObject->conditions, true);
        $currentTriggers = empty($step->triggers) ? [] : json_decode($step->triggers, true);
        $typeCondition = 'classic';
        if (empty($currentConditions['type_condition'])) {
            $typeCondition = (empty($currentTriggers) || $currentTriggers['type_trigger'] != 'user') ? 'classic' : 'user';
        } elseif ($currentConditions['type_condition'] == $currentTriggers['type_trigger']) {
            $typeCondition = $currentConditions['type_condition'];
        } elseif ($currentConditions['type_condition'] == 'user' && $currentTriggers['type_trigger'] == 'classic') {
            $conditionObject->conditions = [];
            $typeCondition = $currentTriggers['type_trigger'];
        }

        $conditions = [
            'user' => [],
            'classic' => [],
        ];
        acym_trigger('onAcymDeclareConditions', [&$conditions]);


        $selectCondition = new \stdClass();
        $selectCondition->name = acym_translation('ACYM_SELECT_CONDITION');
        $selectCondition->option = '';
        array_unshift($conditions['both'], $selectCondition);

        $conditionsUser = ['name' => [], 'option' => []];
        $conditionsClassic = ['name' => [], 'option' => []];
        foreach ($conditions['both'] as $key => $condition) {
            $conditionsUser['name'][$key] = $condition->name;
            $conditionsUser['option'][$key] = $condition->option;
            $conditionsClassic['name'][$key] = $condition->name;
            $conditionsClassic['option'][$key] = $condition->option;
        }

        foreach ($conditions['user'] as $key => $condition) {
            $conditionsUser['name'][$key] = $condition->name;
            $conditionsUser['option'][$key] = $condition->option;
        }

        foreach ($conditions['classic'] as $key => $condition) {
            $conditionsClassic['name'][$key] = $condition->name;
            $conditionsClassic['option'][$key] = $condition->option;
        }

        $data = [
            'automation' => $automation,
            'step' => $step,
            'condition' => $conditionObject,
            'id' => $id,
            'step_automation_id' => empty($step->id) ? 0 : $step->id,
            'user_name' => $conditionsUser['name'],
            'user_option' => json_encode(preg_replace_callback(ACYM_REGEX_SWITCHES, [$this, 'switches'], $conditionsUser['option'])),
            'classic_name' => $conditionsClassic['name'],
            'classic_option' => json_encode(preg_replace_callback(ACYM_REGEX_SWITCHES, [$this, 'switches'], $conditionsClassic['option'])),
            'type_trigger' => empty($step->triggers) ? 'classic' : json_decode($step->triggers, true)['type_trigger'],
            'type_condition' => $typeCondition,
            'workflowHelper' => $workflowHelper,
        ];

        parent::display($data);
        //__END__enterprise_
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
