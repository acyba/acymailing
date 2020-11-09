<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class ConditionClass extends acymClass
{
    var $table = 'condition';
    var $pkey = 'id';

    public function getOneByStepId($stepId)
    {
        $query = 'SELECT * FROM #__acym_condition WHERE step_id = '.intval($stepId);

        return acym_loadObject($query);
    }

    public function delete($elements)
    {
        if (empty($elements)) return 0;

        if (!is_array($elements)) $elements = [$elements];
        acym_arrayToInteger($elements);

        $actionClass = new ActionClass();
        $actionsIds = $actionClass->getAllActionsIdByConditionsId($elements);
        if (!empty($actionsIds)) $actionClass->delete($actionsIds);

        return parent::delete($elements);
    }

    public function save($condition)
    {
        foreach ($condition as $oneAttribute => $value) {
            if (empty($value)) {
                continue;
            }

            if (is_array($value)) {
                $value = json_encode($value);
            }
            if ($oneAttribute != 'conditions') $condition->$oneAttribute = strip_tags($value);
        }

        return parent::save($condition);
    }


    public function getConditionsByStepId($id)
    {
        $query = 'SELECT acycondition.* FROM #__acym_condition as acycondition LEFT JOIN #__acym_step AS step ON step.id = acycondition.step_id WHERE step.id = '.intval($id);

        return acym_loadObjectList($query);
    }
}
