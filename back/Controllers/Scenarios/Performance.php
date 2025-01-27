<?php

namespace AcyMailing\Controllers\Scenarios;

use AcyMailing\Classes\ScenarioClass;
use AcyMailing\Classes\ScenarioHistoryLineClass;
use AcyMailing\Classes\ScenarioProcessClass;
use AcyMailing\Classes\ScenarioQueueClass;
use AcyMailing\Classes\ScenarioStepClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ScenarioHelper;
use AcyMailing\Helpers\WorkflowHelper;

trait Performance
{
    public function performances()
    {
        acym_setVar('layout', 'performances');
        acym_setVar('step', 'performances');

        $data = [];

        $data['isPerformance'] = true;
        $data['workflowHelper'] = new WorkflowHelper();
        $data['scenarioId'] = acym_getVar('int', 'scenarioId', 0);

        $this->prepareScenarioPerformance($data);

        if (empty($data['scenario'])) {
            $this->listing();

            return;
        }

        $this->prepareStepsPerformance($data);

        $this->breadcrumb[$data['scenario']->name] = acym_completeLink('scenarios&task=edit&step=editScenario&scenarioId='.$data['scenario']->id);

        parent::display($data);
    }

    private function prepareScenarioPerformance(&$data)
    {
        if (empty($data['scenarioId'])) {
            return;
        }

        $scenarioClass = new ScenarioClass();
        $data['scenario'] = $scenarioClass->getOneById($data['scenarioId']);
    }

    private function prepareStepsPerformance(&$data)
    {
        $scenarioStepClass = new ScenarioStepClass();
        $scenarioProcessClass = new ScenarioProcessClass();

        $steps = $scenarioStepClass->getAllByScenarioId($data['scenario']->id);
        $numberOfTrigger = $scenarioProcessClass->getNumberOfTriggerByScenarioId($data['scenario']->id);
        $numberOfProcessByStep = $scenarioStepClass->getNumberOfProcessByStepByScenarioId($data['scenario']->id);


        if (empty($steps)) {
            return;
        }

        $triggerNames = $this->getTriggerNames();
        $conditionNames = $this->getConditionNames();
        $actionsNames = $this->getActionNames();

        $triggerName = ucfirst($triggerNames[$data['scenario']->trigger]);
        $chartNodes = [
            $data['scenario']->id => [
                'id' => $data['scenario']->id,
                'name' => 'Trigger '.$triggerNames[$data['scenario']->trigger],
                'value' => $this->createTooltip($triggerName),
                'scenarioId' => $data['scenario']->id,
                'type' => 'trigger',
                'stepId' => '',
            ],
        ];
        $chartData = [];

        $scenarioHelper = new ScenarioHelper();

        foreach ($steps as $step) {
            switch ($step->type) {
                case  ScenarioClass::TYPE_CONDITION:
                    $name = ucfirst($conditionNames[$scenarioHelper->getConditionValue($step)]);
                    break;
                case ScenarioClass::TYPE_ACTION:
                    $name = ucfirst($actionsNames[$scenarioHelper->getActionValue($step)]);
                    break;
                case ScenarioClass::TYPE_DELAY:
                    $name = acym_translation('ACYM_DELAY');
                    break;
                default:
                    $name = '';
            }

            $chartNodes[$step->id] = [
                'id' => $step->id,
                'name' => $name,
                'value' => $this->createTooltip($name),
                'scenarioId' => $step->scenario_id,
                'type' => $step->type,
                'stepId' => $step->id,
            ];

            if (empty($step->previous_id)) {
                $from = $data['scenario']->id;
                $value = intval($numberOfTrigger);
            } else {
                $from = $step->previous_id;
                $value = empty($numberOfProcessByStep[$step->id]) ? 0 : intval($numberOfProcessByStep[$step->id]->count);
            }

            $chartData[] = [
                'source' => $from,
                'target' => $step->id,
                'value' => $value,
            ];
        }

        $data['numberOfTrigger'] = $numberOfTrigger;
        $data['chartNodes'] = $chartNodes;
        $data['chartData'] = $chartData;
    }

    private function getTriggerNames(): array
    {
        $defaultValues = [];
        $triggers = ['classic' => [], 'user' => []];
        acym_trigger('onAcymDeclareTriggers', [&$triggers, &$defaultValues]);

        $triggerNames = [];
        foreach ($triggers['user'] as $key => $trigger) {
            $triggerNames[$key] = strtolower(strip_tags($trigger->name));
        }

        return $triggerNames;
    }


    private function getConditionNames(): array
    {
        $conditions = ['user' => [], 'classic' => []];
        acym_trigger('onAcymDeclareConditions', [&$conditions]);

        $conditionNames = [];
        foreach ($conditions['user'] as $key => $condition) {
            $conditionNames[$key] = strtolower(strip_tags($condition->name));
        }

        return $conditionNames;
    }

    private function getActionNames(): array
    {
        $actions = [];
        acym_trigger('onAcymDeclareActionsScenario', [&$actions]);

        $actionNames = [];
        foreach ($actions as $key => $action) {
            $actionNames[$key] = strtolower(strip_tags($action->name));
        }

        return $actionNames;
    }

