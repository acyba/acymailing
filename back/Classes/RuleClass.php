<?php

namespace AcyMailing\Classes;

use AcyMailing\Core\AcymClass;

class RuleClass extends AcymClass
{
    public const FINAL_RULE_ID = 17;

    public function __construct()
    {
        parent::__construct();

        $this->table = 'rule';
        $this->pkey = 'id';
    }

    public function getAll(?string $key = null): array
    {
        $rules = acym_loadObjectList('SELECT * FROM `#__acym_rule` ORDER BY `ordering` ASC');

        foreach ($rules as $i => $rule) {
            $rules[$i] = $this->prepareRule($rule);
        }

        return $rules;
    }

    public function getOneById(int $id): ?object
    {
        $rule = acym_loadObject('SELECT * FROM `#__acym_rule` WHERE `id` = '.intval($id));

        return empty($rule) ? null : $this->prepareRule($rule);
    }

    private function prepareRule(object $rule): object
    {
        //We have a rule from the database, let's prepare it to be displayed nicely
        $columns = ['executed_on', 'action_message', 'action_user'];
        foreach ($columns as $oneColumn) {
            if (!empty($rule->$oneColumn)) {
                $rule->$oneColumn = json_decode($rule->$oneColumn, true);
            }
        }

        return $rule;
    }

    public function save(object $element): ?int
    {
        if (empty($element)) {
            return null;
        }

        return parent::save($element);
    }

    public function getOrderingNumber(): int
    {
        return (int)acym_loadResult('SELECT COUNT(`id`) FROM #__acym_rule');
    }

    public function cleanTable(): void
    {
        acym_query('TRUNCATE TABLE `#__acym_rule`');
    }
}
