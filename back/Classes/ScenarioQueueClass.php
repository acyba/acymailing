<?php

namespace AcyMailing\Classes;

use AcyMailing\Core\AcymClass;

class ScenarioQueueClass extends AcymClass
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'scenario_queue';
        $this->pkey = 'id';
    }

    public function deleteByStepIds(array $stepIds): ?int
    {
        foreach ($stepIds as $key => $stepId) {
            $stepIds[$key] = acym_escapeDB($stepId);
        }

        return acym_query('DELETE FROM #__acym_'.acym_secureDBColumn($this->table).' WHERE `step_id` IN ('.implode(',', $stepIds).')');
    }

    public function getByProcessId(int $processId): ?\stdClass
    {
        $query = 'SELECT step.type, step.params, queue.execution_date FROM #__acym_scenario_queue AS `queue`
                    JOIN #__acym_scenario_step AS `step` ON step.id = queue.step_id
                    WHERE queue.scenario_process_id = '.$processId;

        $nextStep = acym_loadObject($query);

        if (empty($nextStep)) {
            return null;
        }

        $nextStep->params = json_decode($nextStep->params, true);

        return $nextStep;
    }
}
