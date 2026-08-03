<?php

namespace AcyMailing\Classes;

use AcyMailing\Core\AcymClass;

class ConditionClass extends AcymClass
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'condition';
        $this->pkey = 'id';
    }

    public function getOneByStepId(int $stepId): ?object
    {
        $condition = acym_loadObject('SELECT * FROM #__acym_condition WHERE step_id = '.intval($stepId));

        return empty($condition) ? null : $condition;
    }

    public function delete(array $elements): int
    {
        if (empty($elements)) return 0;

        acym_arrayToInteger($elements);

        $actionClass = new ActionClass();
        $actionsIds = $actionClass->getAllActionsIdByConditionsId($elements);
        if (!empty($actionsIds)) $actionClass->delete($actionsIds);

        return parent::delete($elements);
    }

    public function save(object $element): ?int
    {
        foreach ($element as $oneAttribute => $value) {
            if (empty($value)) {
                continue;
            }

            if ($oneAttribute !== 'conditions') {
                $element->$oneAttribute = is_array($value) ? json_encode($value) : acym_stripTags($value);
            }
        }

        return parent::save($element);
    }

    public function getConditionsByStepId(int $id): array
    {
        $conditions = acym_loadObjectList(
            'SELECT * 
            FROM #__acym_condition 
            WHERE `step_id` = '.intval($id)
        );

        foreach ($conditions as $condition) {
            $condition->conditions = empty($condition->conditions) ? [] : json_decode($condition->conditions, true);
        }

        return $conditions;
    }
}
