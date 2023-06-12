<?php

use AcyMailing\Types\OperatorinType;

trait PayplansAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        $allPlans = acym_loadObjectList('SELECT `title` AS `text`, `plan_id` AS `value` FROM `#__payplans_plan` ORDER BY `ordering` ASC');
        if (empty($allPlans)) return;

        acym_loadLanguageFile('com_payplans', JPATH_SITE);

        $conditions['user']['payplans'] = new stdClass();
        $conditions['user']['payplans']->name = 'Payplans';
        $conditions['user']['payplans']->option = '<div class="cell grid-x grid-margin-x">';

        $operatorIn = new OperatorinType();

        $conditions['user']['payplans']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['payplans']->option .= $operatorIn->display('acym_condition[conditions][__numor__][__numand__][payplans][type]');
        $conditions['user']['payplans']->option .= '</div>';

        $firstGroup = new stdClass();
        $firstGroup->text = acym_translation('ACYM_ANY_PLAN');
        $firstGroup->value = 0;
        array_unshift($allPlans, $firstGroup);

        $conditions['user']['payplans']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['payplans']->option .= acym_select(
            $allPlans,
            'acym_condition[conditions][__numor__][__numand__][payplans][plan]',
            '',
            ['class' => 'acym__select']
        );
        $conditions['user']['payplans']->option .= '</div>';

        $status = [];
        $status[] = acym_selectOption('', acym_translation('ACYM_ANY_STATUS'));
        $status[] = acym_selectOption('1601', acym_translation('COM_PAYPLANS_STATUS_SUBSCRIPTION_ACTIVE'));
        $status[] = acym_selectOption('1602', acym_translation('COM_PAYPLANS_STATUS_SUBSCRIPTION_HOLD'));
        $status[] = acym_selectOption('1603', acym_translation('COM_PAYPLANS_STATUS_SUBSCRIPTION_EXPIRED'));

        $conditions['user']['payplans']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['payplans']->option .= acym_select(
            $status,
            'acym_condition[conditions][__numor__][__numand__][payplans][status]',
            '',
            ['class' => 'acym__select']
        );
        $conditions['user']['payplans']->option .= '</div>';


        $conditions['user']['payplans']->option .= '<div class="cell grid-x margin-top-1 margin-bottom-1">';
        $conditions['user']['payplans']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][payplans][signup_date_inf]', '', 'cell shrink');
        $conditions['user']['payplans']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['payplans']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_SUBSCRIPTION_DATE').'</span>';
        $conditions['user']['payplans']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['payplans']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][payplans][signup_date_sup]', '', 'cell shrink');
        $conditions['user']['payplans']->option .= '</div>';

        $conditions['user']['payplans']->option .= '<div class="cell grid-x">';
        $conditions['user']['payplans']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][payplans][expiration_date_inf]', '', 'cell shrink');
        $conditions['user']['payplans']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['payplans']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_END_DATE').'</span>';
        $conditions['user']['payplans']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['payplans']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][payplans][expiration_date_sup]', '', 'cell shrink');
        $conditions['user']['payplans']->option .= '</div>';

        $conditions['user']['payplans']->option .= '</div>';
    }

    public function onAcymProcessCondition_payplans(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_payplans($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_payplans(&$query, $options, $num)
    {
        $lj = '`#__payplans_subscription` AS payplans'.$num.' ON payplans'.$num.'.`user_id` = user.`cms_id`';
        if (!empty($options['plan'])) $lj .= ' AND payplans'.$num.'.`plan_id` = '.intval($options['plan']);
        if (!empty($options['status'])) $lj .= ' AND payplans'.$num.'.`status` = '.intval($options['status']);

        if (!empty($options['signup_date_inf'])) {
            $options['signup_date_inf'] = acym_replaceDate($options['signup_date_inf']);
            if (!is_numeric($options['signup_date_inf'])) $options['signup_date_inf'] = strtotime($options['signup_date_inf']);
            $lj .= ' AND payplans'.$num.'.`subscription_date` > '.acym_escapeDB(acym_date($options['signup_date_inf'], 'Y-m-d H:i', false));
        }
        if (!empty($options['signup_date_sup'])) {
            $options['signup_date_sup'] = acym_replaceDate($options['signup_date_sup']);
            if (!is_numeric($options['signup_date_sup'])) $options['signup_date_sup'] = strtotime($options['signup_date_sup']);
            $lj .= ' AND payplans'.$num.'.`subscription_date` < '.acym_escapeDB(acym_date($options['signup_date_sup'], 'Y-m-d H:i', false));
        }

        if (!empty($options['expiration_date_inf'])) {
            $options['expiration_date_inf'] = acym_replaceDate($options['expiration_date_inf']);
            if (!is_numeric($options['expiration_date_inf'])) $options['expiration_date_inf'] = strtotime($options['expiration_date_inf']);
            $lj .= ' AND (payplans'.$num.'.`expiration_date` > '.acym_escapeDB(acym_date($options['expiration_date_inf'], 'Y-m-d H:i', false));
            $lj .= ' OR payplans'.$num.'.`expiration_date` = "0000-00-00 00:00:00")';
        }
        if (!empty($options['expiration_date_sup'])) {
            $options['expiration_date_sup'] = acym_replaceDate($options['expiration_date_sup']);
            if (!is_numeric($options['expiration_date_sup'])) $options['expiration_date_sup'] = strtotime($options['expiration_date_sup']);
            $lj .= ' AND payplans'.$num.'.`expiration_date` < '.acym_escapeDB(acym_date($options['expiration_date_sup'], 'Y-m-d H:i', false));
            $lj .= ' AND payplans'.$num.'.`expiration_date` > "0000-00-00 00:00:00"';
        }

        $query->leftjoin['payplans'.$num] = $lj;
        $query->where['member'] = 'user.cms_id > 0';

        $operator = (empty($options['type']) || $options['type'] == 'in') ? 'IS NOT NULL' : 'IS NULL';
        $query->where[] = 'payplans'.$num.'.`user_id` '.$operator;
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        if (!empty($automationCondition['payplans'])) {
            acym_loadLanguageFile('com_payplans', JPATH_SITE);

            if (empty($automationCondition['payplans']['plan'])) {
                $element = acym_translation('ACYM_ANY_PLAN');
            } else {
                $element = acym_loadResult('SELECT `title` FROM #__payplans_plan WHERE `plan_id` = '.intval($automationCondition['payplans']['plan']));
            }

            $status = [
                '' => 'ACYM_ANY',
                '1601' => 'COM_PAYPLANS_STATUS_SUBSCRIPTION_ACTIVE',
                '1602' => 'COM_PAYPLANS_STATUS_SUBSCRIPTION_HOLD',
                '1603' => 'COM_PAYPLANS_STATUS_SUBSCRIPTION_EXPIRED',
            ];

            $status = acym_translation($status[$automationCondition['payplans']['status']]);

            $finalText = acym_translationSprintf('ACYM_REGISTERED', $element, $status);

            $dates = [];
            if (!empty($automationCondition['payplans']['signup_date_inf'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['payplans']['signup_date_inf'], true);
            }

            if (!empty($automationCondition['payplans']['signup_date_sup'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['payplans']['signup_date_sup'], true);
            }

            if (!empty($dates)) {
                $finalText .= '<br />'.acym_translation('ACYM_SUBSCRIPTION_DATE').': '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $dates = [];
            if (!empty($automationCondition['payplans']['expiration_date_inf'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['payplans']['expiration_date_inf'], true);
            }

            if (!empty($automationCondition['payplans']['expiration_date_sup'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['payplans']['expiration_date_sup'], true);
            }

            if (!empty($dates)) {
                $finalText .= '<br />'.acym_translation('ACYM_END_DATE').': '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }
    }
}
