<?php

namespace AcyMailing\Classes;

use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Core\AcymClass;

class ScenarioClass extends AcymClass
{
    const TYPE_DELAY = 'delay';
    const TYPE_CONDITION = 'condition';
    const TYPE_ACTION = 'action';

    private ScenarioStepClass $scenarioStepClass;

    // This is a list of all the step ids we have to save when saving a scenario, this way we can delete all the steps that are not in this list
    private array $stepIdsToKeep = [];

    public function __construct()
    {
        parent::__construct();

        $this->table = 'scenario';
        $this->pkey = 'id';
        $this->scenarioStepClass = new ScenarioStepClass();
    }

    public function getMatchingElements(array $settings = []): array
    {
        $query = 'SELECT DISTINCT scenario.*  FROM #__acym_scenario AS `scenario`';
        $queryCount = 'SELECT COUNT(DISTINCT scenario.id) AS total FROM #__acym_scenario AS scenario';
        $queryStatus = 'SELECT COUNT(DISTINCT scenario.id) AS `all`, SUM(`scenario`.`active`) AS active, SUM(IF(`scenario`.`active` = 0, 1, 0)) AS inactive FROM #__acym_scenario AS `scenario`';
        $filters = [];

        if (!empty($settings['search'])) {
            $searchValue = acym_escapeDB('%'.$settings['search'].'%');
            $filters[] = 'scenario.name LIKE '.$searchValue.' OR scenario.id = '.intval($settings['search']);
        }

        if (!empty($filters)) {
            $queryStatus .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        if (!empty($settings['status'])) {
            switch ($settings['status']) {
                case 'active':
                    $filters[] = 'scenario.active = 1';
                    break;
                case 'inactive':
                    $filters[] = 'scenario.active = 0';
            }
        }

        // We don't filter by status on the status query this way we can select other statuses
        if (!empty($filters)) {
            $query .= ' WHERE ('.implode(') AND (', $filters).')';
            $queryCount .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        if (!empty($settings['ordering']) && !empty($settings['ordering_sort_order'])) {
            $query .= ' ORDER BY scenario.'.acym_secureDBColumn($settings['ordering']).' '.acym_secureDBColumn(strtoupper($settings['ordering_sort_order']));
        }

        if (empty($settings['offset']) || $settings['offset'] < 0) {
            $settings['offset'] = 0;
        }

        if (empty($settings['elementsPerPage']) || $settings['elementsPerPage'] < 1) {
            $pagination = new PaginationHelper();
            $settings['elementsPerPage'] = $pagination->getListLimit();
        }

        return [
            'elements' => acym_loadObjectList($query, 'id', $settings['offset'], $settings['elementsPerPage']),
            'total' => acym_loadObject($queryCount),
            'totalOverall' => acym_loadResult('SELECT COUNT(id) FROM #__acym_scenario'),
            'scenariosNumberStatus' => acym_loadObjectList($queryStatus)[0],
        ];
    }

    public function save(object $element): ?int
    {
        $flow = [];
        if (!empty($element->flow)) {
            $flow = json_decode($element->flow, true);

            // The first step should the trigger
            if (empty($flow[0]['params'])) {
                return 0;
            }

            $element->trigger = $flow[0]['params']['trigger'];

            if (!empty($element->id)) {
                $scenario = $this->getOneById($element->id);
                if (!empty($scenario->trigger_params['next_execution'])) {
                    $flow[0]['params']['next_execution'] = $scenario->trigger_params['next_execution'];
                }
            }

            $element->trigger_params = json_encode($flow[0]['params']);
        } elseif (!empty($element->trigger_params) && is_array($element->trigger_params)) {
            $element->trigger_params = json_encode($element->trigger_params);
        }

        unset($element->flow);

        $element->id = parent::save($element);

        if (!empty($flow) && !empty($element->id)) {
            $this->saveFlow($flow, $element->id);
        }

        return $element->id;
    }

    public function getOneById(int $id): ?object
    {
        $scenario = parent::getOneById($id);

        if (empty($scenario->trigger_params)) {
            return $scenario;
        }

        $scenario->trigger_params = json_decode($scenario->trigger_params, true);

        return $scenario;
    }

    private function saveFlow(array $flow, int $scenarioId): void
    {
        if (!empty($flow[0]['children'][0])) {
            // We save all the step
            $this->saveStep($flow[0]['children'][0], $scenarioId);
        }
        $this->cleanSteps($scenarioId);
    }

    private function saveStep(array $currentStep, int $scenarioId, ?string $previousStepId = null, ?int $conditionValid = null): void
    {
        // If this is a direct child of a condition we pass it, it's a hidden object for the javascript
        if (isset($currentStep['conditionEnd'])) {
            if ($currentStep['conditionEnd'] && !empty($currentStep['children'])) {
                $isConditionValid = empty($currentStep['conditionValid']) ? 0 : 1;
                $this->saveStep($currentStep['children'][0], $scenarioId, $previousStepId, $isConditionValid);
            }

            return;
        }

        switch ($currentStep['params']['type']) {
            case self::TYPE_DELAY:
                $type = self::TYPE_DELAY;
                break;
            case self::TYPE_CONDITION:
                $type = self::TYPE_CONDITION;
                break;
            case self::TYPE_ACTION:
                $type = self::TYPE_ACTION;
                break;
            default:
                acym_enqueueMessage(acym_translation('ACYM_INVALID_STEP_TYPE'), 'error');

                return;
        }

        $step = new \stdClass();
        $step->id = $currentStep['slug'];
        $step->previous_id = $previousStepId;
        $step->type = $type;
        $step->params = $currentStep['params'];
        $step->scenario_id = $scenarioId;
        $step->condition_valid = $conditionValid;

        $this->scenarioStepClass->save($step);

        $this->stepIdsToKeep[] = $step->id;

        if (!empty($currentStep['children'])) {
            foreach ($currentStep['children'] as $child) {
                $this->saveStep($child, $scenarioId, $currentStep['slug']);
            }
        }
    }

    private function cleanSteps(int $scenarioId): void
    {
        $stepIdsToDelete = $this->scenarioStepClass->getAllStepsToDelete($scenarioId, $this->stepIdsToKeep);

        if (empty($stepIdsToDelete)) {
            return;
        }

        $scenarioQueueClass = new ScenarioQueueClass();
        $scenarioQueueClass->deleteByStepIds($stepIdsToDelete);
        $this->scenarioStepClass->delete($stepIdsToDelete);
    }

    private function formatScenario(object $scenario): ?object
    {
        if (empty($scenario)) {
            return null;
        }

        $scenario->trigger_params = json_decode($scenario->trigger_params, true);

        return $scenario;
    }

    public function getAllActiveByTriggers(array $triggerTypes): array
    {
        if (empty($triggerTypes)) {
            return [];
        }

        $triggerTypes = array_map('acym_escapeDB', $triggerTypes);
        $query = 'SELECT * FROM #__acym_scenario WHERE active = 1 AND `trigger` IN ('.implode(', ', $triggerTypes).') ORDER BY `ordering` ASC';

        return array_map([$this, 'formatScenario'], acym_loadObjectList($query));
    }

    public function delete(array $elements): int
    {
        if (empty($elements)) {
            return 0;
        }

        $escapedElements = array_map('acym_escapeDB', $elements);

        $scenarioHistoryLineQuery = 'DELETE FROM #__acym_scenario_history_line WHERE scenario_process_id IN (SELECT id FROM #__acym_scenario_process WHERE scenario_id IN ('.implode(
                ',',
                $escapedElements
            ).'))';
        $scenarioQueueQuery = 'DELETE FROM #__acym_scenario_queue WHERE scenario_process_id IN (SELECT id FROM #__acym_scenario_process WHERE scenario_id IN ('.implode(
                ',',
                $escapedElements
            ).'))';
        $scenarioProcessQuery = 'DELETE FROM #__acym_scenario_process WHERE scenario_id IN ('.implode(',', $escapedElements).')';
        $scenarioStepQuery = 'DELETE FROM #__acym_scenario_step WHERE scenario_id IN ('.implode(',', $escapedElements).')';
        acym_query($scenarioHistoryLineQuery);
        acym_query($scenarioQueueQuery);
        acym_query($scenarioProcessQuery);
        acym_query($scenarioStepQuery);

        return parent::delete($elements);
    }

    public function duplicate(int $scenarioId): void
    {
        $scenarioStepClass = new ScenarioStepClass();

        $scenario = $this->getOneById($scenarioId);

        if (empty($scenario)) {
            return;
        }

        unset($scenario->id);
        $scenario->name = $scenario->name.' - '.acym_translation('ACYM_COPY');

        $scenario->id = $this->save($scenario);

        $scenarioSteps = $scenarioStepClass->getAllByScenarioId($scenarioId);

        if (empty($scenarioSteps)) {
            return;
        }

        $allStepIds = $scenarioStepClass->getAllStepIds();

        $stepCorrespondence = [];

        foreach ($scenarioSteps as $scenarioStep) {
            $stepCorrespondence[$scenarioStep->id] = $scenarioStepClass->generateNewStepId($allStepIds);
            $allStepIds[] = $stepCorrespondence[$scenarioStep->id];
        }

        foreach ($scenarioSteps as $scenarioStep) {
            $scenarioStep->id = $stepCorrespondence[$scenarioStep->id];
            $scenarioStep->previous_id = $stepCorrespondence[$scenarioStep->previous_id] ?? null;
            $scenarioStep->scenario_id = $scenario->id;
            $scenarioStepClass->save($scenarioStep);
        }
    }

    public function markUserUnsubscribeFromScenario(int $userId, int $scenarioId): void
    {
        $query = 'UPDATE #__acym_scenario_process SET unsubscribed = 1 WHERE scenario_id = '.intval($scenarioId).' AND user_id = '.intval($userId);


        acym_query($query);
    }

    public function getScenarioIdByMailId(int $mailId): ?int
    {
        $query = 'SELECT scenario_id 
              FROM #__acym_scenario_step 
              WHERE type = '.acym_escapeDB(self::TYPE_ACTION).' 
              AND params LIKE '.acym_escapeDB('%[mail_id]":"'.$mailId.'"%').' 
              LIMIT 1';

        return acym_loadResult($query);
    }

    public function checkUserMarkedUnsubscribed(int $userId, int $scenarioProcessId): bool
    {
        $queryScenId = 'SELECT scenario_id 
              FROM #__acym_scenario_process 
              WHERE id = '.intval($scenarioProcessId).' 
              AND user_id = '.intval($userId).' 
              LIMIT 1';

        $scenarioId = acym_loadResult($queryScenId);

        $queryUnsubscribed = 'SELECT unsubscribed 
              FROM #__acym_scenario_process 
              WHERE scenario_id = '.intval($scenarioId).' 
              AND user_id = '.intval($userId).' 
              LIMIT 1';

        $result = acym_loadResult($queryUnsubscribed);

        return !empty($result);
    }
}
