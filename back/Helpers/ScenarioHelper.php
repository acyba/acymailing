<?php

namespace AcyMailing\Helpers;

use AcyMailing\Classes\ScenarioClass;
use AcyMailing\Classes\ScenarioHistoryLineClass;
use AcyMailing\Classes\ScenarioProcessClass;
use AcyMailing\Classes\ScenarioStepClass;
use AcyMailing\Classes\ScenarioQueueClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Core\AcymObject;

class ScenarioHelper extends AcymObject
{
    private ScenarioClass $scenarioClass;
    private ScenarioStepClass $scenarioStepClass;
    private ScenarioQueueClass $scenarioQueueClass;
    private ScenarioHistoryLineClass $scenarioHistoryLineClass;
    private ScenarioProcessClass $scenarioProcessClass;

    public function __construct()
    {
        parent::__construct();
        $this->scenarioClass = new ScenarioClass();
        $this->scenarioStepClass = new ScenarioStepClass();
        $this->scenarioQueueClass = new ScenarioQueueClass();
        $this->scenarioHistoryLineClass = new ScenarioHistoryLineClass();
        $this->scenarioProcessClass = new ScenarioProcessClass();
    }

    public function trigger(string $trigger, array $options): void
    {
        if (!acym_level(ACYM_ENTERPRISE) || empty($trigger)) {
            acym_logError('ScenarioHelper::trigger - Missing trigger or not allowed, trigger: '.$trigger);

            return;
        }

        if (empty($options['userId'])) {
            acym_logError('ScenarioHelper::trigger - Missing userId, trigger: '.$trigger);

            return;
        }

        $userId = intval($options['userId']);

        $userClass = new UserClass();
        if (empty($userClass->getOneById($userId))) {
            acym_logError('ScenarioHelper::trigger - User not found, userId: '.$userId);

            return;
        }

        $scenarios = $this->scenarioClass->getAllActiveByTriggers([$trigger]);

        foreach ($scenarios as $scenario) {
            if ($scenario->trigger_once) {
                if (!empty($this->scenarioProcessClass->getOneByScenarioIdUserId($scenario->id, $userId))) {
                    continue;
                }
            }

            // For the trigger TimeAutomationTriggers, this way we know if we are triggering a scenario or not
            $scenario->is_scenario = true;
            $scenario->triggers = [$scenario->trigger => 1];
            $execute = false;
            $options = $this->formatOptions($scenario->trigger_params, 'trigger');
            $options['userId'] = $userId;
            $options['time'] = time();
            acym_trigger('onAcymExecuteTrigger', [&$scenario, &$execute, &$options]);

            if (!$execute) {
                continue;
            }

            $firstStep = $this->scenarioStepClass->getFirstStepByScenarioId($scenario->id);

            if (empty($firstStep)) {
                acym_logError('ScenarioHelper::trigger - No first step found for scenario '.$scenario->id);
                continue;
            }

            $scenarioProcess = new \stdClass();
            $scenarioProcess->scenario_id = $scenario->id;
            $scenarioProcess->user_id = $userId;
            $scenarioProcess->start_at = acym_date('now', 'Y-m-d H:i:s', false);
            $scenarioProcess->id = $this->scenarioProcessClass->save($scenarioProcess);

            $this->createHistory($scenarioProcess->id, 'trigger', 'success');

            $this->handleStep($firstStep, $userId, $scenarioProcess->id);
        }
    }

    private function handleStep(object $step, int $userId, int $scenarioProcessId): void
    {
        switch ($step->type) {
            case ScenarioClass::TYPE_DELAY:
                $this->handleDelay($step, $userId, $scenarioProcessId);
                break;
            case ScenarioClass::TYPE_CONDITION:
                $this->handleCondition($step, $userId, $scenarioProcessId);
                break;
            case ScenarioClass::TYPE_ACTION:
                $this->handleAction($step, $userId, $scenarioProcessId);
                break;
        }
    }

