<?php

namespace AcyMailing\Classes;

use AcyMailing\Core\AcymClass;

class ActionClass extends AcymClass
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'action';
        $this->pkey = 'id';
        $this->intColumns = [
            'id',
            'condition_id',
            'order',
        ];
    }

    public function getActionsByStepId(int $stepId): array
    {
        $actions = acym_loadObjectList(
            'SELECT `action`.* 
            FROM #__acym_action AS `action` 
            LEFT JOIN #__acym_condition AS `conditionT` ON `action`.condition_id = `conditionT`.id 
            WHERE `conditionT`.step_id = '.intval($stepId).' 
            ORDER BY `action`.order'
        );

        array_map([$this, 'fixTypes'], $actions);

        return $actions;
    }

    public function getActionsByConditionId(int $id): array
    {
        $actions = acym_loadObjectList(
            'SELECT action.* 
            FROM #__acym_action as action 
            WHERE action.condition_id = '.intval($id)
        );

        array_map([$this, 'fixTypes'], $actions);

        return $actions;
    }

    public function getOneByConditionId(int $id): ?object
    {
        $action = acym_loadObject(
            'SELECT `action`.* 
            FROM #__acym_action AS `action` 
            WHERE `action`.`condition_id` = '.intval($id)
        );

        if (empty($action)) {
            return null;
        }

        $this->fixTypes($action);

        return $action;
    }

    public function getAllActionsIdByConditionsId(array $elements): array
    {
        if (empty($elements)) {
            return [];
        }

        acym_arrayToInteger($elements);
        $ids = acym_loadResultArray('SELECT id FROM #__acym_action WHERE condition_id IN ('.implode(',', $elements).')');
        acym_arrayToInteger($ids);

        return $ids;
    }

    public function delete(array $elements): int
    {
        acym_arrayToInteger($elements);
        if (empty($elements)) return 0;
        $actions = acym_loadObjectList('SELECT * FROM #__acym_action WHERE id IN ('.implode(',', $elements).')');
        if (empty($actions)) return 0;

        $mailClass = new MailClass();

        foreach ($actions as $action) {
            $action->actions = json_decode($action->actions, true);
            if (!empty($action->actions)) {
                foreach ($action->actions as $innerAction) {
                    if (!empty($innerAction['acy_add_queue']) && !empty($innerAction['acy_add_queue']['mail_id'])) $mailClass->delete([$innerAction['acy_add_queue']['mail_id']]);
                }
            }
        }

        return parent::delete($elements);
    }

    public function save(object $element): ?int
    {
        if (!isset($element->order)) {
            $element->order = 1;
        }

        return parent::save($element);
    }

    public function getActionsByAutomationId(int $id): array
    {
        $actions = acym_loadObjectList(
            'SELECT action.* 
            FROM `#__acym_action` AS `action` 
            JOIN `#__acym_condition` AS `condition` 
                ON `action`.`condition_id` = `condition`.`id` 
            JOIN `#__acym_step` AS `step` 
                ON `condition`.`step_id` = `step`.`id` 
            WHERE `step`.`automation_id` = '.$id
        );
        array_map([$this, 'fixTypes'], $actions);

        return $actions;
    }
}
