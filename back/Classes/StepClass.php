<?php

namespace AcyMailing\Classes;

use AcyMailing\Core\AcymClass;

class StepClass extends AcymClass
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'step';
        $this->pkey = 'id';
    }

    public function save(object $step): ?int
    {
        foreach ($step as $oneAttribute => $value) {
            if (empty($value)) continue;

            $step->$oneAttribute = is_array($value) ? json_encode($value) : strip_tags($value);
        }

        //Some of the database need a name for the column name, they add an index and the name need to be unique
        if (empty($step->name)) {
            $step->name = 'step_'.time();
        }

        return parent::save($step);
    }

    public function getOneStepByAutomationId(int $automationId): ?object
    {
        $step = acym_loadObject('SELECT * FROM #__acym_step WHERE automation_id = '.intval($automationId));

        return empty($step) ? null : $step;
    }

    public function getStepsByAutomationId(int $automationId): array
    {
        return acym_loadObjectList('SELECT * FROM #__acym_step WHERE automation_id = '.intval($automationId));
    }

    public function getActiveStepByTrigger(array $triggers): array
    {
        if (empty($triggers)) {
            return [];
        }

        $query = 'SELECT step.* 
            FROM #__acym_step AS step 
            LEFT JOIN #__acym_automation AS automation ON step.automation_id = automation.id 
            WHERE automation.active = 1';

        foreach ($triggers as $i => $oneTrigger) {
            $triggers[$i] = 'step.triggers LIKE '.acym_escapeDB('%"'.$oneTrigger.'"%');
        }
        $query .= ' AND ('.implode(' OR ', $triggers).')';

        $steps = acym_loadObjectList($query);

        foreach ($steps as $i => $oneStep) {
            if (!empty($oneStep->triggers)) $steps[$i]->triggers = json_decode($oneStep->triggers, true);
        }

        return $steps;
    }

    public function delete(array $elements): int
    {
        if (empty($elements)) return 0;

        acym_arrayToInteger($elements);

        $conditions = acym_loadResultArray('SELECT id FROM #__acym_condition WHERE step_id IN ('.implode(',', $elements).')');
        $conditionClass = new ConditionClass();
        $conditionClass->delete($conditions);

        return parent::delete($elements);
    }
}