    private function handleDelay(object $step, int $userId, int $scenarioProcessId): void
    {
        $nextStep = $this->scenarioStepClass->getStepByPreviousStepId($step->id);

        if (empty($nextStep)) {
            $this->ending($scenarioProcessId);

            return;
        }

        $scenarioQueue = new \stdClass();
        $scenarioQueue->scenario_process_id = $scenarioProcessId;
        $scenarioQueue->step_id = $nextStep->id;
        $timestampNextAction = time() + $this->calculateDelaySecondsToAdd($step->params);
        $scenarioQueue->execution_date = acym_date($timestampNextAction, 'Y-m-d H:i:s', false);

        $this->scenarioQueueClass->save($scenarioQueue);
        $this->scenarioQueueClass->deleteByStepIds([$step->id]);

        $this->createHistory($scenarioProcessId, ScenarioClass::TYPE_DELAY, 'success', $step->id, $step->params);
    }

    private function calculateDelaySecondsToAdd(array $stepDelayParams): int
    {
        if (empty($stepDelayParams['delay']) || empty($stepDelayParams['unit'])) {
            return 0;
        }

        return $stepDelayParams['delay'] * $stepDelayParams['unit'];
    }

    private function handleCondition(object $step, int $userId, int $scenarioProcessId): void
    {
        $conditionNotValidCount = 0;

        $query = new AutomationHelper();
        $query->where = ['user.id = '.$userId];

        $conditionOptions = $this->formatOptions($step->params['option'], $step->type);
        // Unused variable
        $number = 0;
        acym_trigger('onAcymProcessCondition_'.$step->params['condition'], [&$query, &$conditionOptions, &$number, &$conditionNotValidCount]);
        $this->scenarioQueueClass->deleteByStepIds([$step->id]);

        $conditionValid = $conditionNotValidCount === 0;

        $historyOptions = array_merge($conditionOptions, ['scenario_condition' => $step->params['condition']]);
        $this->createHistory($scenarioProcessId, ScenarioClass::TYPE_CONDITION, 'Condition is '.($conditionValid ? 'valid' : 'not valid'), $step->id, $historyOptions);

        $nextStep = $this->scenarioStepClass->getStepByPreviousConditionId($step->id, $conditionValid);

        if (empty($nextStep)) {
            $this->ending($scenarioProcessId);

            return;
        }

        $this->handleStep($nextStep, $userId, $scenarioProcessId);
    }

    private function formatOptions(array $options, string $type): array
    {
        $formattedOptions = [];

        switch ($type) {
            case ScenarioClass::TYPE_CONDITION:
                $regex = '/acym_condition\[conditions\]\[__numor__\]\[__numand__\]\[(.+)\]\[(.+)\]/';
                break;
            case ScenarioClass::TYPE_ACTION:
                $regex = '/acym_action\[actions\]\[__and__\]\[(.+)\]\[(.+)\]/';
                break;
            case 'trigger':
                $regex = '/\[triggers\]\[user\]\[(.+)\]\[(.+)\]/';
                break;
            default:
                $regex = '';
        }

        if (empty($regex)) {
            return $formattedOptions;
        }

        foreach ($options as $key => $option) {
            $formattedKey = preg_replace($regex, '$2', $key);
            if ($formattedKey === $key) {
                continue;
            }
            $formattedOptions[$formattedKey] = $option;
        }

        return $formattedOptions;
    }

    private function handleAction(object $step, int $userId, int $scenarioProcessId): void
    {
        $query = new AutomationHelper();
        $query->where = ['user.id = '.$userId];

        $actionOptions = $this->formatOptions($step->params['option'], $step->type);
        $log = '';
        $report = '';
        try {
            $report = acym_trigger('onAcymProcessAction_'.$step->params['action'], [&$query, &$actionOptions]);
            $report = is_array($report) ? implode(', ', $report) : $report;
        } catch (\Exception $e) {
            $log = $e->getMessage();
        }

        $this->scenarioQueueClass->deleteByStepIds([$step->id]);

        $historyOptions = array_merge($actionOptions, ['scenario_action' => $step->params['action']]);
        $this->createHistory($scenarioProcessId, ScenarioClass::TYPE_CONDITION, 'Action completed: '.$report, $step->id, $historyOptions, $log);

        $nextStep = $this->scenarioStepClass->getStepByPreviousStepId($step->id);

        if (empty($nextStep)) {
            $this->ending($scenarioProcessId);

            return;
        }

        $this->handleStep($nextStep, $userId, $scenarioProcessId);
    }

