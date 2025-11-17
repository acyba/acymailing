<?php

namespace AcyMailing\Classes;

use AcyMailing\Core\AcymClass;

class ScenarioStepClass extends AcymClass
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'scenario_step';
        $this->pkey = 'id';
    }

    public function save(object $element): ?int
    {
        if (!empty($element->params) && !is_string($element->params)) {
            $element->params = json_encode($element->params);
        }

        // The id is a string
        $scenarioStep = acym_loadObject('SELECT * FROM #__acym_scenario_step WHERE `id` = '.acym_escapeDB($element->id));

        $previousForce = $this->forceInsert;
        $this->forceInsert = empty($scenarioStep);
        $id = parent::save($element);
        $this->forceInsert = $previousForce;

        return (int)$id;
    }

    public function getAllByScenarioId(int $scenarioId): array
    {
        $steps = array_map([$this, 'formatStep'], acym_loadObjectList('SELECT * FROM #__acym_scenario_step  WHERE scenario_id = '.$scenarioId, 'id'));

        usort($steps, function ($a, $b) {
            if ($a->previous_id === $b->id) {
                return 1;
            }

            if ($b->previous_id === $a->id) {
                return -1;
            }

            return 0;
        });


        return $steps;
    }

    public function getFirstStepByScenarioId(int $scenarioId): ?object
    {
        $step = acym_loadObject('SELECT * FROM #__acym_scenario_step WHERE previous_id IS NULL AND  scenario_id = '.$scenarioId);
        if (empty($step)) {
            return null;
        }

        return $this->formatStep($step);
    }

    public function getStepByPreviousStepId(string $parentId): ?object
    {
        $step = acym_loadObject('SELECT * FROM #__acym_scenario_step WHERE previous_id = '.acym_escapeDB($parentId));
        if (empty($step)) {
            return null;
        }

        return $this->formatStep($step);
    }

    public function getAllStepsToDelete(int $scenarioId, array $stepIdsToKeep): array
    {
        $stepIdsToKeepEscaped = [];
        foreach ($stepIdsToKeep as $stepIdToKeep) {
            $stepIdsToKeepEscaped[] = acym_escapeDB($stepIdToKeep);
        }

        $query = 'SELECT id FROM #__acym_scenario_step WHERE scenario_id = '.$scenarioId;

        if (!empty($stepIdsToKeepEscaped)) {
            $query .= ' AND id NOT IN ('.implode(',', $stepIdsToKeepEscaped).')';
        }

        return acym_loadResultArray($query);
    }

    public function getStepByPreviousConditionId(string $parentId, bool $conditionValid): ?object
    {
        $conditionValidValue = $conditionValid ? 1 : 0;

        $step = acym_loadObject('SELECT * FROM #__acym_scenario_step WHERE condition_valid = '.$conditionValidValue.' AND previous_id = '.acym_escapeDB($parentId));
        if (empty($step)) {
            return null;
        }

        return $this->formatStep($step);
    }

    public function getAvailableStepsByDate(string $date): array
    {
        $query = 'SELECT scenario_step.*, scenario_process.user_id, scenario_process.id AS scenario_process_id 
                  FROM #__acym_scenario_step AS scenario_step 
                  JOIN #__acym_scenario_queue AS scenario_queue ON scenario_step.id = scenario_queue.step_id AND scenario_queue.execution_date <= '.acym_escapeDB($date).'
                  JOIN #__acym_scenario_process AS scenario_process ON scenario_queue.scenario_process_id = scenario_process.id';

        return array_map([$this, 'formatStep'], acym_loadObjectList($query));
    }

    public function getNumberOfProcessByStepByScenarioId(int $scenarioId): array
    {
        $query = 'SELECT scenario_history.scenario_step_id , COUNT(scenario_history.id) AS count
                  FROM #__acym_scenario_step AS scenario_step 
                  JOIN #__acym_scenario_history_line AS scenario_history ON scenario_step.id = scenario_history.scenario_step_id AND scenario_history.scenario_step_id IS NOT NULL
                  WHERE scenario_step.scenario_id = '.$scenarioId.'
                  GROUP BY scenario_step_id';

        return acym_loadObjectList($query, 'scenario_step_id');
    }

    public function getAllStepIds(): array
    {
        return acym_loadResultArray('SELECT id FROM #__acym_scenario_step');
    }

    public function generateNewStepId(array $allStepIds = []): string
    {
        if (empty($allStepIds)) {
            $allStepIds = $this->getAllStepIds();
        }

        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
        $randomString = '';
        $length = 24;

        for ($i = 0; $i < $length; $i++) {
            $randomIndex = rand(0, strlen($letters) - 1);
            $randomString .= $letters[$randomIndex];
        }

        if (in_array($randomString, $allStepIds)) {
            return $this->generateNewStepId($allStepIds);
        }

        return $randomString;
    }

    private function formatStep(object $step): object
    {
        $step->params = json_decode($step->params, true);

        return $step;
    }
}
