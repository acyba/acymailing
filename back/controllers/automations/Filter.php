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
    public function filter()
    {

        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }
    }

    private function _saveFilters($isMassAction = false)
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

            return true;
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

    public function saveExitFilters()
    {
        $ids = $this->_saveFilters();

        if (empty($ids)) {
            return;
        }

        acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');

        $this->listing();
    }

    public function saveFilters()
    {
        $ids = $this->_saveFilters();

        if (empty($ids)) {
            return;
        }

        acym_setVar('id', $ids['automationId']);
        acym_setVar('stepId', $ids['stepId']);
        acym_setVar('actionId', $ids['actionId']);
        $this->summary();
    }

    public function countresults()
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

    public function countResultsOrTotal()
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
