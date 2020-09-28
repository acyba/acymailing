<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class RuleClass extends acymClass
{
    var $table = 'rule';
    var $pkey = 'id';
    var $errors = [];

    public function getAll($key = null, $active = false)
    {
        $rules = acym_loadObjectList('SELECT * FROM `#__acym_rule` '.($active ? 'WHERE active = 1' : '').' ORDER BY `ordering` ASC');

        foreach ($rules as $i => $rule) {
            $rules[$i] = $this->_prepareRule($rule);
        }

        return $rules;
    }

    public function getOneById($id)
    {
        $rule = acym_loadObject('SELECT * FROM `#__acym_rule` WHERE `id` = '.intval($id));

        return $this->_prepareRule($rule);
    }

    private function _prepareRule($rule)
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

    public function save($rule)
    {
        if (empty($rule)) {
            return false;
        }

        return parent::save($rule);
    }

    public function delete($ids)
    {
        return parent::delete($ids);
    }

    public function getOrderingNumber()
    {
        $query = 'SELECT COUNT(id) FROM #__acym_rule';

        return acym_loadResult($query);
    }

    public function cleanTable()
    {
        acym_query('TRUNCATE TABLE `#__acym_rule`');
    }
}
