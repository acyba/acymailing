<?php

use AcyMailing\Classes\ListClass;

trait SubscriptionAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        $listClass = new ListClass();
        $list = [
            'type' => [
                'sub' => acym_translation('ACYM_SUBSCRIBED'),
                'unsub' => acym_translation('ACYM_UNSUBSCRIBED'),
                'notsub' => acym_translation('ACYM_NO_SUBSCRIPTION_STATUS'),
            ],
            'lists' => $listClass->getAllForSelect(),
            'date' => [
                'subscription_date' => acym_translation('ACYM_SUBSCRIPTION_DATE'),
                'unsubscribe_date' => acym_translation('ACYM_UNSUBSCRIPTION_DATE'),
            ],
        ];

        $conditions['user']['acy_list'] = new stdClass();
        $conditions['user']['acy_list']->name = acym_translation('ACYM_ACYMAILING_LIST');
        $conditions['user']['acy_list']->option = '<div class="intext_select_automation cell">';
        $conditions['user']['acy_list']->option .= acym_select(
            $list['type'],
            'acym_condition[conditions][__numor__][__numand__][acy_list][action]',
            null,
            ['class' => 'intext_select_automation acym__select']
        );
        $conditions['user']['acy_list']->option .= '</div>';
        $conditions['user']['acy_list']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['acy_list']->option .= acym_select(
            $list['lists'],
            'acym_condition[conditions][__numor__][__numand__][acy_list][list]',
            null,
            ['class' => 'intext_select_automation acym__select']
        );
        $conditions['user']['acy_list']->option .= '</div>';
        $conditions['user']['acy_list']->option .= '<br><div class="cell grid-x grid-margin-x">';
        $conditions['user']['acy_list']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][acy_list][date-min]');
        $conditions['user']['acy_list']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $conditions['user']['acy_list']->option .= '<div class="intext_select_automation">';
        $conditions['user']['acy_list']->option .= acym_select(
            $list['date'],
            'acym_condition[conditions][__numor__][__numand__][acy_list][date-type]',
            null,
            ['class' => 'intext_select_automation acym__select cell']
        );
        $conditions['user']['acy_list']->option .= '</div>';
        $conditions['user']['acy_list']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $conditions['user']['acy_list']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][acy_list][date-max]');

        $conditions['classic']['acy_list_all'] = new stdClass();
        $conditions['classic']['acy_list_all']->name = acym_translation('ACYM_NUMBER_USERS_LIST');
        $conditions['classic']['acy_list_all']->option = '<div class="cell shrink acym__automation__inner__text">'.acym_translation('ACYM_THERE_IS').'</div>';
        $conditions['classic']['acy_list_all']->option .= '<div class="intext_select_automation cell">';
        $conditions['classic']['acy_list_all']->option .= acym_select(
            ['>' => acym_translation('ACYM_MORE_THAN'), '<' => acym_translation('ACYM_LESS_THAN'), '=' => acym_translation('ACYM_EXACTLY')],
            'acym_condition[conditions][__numor__][__numand__][acy_list_all][operator]',
            null,
            ['class' => 'intext_select_automation acym__select']
        );
        $conditions['classic']['acy_list_all']->option .= '</div>';
        $conditions['classic']['acy_list_all']->option .= '<input type="number" min="0" class="intext_input_automation cell" name="acym_condition[conditions][__numor__][__numand__][acy_list_all][number]">';
        $conditions['classic']['acy_list_all']->option .= '<div class="cell shrink acym__automation__inner__text">'.acym_translation('ACYM_ACYMAILING_USERS').'</div>';
        $conditions['classic']['acy_list_all']->option .= '<div class="cell grid-x grid-margin-x margin-left-0" style="margin-bottom: 0"><div class="intext_select_automation cell">';
        $conditions['classic']['acy_list_all']->option .= acym_select(
            $list['type'],
            'acym_condition[conditions][__numor__][__numand__][acy_list_all][action]',
            null,
            ['class' => 'intext_select_automation acym__select']
        );
        $conditions['classic']['acy_list_all']->option .= '</div>';
        $conditions['classic']['acy_list_all']->option .= '<div class="intext_select_automation cell">';
        $conditions['classic']['acy_list_all']->option .= acym_select(
            $list['lists'],
            'acym_condition[conditions][__numor__][__numand__][acy_list_all][list]',
            null,
            ['class' => 'intext_select_automation acym__select']
        );
        $conditions['classic']['acy_list_all']->option .= '</div></div>';
        $conditions['classic']['acy_list_all']->option .= '<br><div class="cell grid-x grid-margin-x">';
        $conditions['classic']['acy_list_all']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][acy_list_all][date-min]');
        $conditions['classic']['acy_list_all']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $conditions['classic']['acy_list_all']->option .= '<div class="intext_select_automation">';
        $conditions['classic']['acy_list_all']->option .= acym_select(
            $list['date'],
            'acym_condition[conditions][__numor__][__numand__][acy_list_all][date-type]',
            null,
            ['class' => 'intext_select_automation acym__select cell']
        );
        $conditions['classic']['acy_list_all']->option .= '</div>';
        $conditions['classic']['acy_list_all']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $conditions['classic']['acy_list_all']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][acy_list_all][date-max]');
    }

    private function _processConditionAcyLists(&$query, &$options, $num)
    {
        $otherConditions = '';
        if (!empty($options['date-min'])) {
            $options['date-min'] = acym_replaceDate($options['date-min']);
            if (!is_numeric($options['date-min'])) {
                $options['date-min'] = strtotime($options['date-min']);
            }
            if (!empty($options['date-min'])) {
                $otherConditions .= ' AND userlist'.$num.'.'.acym_secureDBColumn($options['date-type']).' > '.acym_escapeDB(acym_date($options['date-min'], 'Y-m-d H:i:s', false));
            }
        }
        if (!empty($options['date-max'])) {
            $options['date-max'] = acym_replaceDate($options['date-max']);
            if (!is_numeric($options['date-max'])) {
                $options['date-max'] = strtotime($options['date-max']);
            }
            if (!empty($options['date-max'])) {
                $otherConditions .= ' AND userlist'.$num.'.'.acym_secureDBColumn($options['date-type']).' < '.acym_escapeDB(acym_date($options['date-max'], 'Y-m-d H:i:s', false));
            }
        }

        $query->leftjoin['list'.$num] = '#__acym_user_has_list as userlist'.$num.' ON user.id = userlist'.$num.'.user_id AND userlist'.$num.'.list_id = '.intval(
                $options['list']
            ).$otherConditions;
        if ($options['action'] == 'notsub') {
            $query->where[] = 'userlist'.$num.'.user_id IS NULL';
        } else {
            $status = $options['action'] == 'sub' ? '1' : '0';
            $query->where[] = 'userlist'.$num.'.status = '.intval($status);
        }

        return $query->count();
    }

    public function onAcymProcessCondition_acy_list(&$query, &$options, $num, &$conditionNotValid)
    {
        $affectedRows = $this->_processConditionAcyLists($query, $options, $num);
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function onAcymProcessCondition_acy_list_all(&$query, &$options, $num, &$conditionNotValid)
    {
        $affectedRows = $this->_processConditionAcyLists($query, $options, $num);

        $res = false;
        switch ($options['operator']) {
            case '=' :
                $res = $affectedRows == $options['number'];
                break;
            case '>' :
                $res = $affectedRows > $options['number'];
                break;
            case '<' :
                $res = $affectedRows < $options['number'];
                break;
        }

        if (!$res) $conditionNotValid++;
    }

    public function onAcymDeclareSummary_conditions(&$automation)
    {
        if (!empty($automation['acy_list_all'])) {
            $operators = ['=' => acym_translation('ACYM_EXACTLY'), '>' => acym_translation('ACYM_MORE_THAN'), '<' => acym_translation('ACYM_LESS_THAN')];
            $finalText = acym_translation('ACYM_THERE_IS').' '.acym_strtolower(
                    $operators[$automation['acy_list_all']['operator']]
                ).' '.$automation['acy_list_all']['number'].' '.acym_translation('ACYM_ACYMAILING_USERS').' ';
            $listClass = new ListClass();
            $automation['acy_list_all']['list'] = $listClass->getOneById($automation['acy_list_all']['list']);
            if (empty($automation['acy_list_all']['list'])) {
                $automation = '<span class="acym__color__red">'.acym_translation('ACYM_SELECT_A_LIST').'</span>';

                return;
            }
            if ($automation['acy_list_all']['action'] == 'sub') $automation['acy_list_all']['action'] = 'ACYM_SUBSCRIBED';
            if ($automation['acy_list_all']['action'] == 'unsub') $automation['acy_list_all']['action'] = 'ACYM_UNSUBSCRIBED';
            if ($automation['acy_list_all']['action'] == 'notsub') $automation['acy_list_all']['action'] = 'ACYM__NOT_SUBSCRIBED';
            $finalText .= acym_translationSprintf(
                    'ACYM_CONDITION_ACY_LIST_SUMMARY',
                    acym_translation($automation['acy_list_all']['action']),
                    $automation['acy_list_all']['list']->name
                ).' ';

            $automation = $this->_summaryDate($automation['acy_list_all'], $finalText);
        }

        $this->onAcymDeclareSummary_conditionsFilters($automation, 'ACYM_CONDITION_ACY_LIST_SUMMARY', 'ACYM_IS_SUBSCRIBED', 'ACYM_IS_UNSUBSCRIBED', 'ACYM_IS_NOT_SUBSCRIBED');
    }

    private function onAcymDeclareSummary_conditionsFilters(&$automation, $key, $keySub, $keyUnsub, $keyNotSub)
    {
        if (!empty($automation['acy_list'])) {
            $finalText = '';
            $listClass = new ListClass();
            $automation['acy_list']['list'] = $listClass->getOneById($automation['acy_list']['list']);
            if (empty($automation['acy_list']['list'])) {
                $automation = '<span class="acym__color__red">'.acym_translation('ACYM_SELECT_A_LIST').'</span>';

                return;
            }
            if ($automation['acy_list']['action'] == 'sub') $automation['acy_list']['action'] = $keySub;
            if ($automation['acy_list']['action'] == 'unsub') $automation['acy_list']['action'] = $keyUnsub;
            if ($automation['acy_list']['action'] == 'notsub') $automation['acy_list']['action'] = $keyNotSub;
            $finalText .= acym_translationSprintf(
                    $key,
                    acym_translation($automation['acy_list']['action']),
                    $automation['acy_list']['list']->name
                ).' ';

            $automation = $this->_summaryDate($automation['acy_list'], $finalText);
        }

        if (!empty($automation['unconfirmed'])) {
            $automation = acym_translation('ACYM_ACTION_UNCONFIRM');
        }
    }

    private function _summaryDate($automation, $finalText)
    {
        if (!empty($automation['date-min']) || !empty($automation['date-max'])) {
            $finalText .= acym_translationSprintf('ACYM_WHERE_DATE_ACY_LIST_SUMMARY', acym_strtolower(acym_translation('ACYM_'.strtoupper($automation['date-type']))));

            $dates = [];
            if (!empty($automation['date-min'])) {
                $automation['date-min'] = acym_replaceDate($automation['date-min']);
                $dates[] = acym_translationSprintf('ACYM_WHERE_DATE_MIN_ACY_LIST_SUMMARY', acym_date($automation['date-min'], 'd M Y H:i'));
            }
            if (!empty($automation['date-max'])) {
                $automation['date-max'] = acym_replaceDate($automation['date-max']);
                $dates[] = acym_translationSprintf('ACYM_WHERE_DATE_MAX_ACY_LIST_SUMMARY', acym_date($automation['date-max'], 'd M Y H:i'));
            }

            $finalText .= ' '.implode(' '.acym_strtolower(acym_translation('ACYM_AND')).' ', $dates);
        }

        return $finalText;
    }
}
