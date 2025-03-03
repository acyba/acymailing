<?php

namespace AcyMailing\Classes;

use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Core\AcymClass;

class ScenarioHistoryLineClass extends AcymClass
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'scenario_history_line';
        $this->pkey = 'id';
    }

    public function save($element)
    {
        if (isset($element->params) && is_array($element->params)) {
            $element->params = json_encode($element->params);
        }

        return parent::save($element);
    }

    private function formatHistoryLine($historyLine)
    {
        if (!empty($historyLine->params)) {
            $historyLine->params = json_decode($historyLine->params);
        }

        return $historyLine;
    }

    public function getAllByProcessId(int $processId): array
    {
        return array_map([$this, 'formatHistoryLine'], acym_loadObjectList('SELECT * FROM #__acym_scenario_history_line WHERE scenario_process_id = '.$processId, 'id'));
    }

    public function getMatchingElements(array $settings = []): array
    {
        $results = [];

        $query = 'SELECT history_line.*, `user`.email, `user`.id AS user_id FROM #__acym_scenario_history_line AS history_line';
        $queryCount = 'SELECT COUNT(history_line.id) AS total FROM #__acym_scenario_history_line AS history_line';
        $filters = [];

        $joins = [
            'JOIN #__acym_scenario_process AS scenario_process ON history_line.scenario_process_id = scenario_process.id',
            'JOIN #__acym_user AS `user` ON scenario_process.user_id = `user`.id',
        ];

        if (!empty($joins)) {
            $query .= ' '.implode(' ', $joins);
            $queryCount .= ' '.implode(' ', $joins);
        }

        if (!empty($settings['stepId'])) {
            $filters[] = 'history_line.scenario_step_id = '.acym_escapeDB($settings['stepId']);

            $results['totalOverall'] = acym_loadResult('SELECT COUNT(*) FROM #__acym_scenario_history_line WHERE scenario_step_id = '.acym_escapeDB($settings['stepId']));
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