    private function createTooltip(string $name): string
    {
        ob_start();
        include acym_getPartial('scenarios', 'performances_tooltip');

        return ob_get_clean();
    }

    public function getStepStats(): void
    {
        $scenarioId = acym_getVar('int', 'scenarioId', 0);
        $stepId = acym_getVar('string', 'stepId', '');
        $type = acym_getVar('string', 'type', '');

        if (empty($scenarioId) || empty($type) || ($type !== 'trigger' && empty($stepId))) {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR_OCCURRED'), [], false);
        }

        $scenarioClass = new ScenarioClass();
        $scenario = $scenarioClass->getOneById($scenarioId);

        if (empty($scenario)) {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR_OCCURRED'), [], false);
        }

        switch ($type) {
            case 'trigger':
                $html = $this->getTriggerHtml($scenarioId);
                break;
            case ScenarioClass::TYPE_CONDITION:
            case ScenarioClass::TYPE_ACTION:
            case ScenarioClass::TYPE_DELAY:
                $html = $this->getStepHtml($stepId, $type);
                break;
            default:
                $html = [];
        }

        if (empty($html)) {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR_OCCURRED'), [], false);
        }

        acym_sendAjaxResponse('', ['content' => $html]);
    }

    private function getTriggerHtml(int $scenarioId): string
    {
        $page = acym_getVar('int', 'page', 1);
        $search = acym_getVar('string', 'search', '');
        $scenarioProcessClass = new ScenarioProcessClass();

        $pagination = new PaginationHelper();
        $elementsPerPage = $pagination->getListLimit();

        $listingSettings = [
            'scenarioId' => $scenarioId,
            'search' => $search,
            'offset' => ($page - 1) * $elementsPerPage,
            'elementsPerPage' => $elementsPerPage,
        ];

        $matchingElements = $scenarioProcessClass->getMatchingElements($listingSettings);

        $pagination->setStatus($matchingElements['total'], $page, $listingSettings['elementsPerPage']);

        $data = [
            'numberOfTrigger' => $scenarioProcessClass->getNumberOfTriggerByScenarioId($scenarioId),
            'usersProcess' => $matchingElements['elements'],
            'pagination' => $pagination,
            'search' => $search,
            'isEmpty' => empty($matchingElements['totalOverall']),
            'title' => acym_translation('ACYM_NUMBER_OF_TRIGGER'),
        ];

        ob_start();
        include acym_getPartial('scenarios', 'performances_trigger');

        return ob_get_clean();
    }

    private function getStepHtml(string $stepId, string $type): string
    {
        $page = acym_getVar('int', 'page', 1);
        $search = acym_getVar('string', 'search', '');
        $pagination = new PaginationHelper();
        $elementsPerPage = $pagination->getListLimit();

        $listingSettings = [
            'stepId' => $stepId,
            'search' => $search,
            'offset' => ($page - 1) * $elementsPerPage,
            'elementsPerPage' => $elementsPerPage,
        ];

        $scenarioHistoryLineClass = new ScenarioHistoryLineClass();
        $matchingElements = $scenarioHistoryLineClass->getMatchingElements($listingSettings);

        $pagination->setStatus($matchingElements['total'], $page, $listingSettings['elementsPerPage']);

        $data = [
            'historyLines' => $matchingElements['elements'],
            'pagination' => $pagination,
            'search' => $search,
            'isEmpty' => empty($matchingElements['totalOverall']),
            'numberOfTrigger' => $matchingElements['totalOverall'],
            'title' => acym_translation('ACYM_TOTAL_USERS_PROCESSED'),
        ];

        ob_start();
        include acym_getPartial('scenarios', 'performances_step');

        return ob_get_clean();
    }

    public function getUserInfo()
    {
        $userId = acym_getVar('int', 'userId', 0);
        $processId = acym_getVar('int', 'processId', 0);

        if (empty($userId) || empty($processId)) {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR_OCCURRED'), [], false);
        }

        $userClass = new UserClass();
        $user = $userClass->getOneById($userId);

        if (empty($user)) {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR_OCCURRED'), [], false);
        }

        $scenarioProcessClass = new ScenarioProcessClass();
        $process = $scenarioProcessClass->getOneById($processId);

        if (empty($process)) {
            acym_sendAjaxResponse(acym_translation('ACYM_ERROR_OCCURRED'), [], false);
        }

        $scenarioHistoryLineClass = new ScenarioHistoryLineClass();
        $scenarioHistoryLines = $scenarioHistoryLineClass->getAllByProcessId($processId);

        $scenarioQueueClass = new ScenarioQueueClass();
        $upcomingStep = $scenarioQueueClass->getByProcessId($processId);

        $data = [
            'user' => $user,
            'process' => $process,
            'historyLines' => $scenarioHistoryLines,
            'upcomingStep' => $upcomingStep,
        ];

        ob_start();
        include acym_getPartial('scenarios', 'performances_user');

        acym_sendAjaxResponse('', ['content' => ob_get_clean()]);
    }
}
