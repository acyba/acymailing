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
        //__START__enterprise_
        acym_session();
        acym_setVar('layout', 'summary');
        $automationClass = new AutomationClass();
        $stepClass = new StepClass();
        $conditionClass = new ConditionClass();
        $actionClass = new ActionClass();
        $id = acym_getVar('int', 'id');
        $massAction = empty($_SESSION['massAction']) ? '' : $_SESSION['massAction'];
        $workflowHelper = new WorkflowHelper();

        $automation = new \stdClass();
        $step = new \stdClass();
        $action = new \stdClass();
        $condition = new \stdClass();

        if (!empty($id)) {
            $automation = $automationClass->getOneById($id);
            $this->breadcrumb[acym_translation($automation->name)] = acym_completeLink('automation&task=edit&step=summary&id='.$automation->id);
            $steps = $stepClass->getStepsByAutomationId($id);

            if (!empty($steps)) {
                $step = $steps[0];
                //triggers
                if (!empty($step->triggers)) $step->triggers = json_decode($step->triggers, true);
                acym_trigger('onAcymDeclareSummary_triggers', [&$step]);

                //conditions
                $conditions = $conditionClass->getConditionsByStepId($step->id);
                if (!empty($conditions)) {
                    $condition = $conditions[0];
                    $condition->conditions = json_decode($condition->conditions, true);
                    //get actions
                    $actions = $actionClass->getActionsByConditionId($condition->id);
                    if (!empty($actions)) $action = $actions[0];
                    foreach ($condition->conditions as $or => $orValues) {
                        if ($or === 'type_condition') continue;
                        foreach ($orValues as $and => $andValues) {
                            acym_trigger('onAcymDeclareSummary_conditions', [&$condition->conditions[$or][$and]]);
                        }
                    }
                }

                //filters
                if (!empty($action->filters)) $action->filters = json_decode($action->filters, true);

                //actions
                if (!empty($action->actions)) $action->actions = json_decode($action->actions, true);
            }
        } elseif (!empty($massAction)) {
            $action->filters = !empty($massAction['filters']) ? $massAction['filters'] : '';
            $action->actions = !empty($massAction['actions']) ? $massAction['actions'] : '';
            $this->breadcrumb[acym_translation('ACYM_NEW_MASS_ACTION')] = acym_completeLink('automation&task=edit&step=summary');
        }


        if (!empty($action->filters)) {
            foreach ($action->filters as $or => $orValues) {
                if ($or === 'type_filter') continue;
                foreach ($orValues as $and => $andValues) {
                    acym_trigger('onAcymDeclareSummary_filters', [&$action->filters[$or][$and]]);
                }
            }
        }
        if (!empty($action->actions)) {
            foreach ($action->actions as $and => $andValue) {
                acym_trigger('onAcymDeclareSummary_actions', [&$action->actions[$and]]);
            }
        }

        $data = [
            'id' => $id,
            'automation' => $automation,
            'step' => $step,
            'action' => $action,
            'condition' => $condition,
            'workflowHelper' => $workflowHelper,
        ];

        parent::display($data);
        //__END__enterprise_

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
