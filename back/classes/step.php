<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class StepClass extends acymClass
{
    var $table = 'step';
    var $pkey = 'id';

    public function save($step)
    {
        foreach ($step as $oneAttribute => $value) {
            if (empty($value)) continue;
            if (is_array($value)) $value = json_encode($value);

            $step->$oneAttribute = strip_tags($value);
        }

        //Some of the database need a name for the column name, they add an index and the name need to be unique
        if (empty($step->name)) $step->name = 'step_'.time();

        return parent::save($step);
    }

    public function getOneStepByAutomationId($automationId)
    {
        return acym_loadObject('SELECT * FROM #__acym_step WHERE automation_id = '.intval($automationId));
    }

    public function getStepsByAutomationId($automationId)
    {
        return acym_loadObjectList('SELECT * FROM #__acym_step WHERE automation_id = '.intval($automationId));
    }

    public function getActiveStepByTrigger($triggers)
    {
        if (empty($triggers)) return [];

        if (!is_array($triggers)) $triggers = [$triggers];

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

    public function delete($elements)
    {
        if (!is_array($elements)) $elements = [$elements];
        acym_arrayToInteger($elements);

        if (empty($elements)) return 0;

        $conditions = acym_loadResultArray('SELECT id FROM #__acym_condition WHERE step_id IN ('.implode(',', $elements).')');
        $conditionClass = new ConditionClass();
        $conditionsDeleted = $conditionClass->delete($conditions);

        return parent::delete($elements);
    }
}
