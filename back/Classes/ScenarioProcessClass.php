<?php

namespace AcyMailing\Classes;

use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Core\AcymClass;

class ScenarioProcessClass extends AcymClass
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'scenario_process';
        $this->pkey = 'id';
    }

    public function hasUserProcess(int $scenarioId, int $userId): bool
    {
        $process = acym_loadObject('SELECT * FROM #__acym_scenario_process WHERE scenario_id = '.$scenarioId.' AND user_id = '.$userId);

        return !empty($process);
    }

    public function endProcess(int $scenarioProcessId): void
    {
        $scenarioProcess = new \stdClass();
        $scenarioProcess->id = $scenarioProcessId;
        $scenarioProcess->end_at = acym_date('now', 'Y-m-d H:i:s', false);

        $this->save($scenarioProcess);
    }

    public function getNumberOfTriggerByScenarioId(int $scenarioId): int
    {
        $numberOfTrigger = acym_loadResult('SELECT COUNT(*) FROM #__acym_scenario_process WHERE scenario_id = '.$scenarioId);

        if (empty($numberOfTrigger)) {
            return 0;
        }

        return (int)$numberOfTrigger;
    }

    public function getMatchingElements(array $settings = []): array
    {
        $results = [];

        $query = 'SELECT DISTINCT scenario_process.*, user.email FROM #__acym_scenario_process AS `scenario_process`';
        $queryCount = 'SELECT COUNT(DISTINCT scenario_process.id) AS total FROM #__acym_scenario_process AS scenario_process';
        $filters = [];

        $joins = [
            'JOIN #__acym_user AS user ON user.id = scenario_process.user_id',
        ];

        if (!empty($joins)) {
            $query .= ' '.implode(' ', $joins);
            $queryCount .= ' '.implode(' ', $joins);
        }

        if (!empty($settings['scenarioId'])) {
            $filters[] = 'scenario_process.scenario_id = '.intval($settings['scenarioId']);

            $results['totalOverall'] = acym_loadResult('SELECT COUNT(*) FROM #__acym_scenario_process WHERE scenario_id = '.intval($settings['scenarioId']));
        }

        if (!empty($settings['search'])) {
            $searchValue = acym_escapeDB('%'.$settings['search'].'%');
            $filters[] = 'user.name LIKE '.$searchValue.' OR user.email LIKE '.$searchValue;
        }

        if (!empty($filters)) {
            $query .= ' WHERE ('.implode(') AND (', $filters).')';
            $queryCount .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        if (empty($settings['offset']) || $settings['offset'] < 0) {
            $settings['offset'] = 0;
        }

        if (empty($settings['elementsPerPage']) || $settings['elementsPerPage'] < 1) {
            $pagination = new PaginationHelper();
            $settings['elementsPerPage'] = $pagination->getListLimit();
        }

        $results['elements'] = acym_loadObjectList($query, 'id', $settings['offset'], $settings['elementsPerPage']);
        $results['total'] = acym_loadObject($queryCount);

        return $results;
    }
}
