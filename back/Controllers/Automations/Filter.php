<?php

namespace AcyMailing\Controllers\Automations;

use AcyMailing\Classes\ActionClass;
use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\ConditionClass;
use AcyMailing\Classes\StepClass;
use AcyMailing\Helpers\AutomationHelper;
use AcyMailing\Helpers\WorkflowHelper;

trait Filter
{
    public function filter(): void
    {
        //__START__enterprise_
        acym_session();
        acym_setVar('layout', 'filter');
        $id = acym_getVar('int', 'id');
        $stepId = acym_getVar('int', 'stepId');
        $automationClass = new AutomationClass();
        $stepClass = new StepClass();
        $actionClass = new ActionClass();
        $conditionClass = new ConditionClass();
        $workflowHelper = new WorkflowHelper();

        $action = new \stdClass();
        $step = new \stdClass();
        $condition = new \stdClass();

        if (!empty($id)) {
            $automation = $automationClass->getOneById($id);
            $this->breadcrumb[acym_translation($automation->name)] = acym_completeLink('automation&task=edit&step=filter&id='.$automation->id);

            $steps = $stepClass->getStepsByAutomationId($id);
            if (!empty($steps)) {
                $step = $steps[0];
                $conditions = $conditionClass->getConditionsByStepId($step->id);
                if (empty($conditions)) {
                    acym_setVar('stepId', $stepId);
                    acym_setVar('id', $id);
                    acym_enqueueMessage(acym_translation('ACYM_PLEASE_SET_CONDITION_OR_SAVE'), 'warning');

                    $this->condition();

                    return;
                }

                $condition = $conditions[0];
                $actions = $actionClass->getActionsByConditionId($condition->id);
                if (!empty($actions)) $action = $actions[0];
            }
        } else {
            $automation = new \stdClass();
            $this->breadcrumb[acym_translation('ACYM_NEW_MASS_ACTION')] = acym_completeLink('automation&task=edit&step=filter');

            if (!empty($_SESSION['massAction']['filters'])) {
                $action->filters = json_encode($_SESSION['massAction']['filters']);
            }
        }

        if (empty($action->filters)) $action->filters = '[]';

        $currentFilters = empty($action->filters) ? [] : json_decode($action->filters, true);
        $currentTriggers = empty($step->triggers) ? [] : json_decode($step->triggers, true);
        if (empty($currentFilters)) {
            if (empty($currentTriggers) || $currentTriggers['type_trigger'] != 'user') {
                $typeFilter = 'classic';
            } else {
                $typeFilter = 'user';
            }
        } else {
            $typeFilter = $currentFilters['type_filter'];
        }

        $filters = [];
        acym_trigger('onAcymDeclareFilters', [&$filters]);

        uasort(
            $filters,
            function ($a, $b) {
                return strcmp(strtolower($a->name), strtolower($b->name));
            }
        );

        $selectFilter = new \stdClass();
        $selectFilter->name = acym_translation('ACYM_SELECT_FILTER');
        $selectFilter->option = '';
        array_unshift($filters, $selectFilter);

        $filtersClassic = ['name' => [], 'option'];

        foreach ($filters as $key => $filter) {
            $filtersClassic['name'][$key] = $filter->name;
            $filtersClassic['option'][$key] = $filter->option;
        }

        $data = [
            'automation' => $automation,
            'step' => $step,
            'action' => $action,
            'id' => $id,
            'condition' => $condition,
            'step_automation_id' => empty($step->id) ? 0 : $step->id,
            'classic_name' => $filtersClassic['name'],
            'classic_option' => json_encode(preg_replace_callback(ACYM_REGEX_SWITCHES, [$this, 'switches'], $filtersClassic['option'])),
            'type_trigger' => empty($step->triggers) ? 'classic' : json_decode($step->triggers, true)['type_trigger'],
            'type_filter' => $typeFilter,
            'workflowHelper' => $workflowHelper,
        ];

        parent::display($data);
        //__END__enterprise_

        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }
    }

    private function setSaveFilters(bool $isMassAction = false): array
    {
        $automationID = acym_getVar('int', 'id');
        $actionId = acym_getVar('int', 'actionId');
        $action = acym_getVar('array', 'acym_action', []);
        $actionClass = new ActionClass();
        $conditionId = acym_getVar('int', 'conditionId');

        $stepAutomationId = acym_getVar('int', 'stepAutomationId');

        if (!empty($stepAutomationId)) {
            $stepAutomation['id'] = $stepAutomationId;
        }

        if (!empty($conditionId)) {
            $action['condition_id'] = $conditionId;
        }

        if (!empty($actionId)) {
            $action['id'] = $actionId;
        }

        $action['filters']['type_filter'] = acym_getVar('string', 'type_filter');

        if ($isMassAction) {
            acym_session();
            $_SESSION['massAction']['filters'] = $action['filters'];

            return [];
        }

        $action['filters'] = json_encode($action['filters']);

        $action['order'] = 1;

        foreach ($action as $column => $value) {
            acym_secureDBColumn($column);
        }

        //We need an object to save it so we make a object
        $action = (object)$action;

        $action->id = $actionClass->save($action);

        return [
            'automationId' => $automationID,
            'stepId' => $stepAutomationId,
            'actionId' => $action->id,
        ];
    }

    public function saveExitFilters(): void
    {
        $this->setSaveFilters();

        acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');

        $this->listing();
    }

    public function saveFilters(): void
    {
        $ids = $this->setSaveFilters();

        acym_setVar('id', $ids['automationId']);
        acym_setVar('stepId', $ids['stepId']);
        acym_setVar('actionId', $ids['actionId']);
        $this->summary();
    }

    public function countresults(): void
    {
        $or = acym_getVar('int', 'or');
        $and = acym_getVar('int', 'and');
        $stepAutomation = acym_getVar('array', 'acym_action');

        if (empty($stepAutomation['filters'][$or][$and])) {
            acym_sendAjaxResponse(acym_translation('ACYM_AUTOMATION_NOT_FOUND'), [], false);
        }

        $query = new AutomationHelper();

        $filterName = key($stepAutomation['filters'][$or][$and]);
        $options = current($stepAutomation['filters'][$or][$and]);
        $messages = acym_trigger('onAcymProcessFilterCount_'.$filterName, [&$query, &$options, &$and]);

        acym_sendAjaxResponse(implode(' | ', $messages));
    }

    public function countResultsOrTotal(): void
    {
        $or = acym_getVar('int', 'or');
        $stepAutomation = acym_getVar('array', 'acym_action');

        $query = new AutomationHelper();

        if (!empty($stepAutomation) && !empty($stepAutomation['filters'][$or])) {
            foreach ($stepAutomation['filters'][$or] as $and => $andValues) {
                $and = intval($and);
                foreach ($andValues as $filterName => $options) {
                    $options['countTotal'] = true;
                    acym_trigger('onAcymProcessFilter_'.$filterName, [&$query, &$options, &$and]);
                }
            }
        }

        $result = $query->count();

        acym_sendAjaxResponse(acym_translationSprintf('ACYM_SELECTED_USERS_TOTAL', $result));
    }
}
