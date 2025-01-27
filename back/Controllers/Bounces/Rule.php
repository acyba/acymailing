<?php

namespace AcyMailing\Controllers\Bounces;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\RuleClass;

trait Rule
{
    public function rule()
    {
        $ruleClass = new RuleClass();
        acym_setVar('layout', 'rule');
        $ruleId = acym_getVar('int', 'ruleId', 0);
        $listsClass = new ListClass();

        if (!empty($ruleId)) {
            $rule = $ruleClass->getOneById($ruleId);
            $this->breadcrumb[acym_translation($rule->name)] = acym_completeLink('bounces&task=rule&ruleId='.$ruleId);
        } else {
            $this->breadcrumb[acym_translation('ACYM_NEW')] = acym_completeLink('bounces&task=rule');
            $rule = new \stdClass();
            $rule->name = '';
            $rule->active = 1;
            $rule->regex = '';
            $rule->executed_on = [];
            $rule->action_message = [];
            $rule->action_user = [];
            $rule->increment_stats = 0;
            $rule->execute_action_after = 0;
        }

        $data = [
            'ruleId' => $ruleId,
            'lists' => $listsClass->getAllWithIdName(),
            'rule' => $rule,
        ];

        parent::display($data);
    }

    public function applyRule()
    {
        $this->storeRule();
        $this->rule();
    }

    public function saveRule()
    {
        $this->storeRule();
        $this->listing();
    }

    public function storeRule()
    {
        $rule = acym_getVar('array', 'bounce');

        $ruleClass = new RuleClass();

        $rule['executed_on'] = !empty($rule['executed_on']) ? json_encode($rule['executed_on']) : '[]';

        if (!empty($rule['action_user'])) {
            if (in_array('subscribe_user', $rule['action_user'])) {
                $rule['action_user']['subscribe_user_list'] = $rule['subscribe_user_list'];
            }
        }
        unset($rule['subscribe_user_list']);

        if (!empty($rule['action_message']) && !in_array('forward_message', $rule['action_message'])) {
            unset($rule['action_message']['forward_to']);
        }

        if (empty($rule['id'])) {
            $rule['ordering'] = $ruleClass->getOrderingNumber() + 1;
        }

        $ruleObject = new \stdClass();
        $ruleObject->executed_on = '[]';
        $ruleObject->action_message = '[]';
        $ruleObject->action_user = '[]';
        $ruleObject->description = $rule['description'];

        foreach ($rule as $column => $value) {
            acym_secureDBColumn($column);
            if (is_array($value) || is_object($value)) {
                $ruleObject->$column = json_encode($value);
            } else {
                $ruleObject->$column = strip_tags($value);
            }
        }

        $res = $ruleClass->save($ruleObject);

        if (!$res) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
        } else {
            acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');
            acym_setVar('ruleId', $res);
        }
    }
}
