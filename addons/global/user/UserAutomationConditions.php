<?php

use AcyMailing\Helpers\AutomationHelper;
use AcyMailing\Types\OperatorInType;
use AcyMailing\Types\OperatorType;

trait UserAutomationConditions
{
    protected function initAutomationConditions(): void
    {
        $this->automationConditions = [
            'user' => [
                'acy_group' => acym_translation('ACYM_GROUP'),
                'acy_cmsfield' => acym_translation('ACYM_ACCOUNT_USER_FIELD'),
            ],
            'classic' => [
                'acy_totaluser' => acym_translation('ACYM_NUMBER_OF_SUBSCRIBERS'),
            ],
            'both' => [
                'acy_toss' => acym_translation('ACYM_TOSS'),
            ],
        ];
    }

    public function displayConditionOptions_acy_group(string $fieldName): void
    {
        $allGroups = acym_getGroups();
        $groups = ['' => acym_translation('ACYM_NO_GROUP')];
        foreach ($allGroups as $group) {
            $groups[$group->id] = $group->text;
        }
        $operatorIn = new OperatorInType();
        ?>
		<div class="intext_select_automation cell">
            <?php $operatorIn->display($fieldName.'[in]', '', 'acym__select', true); ?>
		</div>
		<div class="intext_select_automation cell">
            <?php acym_select(
                $groups,
                $fieldName.'[group]',
                null,
                ['class' => 'acym__select'],
                'value',
                'text',
                null,
                false,
                true
            ); ?>
		</div>
        <?php if (ACYM_CMS === 'joomla') { ?>
			<div class="cell grid-x medium-3">
                <?php acym_switch([
                    'name' => $fieldName.'[subgroup]',
                    'value' => 1,
                    'label' => acym_translation('ACYM_INCLUDE_SUB_GROUPS'),
                    // One switch per row of the form, the JS replaces the placeholder to keep the ids unique
                    'idPrefix' => '__numand__',
                ]); ?>
			</div>
        <?php }
    }

    private function getCmsFieldsForConditions(): array
    {
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

        return $cmsFields;
    }

    public function displayConditionOptions_acy_cmsfield(string $fieldName): void
    {
        $operator = new OperatorType();
        ?>
		<div class="intext_select_automation cell">
            <?php acym_select(
                $this->getCmsFieldsForConditions(),
                $fieldName.'[field]',
                null,
                ['class' => 'acym__select'],
                'value',
                'text',
                null,
                false,
                true
            ); ?>
		</div>
		<div class="intext_select_automation cell">
            <?php $operator->display($fieldName.'[operator]', '', 'acym__select', true); ?>
		</div>
		<input class="intext_input_automation cell" type="text" name="<?php echo acym_escape($fieldName.'[value]'); ?>">
        <?php
    }

    public function displayConditionOptions_acy_totaluser(string $fieldName): void
    {
        ?>
		<div class="cell shrink acym__automation__inner__text"><?php echo acym_escapeHtml(acym_translation('ACYM_THERE_IS')); ?></div>
		<div class="intext_select_automation cell">
            <?php acym_select(
                [
                    '=' => acym_translation('ACYM_EXACTLY'),
                    '>' => acym_translation('ACYM_MORE_THAN'),
                    '<' => acym_translation('ACYM_LESS_THAN'),
                ],
                $fieldName.'[operator]',
                null,
                ['class' => 'intext_select_automation acym__select'],
                'value',
                'text',
                null,
                false,
                true
            ); ?>
		</div>
		<input type="number" min="0" class="intext_input_automation cell" name="<?php echo acym_escape($fieldName.'[number]'); ?>">
		<div class="cell shrink acym__automation__inner__text"><?php echo acym_escapeHtml(acym_translation('ACYM_ACYMAILING_USERS')); ?></div>
        <?php
    }

    public function displayConditionOptions_acy_toss(string $fieldName): void
    {
        ?>
		<input type="hidden" name="<?php echo acym_escape($fieldName.'[toss]'); ?>" value="true">
		<div class="acym__automation__inner__text"><?php echo acym_escapeHtml(acym_translation('ACYM_TOSS_DESC')); ?></div>
        <?php
    }

    public function onAcymProcessCondition_acy_toss(&$query, $option, $num, &$conditionNotValid)
    {
        if (!acym_rand(0, 1)) $conditionNotValid++;
    }

    public function onAcymProcessCondition_acy_totaluser(&$query, $option, $num, &$conditionNotValid)
    {
        $numberUsers = $query->count();
        $res = false;

        switch ($option['operator']) {
            case '=':
                $res = $numberUsers == $option['number'];
                break;
            case '>':
                $res = $numberUsers > $option['number'];
                break;
            case '<':
                $res = $numberUsers < $option['number'];
                break;
        }

        if (!$res) $conditionNotValid++;
    }

