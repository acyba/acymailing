<?php

use AcyMailing\Types\OperatorType;
use AcyMailing\Types\OperatorinType;

trait UserAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        $allGroups = acym_getGroups();
        $groups = [];
        foreach ($allGroups as $group) {
            $groups[$group->id] = $group->text;
        }
        $operatorIn = new OperatorinType();

        $conditions['user']['acy_group'] = new stdClass();
        $conditions['user']['acy_group']->name = acym_translation('ACYM_GROUP');
        $conditions['user']['acy_group']->option = '<div class="intext_select_automation cell">';
        $conditions['user']['acy_group']->option .= $operatorIn->display('acym_condition[conditions][__numor__][__numand__][acy_group][in]');
        $conditions['user']['acy_group']->option .= '</div>';
        $conditions['user']['acy_group']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['acy_group']->option .= acym_select(
            $groups,
            'acym_condition[conditions][__numor__][__numand__][acy_group][group]',
            null,
            ['class' => 'acym__select']
        );
        $conditions['user']['acy_group']->option .= '</div>';

        if (ACYM_CMS == 'joomla') {
            $conditions['user']['acy_group']->option .= '<div class="cell grid-x medium-3">';
            $conditions['user']['acy_group']->option .= acym_switch(
                'acym_condition[conditions][__numor__][__numand__][acy_group][subgroup]',
                1,
                acym_translation('ACYM_INCLUDE_SUB_GROUPS')
            );
            $conditions['user']['acy_group']->option .= '</div>';
        }


        $cmsFields = [];
        foreach (acym_getColumns('users', false) as $key => $column) {
            $cmsFields[$column] = $column;
        }

        // Handle custom fields
        if (ACYM_CMS == 'joomla' && ACYM_J37) {
            $query = 'SELECT id, title 
						FROM #__fields 
						WHERE context = "com_users.user"
							AND state = 1
							AND type IN ("calendar", "checkboxes", "color", "integer", "list", "radio", "sql", "text", "url")
						ORDER BY title ASC';
            $customFields = acym_loadObjectList($query);
            foreach ($customFields as $oneCF) {
                $cmsFields['cf_'.$oneCF->id] = $oneCF->title;
            }
        }
        $excluded = ['password', 'params', 'activation', 'lastResetTime', 'resetCount', 'optKey', 'otep', 'requireReset', 'user_pass', 'user_activation_key'];
        foreach ($excluded as $oneExcluded) {
            unset($cmsFields[$oneExcluded]);
        }

        $operator = new OperatorType();

        $conditions['user']['acy_cmsfield'] = new stdClass();
        $conditions['user']['acy_cmsfield']->name = acym_translation('ACYM_ACCOUNT_USER_FIELD');
        $conditions['user']['acy_cmsfield']->option = '<div class="intext_select_automation cell">';
        $conditions['user']['acy_cmsfield']->option .= acym_select(
            $cmsFields,
            'acym_condition[conditions][__numor__][__numand__][acy_cmsfield][field]',
            null,
            ['class' => 'acym__select']
        );
        $conditions['user']['acy_cmsfield']->option .= '</div>';
        $conditions['user']['acy_cmsfield']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['acy_cmsfield']->option .= $operator->display('acym_condition[conditions][__numor__][__numand__][acy_cmsfield][operator]');
        $conditions['user']['acy_cmsfield']->option .= '</div>';
        $conditions['user']['acy_cmsfield']->option .= '<input class="intext_input_automation cell" type="text" name="acym_condition[conditions][__numor__][__numand__][acy_cmsfield][value]">';

        $conditions['classic']['acy_totaluser'] = new stdClass();
        $conditions['classic']['acy_totaluser']->name = acym_translation('ACYM_NUMBER_OF_SUBSCRIBERS');
        $conditions['classic']['acy_totaluser']->option = '<div class="cell shrink acym__automation__inner__text">'.acym_translation('ACYM_THERE_IS').'</div>';
        $conditions['classic']['acy_totaluser']->option .= '<div class="intext_select_automation cell">';
        $conditions['classic']['acy_totaluser']->option .= acym_select(
            ['=' => acym_translation('ACYM_EXACTLY'), '>' => acym_translation('ACYM_MORE_THAN'), '<' => acym_translation('ACYM_LESS_THAN')],
            'acym_condition[conditions][__numor__][__numand__][acy_totaluser][operator]',
            null,
            ['class' => 'intext_select_automation acym__select']
        );
        $conditions['classic']['acy_totaluser']->option .= '</div>';
        $conditions['classic']['acy_totaluser']->option .= '<input type="number" min="0" class="intext_input_automation cell" name="acym_condition[conditions][__numor__][__numand__][acy_totaluser][number]">';
        $conditions['classic']['acy_totaluser']->option .= '<div class="cell shrink acym__automation__inner__text">'.acym_translation('ACYM_ACYMAILING_USERS').'</div>';

        $conditions['both']['acy_toss'] = new stdClass();
        $conditions['both']['acy_toss']->name = acym_translation('ACYM_TOSS');
        $conditions['both']['acy_toss']->option = '<input type="hidden" name="acym_condition[conditions][__numor__][__numand__][acy_toss][toss]" value="true"><div class="acym__automation__inner__text">'.acym_translation(
                'ACYM_TOSS_DESC'
            ).'</div>';
    }

    public function onAcymProcessCondition_acy_toss(&$query, $option, $num, &$conditionNotValid)
    {
        if (!mt_rand(0, 1)) $conditionNotValid++;
    }

    public function onAcymProcessCondition_acy_totaluser(&$query, $option, $num, &$conditionNotValid)
    {
        $numberUsers = $query->count();
        $res = false;

        switch ($option['operator']) {
            case '=' :
                $res = $numberUsers == $option['number'];
                break;
            case '>' :
                $res = $numberUsers > $option['number'];
                break;
            case '<' :
                $res = $numberUsers < $option['number'];
                break;
        }

        if (!$res) $conditionNotValid++;
    }

    public function onAcymProcessCondition_acy_group(&$query, $options, $num, &$conditionNotValid)
    {
        $affectedRows = $this->_processAcyGroup($query, $options, $num);
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function _processAcyGroup(&$query, $options, $num)
    {
        if (ACYM_CMS == 'joomla') {
            $operator = (empty($options['in']) || $options['in'] == 'in') ? 'IS NOT NULL AND cmsuser'.$num.'.user_id != 0' : "IS NULL";

            if (empty($options['subgroup'])) {
                $value = ' = '.intval($options['group']);
            } else {
                $lftrgt = acym_loadObject('SELECT lft, rgt FROM #__usergroups WHERE id = '.intval($options['group']));
                $allGroups = acym_loadResultArray('SELECT id FROM #__usergroups WHERE lft > '.intval($lftrgt->lft).' AND rgt < '.intval($lftrgt->rgt));
                array_unshift($allGroups, $options['group']);
                $value = ' IN ('.implode(', ', $allGroups).')';
            }

            $query->leftjoin['cmsuser'.$num] = "#__user_usergroup_map AS cmsuser$num ON cmsuser$num.user_id = user.cms_id AND cmsuser$num.group_id".$value;
            $query->where[] = "cmsuser$num.user_id ".$operator;
        } else {
            $operator = (empty($options['in']) || $options['in'] == 'in') ? 'IS NOT NULL AND cmsuser'.$num.'.user_id != 0' : "IS NULL";

            $query->leftjoin['cmsuser'.$num] = '#__usermeta AS cmsuser'.$num.' ON cmsuser'.$num.'.user_id = user.cms_id AND cmsuser'.$num.'.meta_key = "#__capabilities" AND cmsuser'.$num.'.meta_value LIKE '.acym_escapeDB(
                    '%'.strlen($options['group']).':"'.$options['group'].'"%'
                );
            $query->where[] = 'cmsuser'.$num.'.user_id '.$operator;
        }

        return $query->count();
    }

    public function onAcymProcessCondition_acy_cmsfield(&$query, $options, $num, &$conditionNotValid)
    {
        $affectedRows = $this->_processAcyCMSField($query, $options, $num);
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function _processAcyCMSField(&$query, $options, $num)
    {
        if (empty($options['field'])) {
            return;
        }

        // Handle custom fields
        if (strpos($options['field'], 'cf_') !== false) {
            $cfId = substr($options['field'], 3);
            $query->leftjoin['cmsuserfields'.$num] = '#__fields_values AS cmsuserfields'.$num.' ON cmsuserfields'.$num.'.item_id = user.cms_id AND cmsuserfields'.$num.'.field_id = '.intval(
                    $cfId
                );
            $query->where[] = $query->convertQuery('cmsuserfields'.$num, 'value', $options['operator'], $options['value'], '');
        } else {
            // Handle normal fields
            $type = '';
            $query->leftjoin['cmsuser'.$num] = '#__users AS cmsuser'.$num.' ON cmsuser'.$num.'.id = user.cms_id';

            if (in_array($options['field'], ['registerDate', 'lastvisitDate', 'user_registered'])) {
                $type = 'datetime';
                $options['value'] = acym_replaceDate($options['value']);

                if (!is_numeric($options['value']) && strtotime($options['value']) !== false) {
                    $options['value'] = strtotime($options['value']);
                }
                if (is_numeric($options['value'])) {
                    $options['value'] = date('Y-m-d H:i:s', $options['value']);
                }
            }

            $query->where[] = $query->convertQuery('cmsuser'.$num, $options['field'], $options['operator'], $options['value'], $type);
        }

        return $query->count();
    }

    public function onAcymDeclareSummary_conditions(&$automation)
    {
        $this->_summaryGroup($automation);

        if (!empty($automation['acy_cmsfield'])) {
            $automation = acym_translationSprintf(
                'ACYM_CONDITION_ACY_CMS_FIELD_SUMMARY',
                $automation['acy_cmsfield']['field'],
                $automation['acy_cmsfield']['operator'],
                $automation['acy_cmsfield']['value']
            );
        }

        if (!empty($automation['acy_totaluser'])) {
            $operators = ['=' => acym_translation('ACYM_EXACTLY'), '>' => acym_translation('ACYM_MORE_THAN'), '<' => acym_translation('ACYM_LESS_THAN')];
            $automation = acym_translation('ACYM_THERE_IS').' '.acym_strtolower(
                    $operators[$automation['acy_totaluser']['operator']]
                ).' '.$automation['acy_totaluser']['number'].' '.acym_translation('ACYM_ACYMAILING_USERS').' ';
        }

        if (!empty($automation['acy_toss'])) {
            $automation = acym_translation('ACYM_TOSS_DESC');
        }
    }

    private function _summaryGroup(&$automation)
    {
        if (empty($automation['acy_group'])) return;

        if ('joomla' === ACYM_CMS) {
            $allGroups = acym_getGroups();
            $groups = [];
            foreach ($allGroups as $group) {
                if ($automation['acy_group']['group'] == $group->id) $automation['acy_group']['group'] = $group->text;
                $groups[$group->id] = $group->text;
            }
        } else {
            $groupKey = 'ACYM_'.strtoupper($automation['acy_group']['group']);
            if (acym_translationExists($groupKey)) {
                $automation['acy_group']['group'] = $groupKey;
            }
        }

        $finalText = acym_translationSprintf(
            'ACYM_FILTER_ACY_GROUP_SUMMARY',
            acym_translation($automation['acy_group']['in'] == 'in' ? 'ACYM_IN' : 'ACYM_NOT_IN'),
            acym_translation($automation['acy_group']['group'])
        );
        if ('joomla' === ACYM_CMS) {
            $finalText .= $automation['acy_group']['subgroup'] == 1 ? '' : ' '.acym_translation('ACYM_FILTER_ACY_GROUP_SUBGROUP_SUMMARY');
        }

        $automation = $finalText;
    }
}
