<?php

use AcyMailing\Types\OperatorinType;

trait MemberpressAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        $allGroups = acym_loadObjectList(
            'SELECT post.post_title AS `text`, post.`ID` AS `value` 
			 FROM #__posts AS post
             WHERE post.`post_type` = "memberpressproduct" AND post.`post_status` = "publish" 
             ORDER BY post.post_title ASC'
        );
        if (empty($allGroups)) return;

        $conditions['user']['memberpress'] = new stdClass();
        $conditions['user']['memberpress']->name = acym_translationSprintf('ACYM_INTEGRATION_PLUGIN_MEMBERSHIP', 'MemberPress');
        $conditions['user']['memberpress']->option = '<div class="cell grid-x grid-margin-x">';

        $operatorIn = new OperatorinType();

        $conditions['user']['memberpress']->option .= '<div class="intext_select_automation cell acym__small_select">';
        $conditions['user']['memberpress']->option .= $operatorIn->display('acym_condition[conditions][__numor__][__numand__][memberpress][type]');
        $conditions['user']['memberpress']->option .= '</div>';

        $firstGroup = new stdClass();
        $firstGroup->text = acym_translation('ACYM_ANY_MEMBERSHIP');
        $firstGroup->value = 0;
        array_unshift($allGroups, $firstGroup);

        $conditions['user']['memberpress']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['memberpress']->option .= acym_select(
            $allGroups,
            'acym_condition[conditions][__numor__][__numand__][memberpress][membership]',
            '',
            ['class' => 'acym__select']
        );
        $conditions['user']['memberpress']->option .= '</div>';

        $activeMemberPress = [
            acym_selectOption('', acym_translation('ACYM_ACTIVATED_OR_DEACTIVATED')),
            acym_selectOption(1, acym_translation('ACYM_ACTIVATED')),
            acym_selectOption(-1, acym_translation('ACYM_DEACTIVATED')),
        ];

        $conditions['user']['memberpress']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['memberpress']->option .= acym_select(
            $activeMemberPress,
            'acym_condition[conditions][__numor__][__numand__][memberpress][active]',
            '',
            ['class' => 'acym__select']
        );
        $conditions['user']['memberpress']->option .= '</div>';

        $statusMemberPress = [];
        $statusMemberPress[] = acym_selectOption('', acym_translation('ACYM_ANY_STATUS'));
        $statusMemberPress[] = acym_selectOption('active', acym_translation('ACYM_ENABLED'));
        $statusMemberPress[] = acym_selectOption('pending', acym_translation('ACYM_PENDING'));
        $statusMemberPress[] = acym_selectOption('suspended', acym_translation('ACYM_SUSPENDED'));
        $statusMemberPress[] = acym_selectOption('cancelled', acym_translation('ACYM_STOPPED'));

        $conditions['user']['memberpress']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['memberpress']->option .= acym_select(
            $statusMemberPress,
            'acym_condition[conditions][__numor__][__numand__][memberpress][status]',
            '',
            ['class' => 'acym__select']
        );
        $conditions['user']['memberpress']->option .= '</div>';

        $conditions['user']['memberpress']->option .= '<div class="cell grid-x margin-top-1 margin-bottom-1">';
        $conditions['user']['memberpress']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][memberpress][signup_date_inf]', '', 'cell shrink');
        $conditions['user']['memberpress']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['memberpress']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_SUBSCRIPTION_DATE').'</span>';
        $conditions['user']['memberpress']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['memberpress']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][memberpress][signup_date_sup]', '', 'cell shrink');
        $conditions['user']['memberpress']->option .= '</div>';

        $conditions['user']['memberpress']->option .= '<div class="cell grid-x">';
        $conditions['user']['memberpress']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][memberpress][expiration_date_inf]', '', 'cell shrink');
        $conditions['user']['memberpress']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['memberpress']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_END_DATE').'</span>';
        $conditions['user']['memberpress']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink margin-left-1 margin-right-1"><</span>';
        $conditions['user']['memberpress']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][memberpress][expiration_date_sup]', '', 'cell shrink');
        $conditions['user']['memberpress']->option .= '</div>';

        $conditions['user']['memberpress']->option .= '</div>';
    }

    public function onAcymProcessCondition_memberpress(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_memberpress($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_memberpress(&$query, $options, $num)
    {
        $transaction = 'memberpress_transaction'.$num;
        $subscription = 'memberpress_sub'.$num;
        $joinTransaction = '`#__mepr_transactions` AS '.$transaction.' ON '.$transaction.'.`user_id` = user.`cms_id` AND user.`cms_id` > 0';
        if (!empty($options['membership'])) $joinTransaction .= ' AND '.$transaction.'.`product_id` = '.intval($options['membership']);

        if (!empty($options['signup_date_inf'])) {
            $options['signup_date_inf'] = acym_replaceDate($options['signup_date_inf']);
            if (!is_numeric($options['signup_date_inf'])) $options['signup_date_inf'] = strtotime($options['signup_date_inf']);
            $joinTransaction .= ' AND '.$transaction.'.`created_at` > '.acym_escapeDB(acym_date($options['signup_date_inf'], 'Y-m-d H:i', false));
        }
        if (!empty($options['signup_date_sup'])) {
            $options['signup_date_sup'] = acym_replaceDate($options['signup_date_sup']);
            if (!is_numeric($options['signup_date_sup'])) $options['signup_date_sup'] = strtotime($options['signup_date_sup']);
            $joinTransaction .= ' AND '.$transaction.'.`created_at` < '.acym_escapeDB(acym_date($options['signup_date_sup'], 'Y-m-d H:i', false));
        }

        if (!empty($options['expiration_date_inf'])) {
            $options['expiration_date_inf'] = acym_replaceDate($options['expiration_date_inf']);
            if (!is_numeric($options['expiration_date_inf'])) $options['expiration_date_inf'] = strtotime($options['expiration_date_inf']);
            $joinTransaction .= ' AND ('.$transaction.'.`expires_at` > '.acym_escapeDB(acym_date($options['expiration_date_inf'], 'Y-m-d H:i', false));
            $joinTransaction .= ' OR '.$transaction.'.`expires_at` = "0000-00-00 00:00:00")';
        }
        if (!empty($options['expiration_date_sup'])) {
            $options['expiration_date_sup'] = acym_replaceDate($options['expiration_date_sup']);
            if (!is_numeric($options['expiration_date_sup'])) $options['expiration_date_sup'] = strtotime($options['expiration_date_sup']);
            $joinTransaction .= ' AND '.$transaction.'.`expires_at` < '.acym_escapeDB(acym_date($options['expiration_date_sup'], 'Y-m-d H:i', false));
            $joinTransaction .= ' AND '.$transaction.'.`expires_at` > "0000-00-00 00:00:00"';
        }

        $options['active'] = intval($options['active']);
        if (in_array($options['active'], [1, -1])) {
            $joinTransaction .= ' AND '.$transaction.'.`status` '.($options['active'] === 1 ? '=' : '!=').' "complete"';
        }

        if (empty($options['type']) || $options['type'] === 'in') {
            $query->join[$transaction] = $joinTransaction;

            if (!empty($options['status'])) {
                $query->join[$subscription] = '`#__mepr_subscriptions` AS '.$subscription.' ON '.$subscription.'.`id` = '.$transaction.'.`subscription_id`';
                $query->where[] = $subscription.'.`status` = '.acym_escapeDB($options['status']);
            }
        } else {
            $query->leftjoin[$transaction] = $joinTransaction;

            if (empty($options['status'])) {
                $query->where[] = $transaction.'.`user_id` IS NULL';
            } else {
                $query->leftjoin[$subscription] = '`#__mepr_subscriptions` AS '.$subscription.' ON '.$subscription.'.`id` = '.$transaction.'.`subscription_id`';
                $query->leftjoin[$subscription] .= ' AND '.$subscription.'.`status` = '.acym_escapeDB($options['status']);
                $query->where[] = $subscription.'.`user_id` IS NULL';
            }
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        if (empty($automationCondition['memberpress'])) return;

        if (empty($automationCondition['memberpress']['membership'])) {
            $element = acym_translation('ACYM_ANY_MEMBERSHIP');
        } else {
            $element = acym_loadResult(
                'SELECT post_meta.meta_value FROM #__posts AS post
			 	 JOIN #__postmeta AS post_meta ON post.ID = post_meta.post_id 
			 	 	AND post_meta.meta_key = "_mepr_product_pricing_title" 
			 	 	AND post_meta.post_id = '.intval($automationCondition['memberpress']['membership']).'
				 WHERE post_type = "memberpressproduct" AND post_status = "publish"'
            );
        }


        $activeMemberPress = [
            '' => acym_translation('ACYM_ACTIVATED_OR_DEACTIVATED'),
            1 => acym_translation('ACYM_ACTIVATED'),
            -1 => acym_translation('ACYM_DEACTIVATED'),
        ];

        $statusMemberPress = [
            '' => acym_translation('ACYM_ANY_STATUS'),
            'active' => acym_translation('ACYM_ENABLED'),
            'pending' => acym_translation('ACYM_PENDING'),
            'suspended' => acym_translation('ACYM_SUSPENDED'),
            'cancelled' => acym_translation('ACYM_STOPPED'),
        ];

        $status = $statusMemberPress[$automationCondition['memberpress']['status']];
        $statusActive = $activeMemberPress[$automationCondition['memberpress']['active']];

        $finalText = acym_translationSprintf('ACYM_SUBSCRIBED_INTEGRATION_PLUGIN', $element, $statusActive, $status);

        $dates = [];
        if (!empty($automationCondition['memberpress']['signup_date_inf'])) {
            $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['memberpress']['signup_date_inf'], true);
        }

        if (!empty($automationCondition['memberpress']['signup_date_sup'])) {
            $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['memberpress']['signup_date_sup'], true);
        }

        if (!empty($dates)) {
            $finalText .= '<br />'.acym_translation('ACYM_SUBSCRIPTION_DATE').': '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
        }

        $dates = [];
        if (!empty($automationCondition['memberpress']['expiration_date_inf'])) {
            $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['memberpress']['expiration_date_inf'], true);
        }

        if (!empty($automationCondition['memberpress']['expiration_date_sup'])) {
            $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['memberpress']['expiration_date_sup'], true);
        }

        if (!empty($dates)) {
            $finalText .= '<br />'.acym_translation('ACYM_END_DATE').': '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
        }

        $automationCondition = $finalText;
    }
}
