<?php

use AcyMailing\Core\AcymPlugin;
use AcyMailing\Types\OperatorInType;

class plgAcymCbsubs extends AcymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'Community Builder - Subscriptions',
            'description' => '- Filter AcyMailing users based on their CBSubs subscriptions',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/cb-subscriptions',
            'category' => 'Subscription system',
            'level' => 'starter',
        ];
        $this->installed = in_array(acym_getPrefix().'cbsubs_plans', acym_getTableList());
    }

    public function onAcymDeclareConditions(&$conditions)
    {
        $allGroups = acym_loadObjectList('SELECT `name` AS `text`, `id` AS `value` FROM #__cbsubs_plans ORDER BY `ordering` ASC');
        if (empty($allGroups)) return;


        $conditions['user']['cbsubs'] = new stdClass();
        $conditions['user']['cbsubs']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'Community Builder', 'Subscriptions');
        $conditions['user']['cbsubs']->option = '<div class="cell grid-x grid-margin-x">';

        $operatorIn = new OperatorInType();

        $conditions['user']['cbsubs']->option .= '<div class="intext_select_automation cell acym__small_select">';
        $conditions['user']['cbsubs']->option .= $operatorIn->display('acym_condition[conditions][__numor__][__numand__][cbsubs][type]');
        $conditions['user']['cbsubs']->option .= '</div>';

        $firstGroup = new stdClass();
        $firstGroup->text = acym_translation('ACYM_ANY_PLAN');
        $firstGroup->value = 0;
        array_unshift($allGroups, $firstGroup);

        $conditions['user']['cbsubs']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['cbsubs']->option .= acym_select(
            $allGroups,
            'acym_condition[conditions][__numor__][__numand__][cbsubs][plan]',
            '',
            ['class' => 'acym__select']
        );
        $conditions['user']['cbsubs']->option .= '</div>';

        $statusCBSubs = [];
        $statusCBSubs[] = acym_selectOption('', acym_translation('ACYM_ANY_STATUS'));
        $statusCBSubs[] = acym_selectOption('A', 'Active');
        $statusCBSubs[] = acym_selectOption('R', 'Registered Unpaid');
        $statusCBSubs[] = acym_selectOption('X', 'Expired');
        $statusCBSubs[] = acym_selectOption('C', 'Unsubscribed');
        $statusCBSubs[] = acym_selectOption('U', 'Upgraded to other');
        $statusCBSubs[] = acym_selectOption('I', 'Invalid');

        $conditions['user']['cbsubs']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['cbsubs']->option .= acym_select(
            $statusCBSubs,
            'acym_condition[conditions][__numor__][__numand__][cbsubs][status]',
            '',
            ['class' => 'acym__select']
        );
        $conditions['user']['cbsubs']->option .= '</div>';

        $autoRecurring = [];
        $autoRecurring[] = acym_selectOption('', 'Autorecurring Type');
        $autoRecurring[] = acym_selectOption('0', 'Not autorecurring');
        $autoRecurring[] = acym_selectOption('1', 'Autorecurring without notifications');
        $autoRecurring[] = acym_selectOption('2', 'Autorecurring with notifications');

        $conditions['user']['cbsubs']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['cbsubs']->option .= acym_select(
            $autoRecurring,
            'acym_condition[conditions][__numor__][__numand__][cbsubs][recurring]',
            '',
            ['class' => 'acym__select']
        );
        $conditions['user']['cbsubs']->option .= '</div>';

        $conditions['user']['cbsubs']->option .= '<div class="cell grid-x margin-top-1 margin-bottom-1">';
        $conditions['user']['cbsubs']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][cbsubs][signup_date_inf]', '', 'cell shrink');
        $conditions['user']['cbsubs']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['cbsubs']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_SUBSCRIPTION_DATE').'</span>';
        $conditions['user']['cbsubs']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['cbsubs']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][cbsubs][signup_date_sup]', '', 'cell shrink');
        $conditions['user']['cbsubs']->option .= '</div>';

        $conditions['user']['cbsubs']->option .= '<div class="cell grid-x">';
        $conditions['user']['cbsubs']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][cbsubs][expiration_date_inf]', '', 'cell shrink');
        $conditions['user']['cbsubs']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['cbsubs']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_END_DATE').'</span>';
        $conditions['user']['cbsubs']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['cbsubs']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][cbsubs][expiration_date_sup]', '', 'cell shrink');
        $conditions['user']['cbsubs']->option .= '</div>';

        $conditions['user']['cbsubs']->option .= '</div>';
    }

    public function onAcymDeclareConditionsScenario(&$conditions){
        $this->onAcymDeclareConditions($conditions);
    }

    public function onAcymProcessCondition_cbsubs(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_cbsubs($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_cbsubs(&$query, $options, $num)
    {
        $lj = '`#__cbsubs_subscriptions` AS cbsubs'.$num.' ON cbsubs'.$num.'.`user_id` = user.`cms_id`';
        if (!empty($options['plan'])) $lj .= ' AND cbsubs'.$num.'.`plan_id` = '.intval($options['plan']);
        if (!empty($options['status'])) $lj .= ' AND cbsubs'.$num.'.`status` = '.acym_escapeDB($options['status']);
        if (isset($options['recurring']) && strlen($options['recurring']) > 0) $lj .= ' AND cbsubs'.$num.'.`autorecurring_type` = '.intval($options['recurring']);

        if (!empty($options['signup_date_inf'])) {
            $options['signup_date_inf'] = acym_replaceDate($options['signup_date_inf']);
            if (!is_numeric($options['signup_date_inf'])) $options['signup_date_inf'] = strtotime($options['signup_date_inf']);
            $lj .= ' AND cbsubs'.$num.'.`subscription_date` > '.acym_escapeDB(acym_date($options['signup_date_inf'], 'Y-m-d H:i', false));
        }
        if (!empty($options['signup_date_sup'])) {
            $options['signup_date_sup'] = acym_replaceDate($options['signup_date_sup']);
            if (!is_numeric($options['signup_date_sup'])) $options['signup_date_sup'] = strtotime($options['signup_date_sup']);
            $lj .= ' AND cbsubs'.$num.'.`subscription_date` < '.acym_escapeDB(acym_date($options['signup_date_sup'], 'Y-m-d H:i', false));
        }

        if (!empty($options['expiration_date_inf'])) {
            $options['expiration_date_inf'] = acym_replaceDate($options['expiration_date_inf']);
            if (!is_numeric($options['expiration_date_inf'])) $options['expiration_date_inf'] = strtotime($options['expiration_date_inf']);
            $lj .= ' AND (cbsubs'.$num.'.`expiry_date` > '.acym_escapeDB(acym_date($options['expiration_date_inf'], 'Y-m-d H:i', false));
            $lj .= ' OR cbsubs'.$num.'.`expiry_date` = "0000-00-00 00:00:00")';
        }
        if (!empty($options['expiration_date_sup'])) {
            $options['expiration_date_sup'] = acym_replaceDate($options['expiration_date_sup']);
            if (!is_numeric($options['expiration_date_sup'])) $options['expiration_date_sup'] = strtotime($options['expiration_date_sup']);
            $lj .= ' AND cbsubs'.$num.'.`expiry_date` < '.acym_escapeDB(acym_date($options['expiration_date_sup'], 'Y-m-d H:i', false));
            $lj .= ' AND cbsubs'.$num.'.`expiry_date` > "0000-00-00 00:00:00"';
        }

        $query->leftjoin['cbsubs_'.$num] = $lj;
        $query->where['member'] = 'user.`cms_id` > 0';
        $operator = (empty($options['type']) || $options['type'] === 'in') ? 'IS NOT NULL' : 'IS NULL';
        $query->where[] = 'cbsubs'.$num.'.`user_id` '.$operator;
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        if (empty($automationCondition['cbsubs'])) return;

        if (empty($automationCondition['cbsubs']['plan'])) {
            $element = acym_translation('ACYM_ANY_PLAN');
        } else {
            $element = acym_loadResult('SELECT `name` FROM #__cbsubs_plans WHERE `id` = '.intval($automationCondition['cbsubs']['plan']));
        }

        $status = [
            '' => 'ACYM_ANY_STATUS',
            'A' => 'ACYM_ACTIVE',
            'R' => 'Registered Unpaid',
            'X' => 'Expired',
            'C' => 'ACYM_UNSUBSCRIBED',
            'U' => 'Upgraded to other',
            'I' => 'Invalid',
        ];

        $status = acym_translation($status[$automationCondition['cbsubs']['status']]);

        $finalText = acym_translationSprintf('ACYM_REGISTERED', $element, $status);

        $dates = [];
        if (!empty($automationCondition['cbsubs']['signup_date_inf'])) {
            $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['cbsubs']['signup_date_inf'], true);
        }

        if (!empty($automationCondition['cbsubs']['signup_date_sup'])) {
            $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['cbsubs']['signup_date_sup'], true);
        }

        if (!empty($dates)) {
            $finalText .= '<br />'.acym_translation('ACYM_SUBSCRIPTION_DATE').': '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
        }

        $dates = [];
        if (!empty($automationCondition['cbsubs']['expiration_date_inf'])) {
            $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['cbsubs']['expiration_date_inf'], true);
        }

        if (!empty($automationCondition['cbsubs']['expiration_date_sup'])) {
            $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['cbsubs']['expiration_date_sup'], true);
        }

        if (!empty($dates)) {
            $finalText .= '<br />'.acym_translation('ACYM_END_DATE').': '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
        }

        $automationCondition = $finalText;
    }

    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_cbsubs(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_cbsubs($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_cbsubs(&$query, $options, $num)
    {
        $this->processConditionFilter_cbsubs($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
