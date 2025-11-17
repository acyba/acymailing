<?php

namespace AcyMailing\Classes;

use AcyMailing\Helpers\AutomationHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Core\AcymClass;

class AutomationClass extends AcymClass
{
    const ACTIONS_TO_SKIP = ['acy_send_email'];

    public bool $didAnAction = false;
    public array $report = [];

    public function __construct()
    {
        parent::__construct();

        $this->table = 'automation';
        $this->pkey = 'id';
        $this->intColumns = [
            'id',
            'active',
            'admin',
        ];
    }

    public function getMatchingElements(array $settings = []): array
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
            $query .= 'active = '.($settings['status'] === 'active' ? '1' : '0');
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

        $elements = acym_loadObjectList($query, '', $settings['offset'], $settings['elementsPerPage']);
        array_map([$this, 'fixTypes'], $elements);

        return [
            'elements' => $elements,
            'total' => acym_loadObject($queryCount),
        ];
    }

    public function save(object $element): ?int
    {
        foreach ($element as $oneAttribute => $value) {
            if (empty($value)) {
                continue;
            }

            $element->$oneAttribute = is_array($value) ? json_encode($value) : strip_tags($value);
        }

        return parent::save($element);
    }

    public function delete(array $elements): int
    {
        if (empty($elements)) return 0;

        acym_arrayToInteger($elements);

        $steps = acym_loadResultArray('SELECT id FROM #__acym_step WHERE automation_id IN ('.implode(',', $elements).')');
        $stepClass = new StepClass();
        $stepClass->delete($steps);

        return parent::delete($elements);
    }

    /**
     * @param mixed $trigger The identifier of the trigger
     * @param array $data    An array with data for user-type triggers (user id, order, event...)
     */
    public function trigger($triggers, array $data = []): void
    {
        if (!acym_level(ACYM_ENTERPRISE) || empty($triggers)) {
            return;
        }

        if (!is_array($triggers)) {
            $triggers = [$triggers];
        }

        $stepClass = new StepClass();
        $actionClass = new ActionClass();
        $conditionClass = new ConditionClass();

        $steps = $stepClass->getActiveStepByTrigger($triggers);

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
                        if (!$this->verifyCondition($condition->conditions, $newData)) continue;

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

    public function execute(object $action, array $data = []): bool
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

    private function verifyCondition($conditions, array $data = []): bool
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

    public function getAutomationsAdmin(array $ids = []): array
    {
        acym_arrayToInteger($ids);

        $query = 'SELECT * FROM #__acym_automation WHERE `admin` = 1';
        if (!empty($ids)) {
            $query .= ' AND `id` IN ('.implode(', ', $ids).')';
        }

        $automations = acym_loadObjectList($query, 'name');
        array_map([$this, 'fixTypes'], $automations);

        return $automations;
    }
}
