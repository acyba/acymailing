<?php

class acymactionClass extends acymClass
{
    var $table = 'action';
    var $pkey = 'id';

    public function getActionsByStepId($stepId)
    {
        $query = 'SELECT action.* FROM #__acym_action AS action LEFT JOIN #__acym_condition AS conditionT ON action.condition_id = conditionT.id WHERE conditionT.step_id = '.intval($stepId).' ORDER BY action.order';

        return acym_loadObjectList($query);
    }

    public function getActionsByConditionId($id)
    {
        $query = 'SELECT action.* FROM #__acym_action as action LEFT JOIN #__acym_condition as acycondition ON acycondition.id = action.condition_id WHERE acycondition.id = '.intval($id);

        return acym_loadObjectList($query);
    }

    public function getAllActionsIdByConditionsId($elements)
    {
        acym_arrayToInteger($elements);

        return acym_loadResultArray('SELECT id FROM #__acym_action WHERE condition_id IN ('.implode(',', $elements).')');
    }

    public function delete($elements)
    {
        acym_arrayToInteger($elements);
        $actions = acym_loadObjectList('SELECT * FROM #__acym_action WHERE id IN ('.implode(',', $elements).')');
        if (empty($actions)) return;

        $mailClass = acym_get('class.mail');

        foreach ($actions as $action) {
            $action->actions = json_decode($action->actions, true);
            if (!empty($action->actions)) {
                foreach ($action->actions as $innerAction) {
                    if (!empty($innerAction['acy_add_queue']) && !empty($innerAction['acy_add_queue']['mail_id'])) $mailClass->delete($innerAction['acy_add_queue']['mail_id']);
                }
            }
        }

        parent::delete($elements);
    }
}