    private function ending(int $scenarioProcessId): void
    {
        $this->createHistory($scenarioProcessId, 'ending', acym_translation('ACYM_SCENARIO_ENDED'));

        $this->scenarioProcessClass->endProcess($scenarioProcessId);
    }

    public function executeAvailableSteps(): void
    {
        $dateNowUtc = acym_date('now', 'Y-m-d H:i:s', false);

        $steps = $this->scenarioStepClass->getAvailableStepsByDate($dateNowUtc);

        if (empty($steps)) {
            return;
        }

        foreach ($steps as $step) {
            $this->handleStep($step, $step->user_id, $step->scenario_process_id);
        }
    }

    public function triggerTimeScenarios(): void
    {
        $userStatusTriggers = [];
        acym_trigger('onAcymDefineUserStatusCheckTriggers', [&$userStatusTriggers]);

        if (empty($userStatusTriggers)) {
            return;
        }

        $scenarioClass = new ScenarioClass();
        $scenarios = $scenarioClass->getAllActiveByTriggers($userStatusTriggers);

        if (empty($scenarios)) {
            return;
        }

        foreach ($scenarios as $scenario) {
            $nextExecution = $scenario->trigger_params['next_execution'] ?? null;
            $scenario->next_execution = $nextExecution;

            if (!empty($nextExecution) && $nextExecution > time()) {
                continue;
            }

            $firstStep = $this->scenarioStepClass->getFirstStepByScenarioId($scenario->id);
            if (empty($firstStep)) {
                acym_logError('ScenarioHelper::triggerTimeScenarios - No first step found for scenario '.$scenario->id);
                continue;
            }

            $execute = false;
            $options = $this->formatOptions($scenario->trigger_params['option'] ?? [], 'trigger');
            $options['time'] = time();

            $scenario->is_scenario = true;
            $scenario->triggers = [$scenario->trigger => $options];

            // Get the users matching the trigger
            acym_trigger('onAcymExecuteTrigger', [&$scenario, &$execute, &$options]);

            if ($nextExecution !== $scenario->next_execution) {
                $scenario->trigger_params['next_execution'] = $scenario->next_execution;
                $scenarioClass->save($scenario);
            }

            if (empty($options['userIds'])) {
                continue;
            }

            foreach ($options['userIds'] as $userId) {
                if ($scenario->trigger_once && !empty($this->scenarioProcessClass->getOneByScenarioIdUserId($scenario->id, $userId))) {
                    continue;
                }

                $scenarioProcess = new \stdClass();
                $scenarioProcess->scenario_id = $scenario->id;
                $scenarioProcess->user_id = $userId;
                $scenarioProcess->start_at = acym_date('now', 'Y-m-d H:i:s', false);
                $scenarioProcess->id = $this->scenarioProcessClass->save($scenarioProcess);

                $this->createHistory($scenarioProcess->id, 'trigger', 'success');

                $this->handleStep($firstStep, $userId, $scenarioProcess->id);
            }
        }
    }

    private function createHistory(int $scenarioProcessId, string $type, string $result, string $scenarioStepId = '', array $params = [], string $log = ''): void
    {
        $scenarioHistoryLine = new \stdClass();
        $scenarioHistoryLine->scenario_process_id = $scenarioProcessId;
        $scenarioHistoryLine->date = acym_date('now', 'Y-m-d H:i:s', false);
        $scenarioHistoryLine->type = $type;
        //TODO maybe add something more specific
        $scenarioHistoryLine->result = $result;

        if (!empty($scenarioStepId)) {
            $scenarioHistoryLine->scenario_step_id = $scenarioStepId;
        }

        if (!empty($params)) {
            $scenarioHistoryLine->params = $params;
        }

        if (!empty($log)) {
            $scenarioHistoryLine->log = $log;
        }

        $this->scenarioHistoryLineClass->save($scenarioHistoryLine);
    }

    public function getConditionValue(object $step): string
    {
        return $step->params['condition'];
    }

    public function getActionValue(object $step): string
    {
        return $step->params['action'];
    }
}
