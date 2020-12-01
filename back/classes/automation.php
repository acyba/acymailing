<?php

namespace AcyMailing\Classes;

use AcyMailing\Helpers\AutomationHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Libraries\acymClass;

class AutomationClass extends acymClass
{
    var $table = 'automation';
    var $pkey = 'id';
    var $didAnAction = false;
    var $report = [];

    public function getMatchingElements($settings = [])
    {
        $query = 'SELECT * FROM #__acym_automation';
        $queryCount = 'SELECT COUNT(id) AS total, SUM(active) AS totalActive FROM #__acym_automation';
        $filters = [];

        if (!empty($settings['search'])) {
            $filters[] = 'name LIKE '.acym_escapeDB('%'.$settings['search'].'%');
        }

        if (!empty($filters)) {
            $query .= ' WHERE ('.implode(') AND (', $filters).')';
            $queryCount .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        if (!empty($settings['status'])) {
            $query .= empty($filters) ? ' WHERE ' : ' AND ';
            $query .= 'active = '.($settings['status'] == 'active' ? '1' : '0');
        }

        if (!empty($settings['ordering']) && !empty($settings['ordering_sort_order'])) {
            $query .= ' ORDER BY '.acym_secureDBColumn($settings['ordering']).' '.acym_secureDBColumn(strtoupper($settings['ordering_sort_order']));
        } else {
            $query .= ' ORDER BY id asc';
        }

        if (empty($settings['offset']) || $settings['offset'] < 0) {
            $settings['offset'] = 0;
        }

        if (empty($settings['elementsPerPage']) || $settings['elementsPerPage'] < 1) {
            $pagination = new PaginationHelper();
            $settings['elementsPerPage'] = $pagination->getListLimit();
        }

        $results['elements'] = acym_loadObjectList($query, '', $settings['offset'], $settings['elementsPerPage']);

        $results['total'] = acym_loadObject($queryCount);

        return $results;
    }

    public function save($automation)
    {
        foreach ($automation as $oneAttribute => $value) {
            if (empty($value)) {
                continue;
            }

            if (is_array($value)) {
                $value = json_encode($value);
            }

            $automation->$oneAttribute = strip_tags($value);
        }

        return parent::save($automation);
    }

    public function delete($elements)
    {
        if (empty($elements)) return 0;

        if (!is_array($elements)) $elements = [$elements];
        acym_arrayToInteger($elements);

        $steps = acym_loadResultArray('SELECT id FROM #__acym_step WHERE automation_id IN ('.implode(',', $elements).')');
        $stepClass = new StepClass();
        $stepsDeleted = $stepClass->delete($steps);

        return parent::delete($elements);
    }

    /**
     * @param mixed $trigger The identifier of the trigger
     * @param array $data    An array with data for user-type triggers (user id, order, event...)
     */
    public function trigger($trigger, $data = [])
    {
        if (!acym_level(2) || empty($trigger)) return;

        $stepClass = new StepClass();
        $actionClass = new ActionClass();
        $conditionClass = new ConditionClass();
        $steps = $stepClass->getActiveStepByTrigger($trigger);

        $data['time'] = time();
        foreach ($steps as $step) {
            $newData = $data;
            $execute = false;

            // If we reached the next execution time we execute
            // next_execution is only set if one of the time triggers like "asap" or "day" is selected in the automation
            if (!empty($step->next_execution) && $step->next_execution <= $newData['time']) {
                $execute = true;
            }

            // Call the triggers to set the next execution time
            acym_trigger('onAcymExecuteTrigger', [&$step, &$execute, &$newData]);

            $newData['automation'] = $this->getOneById($step->automation_id);

            if ($execute) {
                $step->last_execution = $newData['time'];
                $conditions = $conditionClass->getConditionsByStepId($step->id);
                if (!empty($conditions)) {
                    foreach ($conditions as $condition) {
                        if (!$this->_verifyCondition($condition->conditions, $newData)) continue;

                        $actions = $actionClass->getActionsByStepId($step->id);
                        if (empty($actions)) continue;

                        foreach ($actions as $action) {
                            $this->execute($action, $newData);
                        }
                    }
                }
            }

            $stepClass->save($step);
        }
    }

    public function execute($action, $data = [])
    {
        $usersTriggeringAction = empty($data['userIds']) ? [] : $data['userIds'];
        $userTriggeringAction = empty($data['userId']) ? 0 : $data['userId'];
        $action->actions = json_decode($action->actions, true);
        if (empty($action->actions)) return false;

        $isMassAction = false;
        static $massAction = 0;
        if (empty($action->id)) {
            $action->id = $massAction--;
            $isMassAction = true;
        }

        $action->filters = json_decode($action->filters, true);
        if (empty($action->filters)) return false;


        $initialWhere = ['1 = 1'];
        $query = new AutomationHelper();
        $query->removeFlag($action->id);

        if (!empty($action->filters['type_filter']) && $action->filters['type_filter'] == 'user') {
            if (empty($usersTriggeringAction)) {
                if (empty($userTriggeringAction)) return false;

                $initialWhere = ['user.id = '.intval($userTriggeringAction)];
            } else {
                acym_arrayToInteger($usersTriggeringAction);
                $initialWhere = ['user.id IN ('.implode(', ', $usersTriggeringAction).')'];
            }
        }

        $typeFilter = $action->filters['type_filter'];

        unset($action->filters['type_filter']);
        if (empty($action->filters)) {
            $query->where = $initialWhere;
        }

        //We do the or first
        foreach ($action->filters as $or => $orValue) {
            if (empty($orValue)) {
                continue;
            }
            $num = 0;
            $query->where = $initialWhere;
            //Next the and
            foreach ($orValue as $and => $andValue) {
                $num++;
                //Finally we have all names filter
                foreach ($andValue as $filterName => $filterOptions) {
                    acym_trigger('onAcymProcessFilter_'.$filterName, [&$query, &$filterOptions, &$num]);
                }
            }

            $query->addFlag($action->id);
        }

        $this->didAnAction = $this->didAnAction || $query->count() > 0;
        foreach ($action->actions as $and => $andValue) {
            foreach ($andValue as $actionName => $actionOptions) {
                $this->report = array_merge(
                    $this->report,
                    acym_trigger(
                        'onAcymProcessAction_'.$actionName,
                        [&$query, &$actionOptions, ['automationAdmin' => !empty($data['automation']->admin), 'user_id' => $userTriggeringAction]]
                    )
                );
                $action->actions[$and][$actionName] = $actionOptions;
            }
        }

        if (!$isMassAction) {
            $action->filters['type_filter'] = $typeFilter;
            $action->filters = json_encode($action->filters);
            $action->actions = json_encode($action->actions);
            $actionClass = new ActionClass();
            $actionClass->save($action);
        }

        $query->removeFlag($action->id);

        return $this->didAnAction;
    }

    private function _verifyCondition($conditions, $data = [])
    {
        if (empty($conditions)) return true;
        $userTriggeringAction = empty($data['userId']) ? 0 : $data['userId'];
        $usersTriggeringAction = empty($data['userIds']) ? [] : $data['userIds'];

        $conditions = json_decode($conditions, true);
        $query = new AutomationHelper();
        $initialWhere = ['1 = 1'];
        if (!empty($conditions['type_condition']) && $conditions['type_condition'] == 'user') {
            if (empty($usersTriggeringAction)) {
                if (empty($userTriggeringAction)) return false;

                $initialWhere = ['user.id = '.intval($userTriggeringAction)];
            } else {
                acym_arrayToInteger($usersTriggeringAction);
                $initialWhere = ['user.id IN ('.implode(', ', $usersTriggeringAction).')'];
            }
        }
        unset($conditions['type_condition']);

        if (empty($conditions)) return true;

        foreach ($conditions as $or => $orValue) {
            if (empty($orValue)) continue;

            // we increment id condition not validate
            $conditionNotValid = 0;
            $num = 0;
            //Next the and
            foreach ($orValue as $and => $andValue) {
                $num++;
                $query->where = $initialWhere;
                //Finally we have all names condition
                foreach ($andValue as $filterName => $filterOptions) {
                    acym_trigger('onAcymProcessCondition_'.$filterName, [&$query, &$filterOptions, &$num, &$conditionNotValid]);
                }
            }

            if ($conditionNotValid == 0) return true;
        }

        return false;
    }

    public function getAutomationsAdmin($ids = [])
    {
        acym_arrayToInteger($ids);

        $query = 'SELECT * FROM #__acym_automation WHERE `admin` = 1';
        if (!empty($ids)) $query .= ' AND `id` IN ('.implode(', ', $ids).')';

        return acym_loadObjectList($query, 'name');
    }
}