    public function onAcymProcessCondition_acy_group(&$query, $options, $num, &$conditionNotValid)
    {
        $affectedRows = $this->processAcyGroup($query, $options, $num);
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processAcyGroup(&$query, $options, $num)
    {
        if (empty($options['group'])) {
            if (ACYM_CMS === 'joomla') {
                $operator = (empty($options['in']) || $options['in'] === 'in') ? 'IS NULL' : 'IS NOT NULL AND cmsuser'.$num.'.user_id != 0';
                $query->leftjoin['cmsuser'.$num] = '#__user_usergroup_map AS cmsuser'.$num.' 
                ON cmsuser'.$num.'.user_id = user.cms_id ';
            } else {
                $operator = (empty($options['in']) || $options['in'] === 'in') ? 'IS NOT NULL AND cmsuser'.$num.'.user_id != 0' : 'IS NULL AND user.cms_id != 0';
                $query->leftjoin['cmsuser'.$num] = '#__usermeta AS cmsuser'.$num.' 
                ON cmsuser'.$num.'.user_id = user.cms_id 
                AND cmsuser'.$num.'.meta_key = "#__capabilities" 
                AND cmsuser'.$num.'.meta_value = "a:0:{}"';
            }
        } else {
            $operator = (empty($options['in']) || $options['in'] === 'in') ? 'IS NOT NULL AND cmsuser'.$num.'.user_id != 0' : 'IS NULL';

            if (ACYM_CMS === 'joomla') {
                if (empty($options['subgroup'])) {
                    $value = ' = '.intval($options['group']);
                } else {
                    $lftrgt = acym_loadObject('SELECT lft, rgt FROM #__usergroups WHERE id = '.intval($options['group']));
                    $allSubGroups = acym_loadResultArray('SELECT id FROM #__usergroups WHERE lft > '.intval($lftrgt->lft).' AND rgt < '.intval($lftrgt->rgt));
                    array_unshift($allSubGroups, intval($options['group']));
                    $value = ' IN ('.implode(', ', $allSubGroups).')';
                }

                $query->leftjoin['cmsuser'.$num] = '#__user_usergroup_map AS cmsuser'.$num.' 
                ON cmsuser'.$num.'.user_id = user.cms_id 
                AND cmsuser'.$num.'.group_id'.$value;
            } else {
                $query->leftjoin['cmsuser'.$num] = '#__usermeta AS cmsuser'.$num.' 
                ON cmsuser'.$num.'.user_id = user.cms_id 
                AND cmsuser'.$num.'.meta_key = "#__capabilities" 
                AND cmsuser'.$num.'.meta_value LIKE '.acym_escapeDB('%'.strlen($options['group']).':"'.acym_secureDBColumn($options['group']).'"%');
            }
        }

        $query->where[] = 'cmsuser'.$num.'.user_id '.$operator;

        return $query->count();
    }

    public function onAcymProcessCondition_acy_cmsfield(&$query, $options, $num, &$conditionNotValid)
    {
        $affectedRows = $this->processAcyCMSField($query, $options, $num);
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processAcyCMSField(&$query, $options, $num)
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
            $query->where[] = $query->convertQuery('cmsuserfields'.$num, 'value', $options['operator'], $options['value']);
        } else {
            // Handle normal fields
            $type = '';
            $query->leftjoin['cmsuser'.$num] = '#__users AS cmsuser'.$num.' ON cmsuser'.$num.'.id = user.cms_id';

            if (in_array($options['field'], ['registerDate', 'lastvisitDate', 'user_registered'])) {
                $type = AutomationHelper::TYPE_DATETIME;
                $options['value'] = acym_replaceDate($options['value']);

                if (!is_numeric($options['value']) && strtotime($options['value']) !== false) {
                    $options['value'] = strtotime($options['value']);
                }
                if (is_numeric($options['value'])) {
                    $options['value'] = gmdate('Y-m-d H:i:s', $options['value']);
                }
            }

            $query->where[] = $query->convertQuery(
                'cmsuser'.$num,
                $options['field'],
                $options['operator'],
                $options['value'],
                $type
            );
        }

        return $query->count();
    }

    public function onAcymDeclareSummary_conditions(&$automation)
    {
        $this->summaryGroup($automation);

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

    private function summaryGroup(&$automation)
    {
        if (empty($automation['acy_group'])) return;

        if ('joomla' === ACYM_CMS) {
            $allGroups = acym_getGroups();
            $groups = [];
            foreach ($allGroups as $group) {
                if ($automation['acy_group']['group'] == $group->id) {
                    $automation['acy_group']['group'] = $group->text;
                }
                $groups[$group->id] = $group->text;
            }
        } else {
            $groupKey = 'ACYM_'.strtoupper($automation['acy_group']['group']);
            if (acym_translationExists($groupKey)) {
                $automation['acy_group']['group'] = $groupKey;
            }
        }

        if (empty($automation['acy_group']['group'])) {
            $automation['acy_group']['group'] = acym_translation('ACYM_NO_GROUP');
        }

        $finalText = acym_translationSprintf(
            'ACYM_FILTER_ACY_GROUP_SUMMARY',
            acym_translation($automation['acy_group']['in'] == 'in' ? 'ACYM_IN' : 'ACYM_NOT_IN'),
            acym_translation($automation['acy_group']['group'])
        );
        if ('joomla' === ACYM_CMS) {
            $finalText .= $automation['acy_group']['subgroup'] == 1 ? ' '.acym_translation('ACYM_FILTER_ACY_GROUP_SUBGROUP_SUMMARY') : '';
        }

        $automation = $finalText;
    }
}
