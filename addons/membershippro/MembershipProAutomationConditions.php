<?php

use AcyMailing\Types\OperatorInType;

trait MembershipProAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        if (!$this->installed) {
            return;
        }

        $plans = acym_loadObjectList('SELECT id AS value, title AS text FROM `#__osmembership_plans` ORDER BY `title` ASC');
        if (empty($plans)) {
            return;
        }

        acym_loadLanguageFile('com_osmembership', JPATH_SITE);

        $payments = acym_loadObjectList('SELECT name AS value, title AS text FROM `#__osmembership_plugins` ORDER BY `title` ASC');

        $operatorIn = new OperatorInType();
        $conditions['user']['membershippro'] = new stdClass();
        $conditions['user']['membershippro']->name = 'Membership Pro';
        $conditions['user']['membershippro']->option = '<div class="cell intext_select_automation">';
        $conditions['user']['membershippro']->option .= $operatorIn->display('acym_condition[conditions][__numor__][__numand__][membershippro][type]');
        $conditions['user']['membershippro']->option .= '</div>';

        $conditions['user']['membershippro']->option .= '<div class="cell intext_select_automation">';
        $conditions['user']['membershippro']->option .= acym_selectMultiple(
            $plans,
            'acym_condition[conditions][__numor__][__numand__][membershippro][plans]',
            [],
            [
                'class' => 'acym__select',
                'data-placeholder' => acym_translation('ACYM_ANY_PLAN'),
            ]
        );
        $conditions['user']['membershippro']->option .= '</div>';

        $conditions['user']['membershippro']->option .= '<div class="cell intext_select_automation">';
        $conditions['user']['membershippro']->option .= acym_selectMultiple(
            $payments,
            'acym_condition[conditions][__numor__][__numand__][membershippro][payments]',
            [],
            [
                'class' => 'acym__select',
                'data-placeholder' => acym_translation('ACYM_ANY_PAYMENT_METHOD'),
            ]
        );
        $conditions['user']['membershippro']->option .= '</div>';

        $conditions['user']['membershippro']->option .= '<div class="cell intext_select_automation">';
        $conditions['user']['membershippro']->option .= acym_selectMultiple(
            [
                '0' => acym_translation('OSM_PENDING'),
                '1' => acym_translation('OSM_ACTIVE'),
                '2' => acym_translation('OSM_EXPIRED'),
                '3' => acym_translation('OSM_CANCELLED_PENDING'),
                '4' => acym_translation('OSM_CANCELLED_REFUNDED'),
            ],
            'acym_condition[conditions][__numor__][__numand__][membershippro][statuses]',
            [],
            [
                'class' => 'acym__select',
                'data-placeholder' => acym_translation('ACYM_STATUS'),
            ]
        );
        $conditions['user']['membershippro']->option .= '</div>';

        $conditions['user']['membershippro']->option .= '<div class="cell grid-x grid-margin-x margin-top-1 margin-left-0">';
        $conditions['user']['membershippro']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][membershippro][create_date_min]', '', 'cell shrink');
        $conditions['user']['membershippro']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['membershippro']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_DATE_CREATED').'</span>';
        $conditions['user']['membershippro']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['membershippro']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][membershippro][create_date_max]', '', 'cell shrink');
        $conditions['user']['membershippro']->option .= '</div>';

        $conditions['user']['membershippro']->option .= '<div class="cell grid-x grid-margin-x margin-top-1 margin-left-0">';
        $conditions['user']['membershippro']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][membershippro][expiry_date_min]', '', 'cell shrink');
        $conditions['user']['membershippro']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['membershippro']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_EXPIRATION_DATE').'</span>';
        $conditions['user']['membershippro']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['membershippro']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][membershippro][expiry_date_max]', '', 'cell shrink');
        $conditions['user']['membershippro']->option .= '</div>';
    }

    public function onAcymDeclareConditionsScenario(&$conditions)
    {
        $this->onAcymDeclareConditions($conditions);
    }

    public function onAcymProcessCondition_membershippro(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_membershippro($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_membershippro(&$query, $options, $num)
    {
        $conditions = ['user.cms_id > 0'];

        if (!empty($options['plans'])) {
            acym_arrayToInteger($options['plans']);
            $conditions[] = 'osmembership'.$num.'.plan_id IN ('.implode(', ', $options['plans']).')';
        }

        if (!empty($options['payments'])) {
            $options['payments'] = array_map('acym_escapeDB', $options['payments']);
            $conditions[] = 'osmembership'.$num.'.payment_method IN ('.implode(', ', $options['payments']).')';
        }

        if (!empty($options['statuses'])) {
            acym_arrayToInteger($options['statuses']);
            $conditions[] = 'osmembership'.$num.'.published IN ('.implode(', ', $options['statuses']).')';
        }

        $dateOptions = ['create_date_min', 'create_date_max', 'expiry_date_min', 'expiry_date_max'];
        foreach ($dateOptions as $oneField) {
            if (empty($options[$oneField])) continue;
            $options[$oneField] = acym_replaceDate($options[$oneField]);
            if (is_numeric($options[$oneField])) {
                $options[$oneField] = acym_date($options[$oneField], 'Y-m-d H:i:s', false);
            }
        }

        if (!empty($options['create_date_min'])) $conditions[] = 'osmembership'.$num.'.from_date > '.acym_escapeDB($options['create_date_min']);
        if (!empty($options['create_date_max'])) $conditions[] = 'osmembership'.$num.'.from_date < '.acym_escapeDB($options['create_date_max']);
        if (!empty($options['expiry_date_min'])) $conditions[] = 'osmembership'.$num.'.to_date > '.acym_escapeDB($options['expiry_date_min']);
        if (!empty($options['expiry_date_max'])) $conditions[] = 'osmembership'.$num.'.to_date < '.acym_escapeDB($options['expiry_date_max']);

        $join = '#__osmembership_subscribers AS osmembership'.$num.' ON (user.cms_id = osmembership'.$num.'.user_id OR user.email = osmembership'.$num.'.email)';
        if ($options['type'] === 'in') {
            $query->join['osmembership'.$num] = $join;
            $query->where = array_merge($query->where, $conditions);
        } else {
            $query->leftjoin['osmembership'.$num] = $join.' AND '.implode(' AND ', $conditions);
            $query->where[] = 'osmembership'.$num.'.user_id IS NULL';
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        if (empty($automationCondition['membershippro'])) {
            return;
        }

        $finalText = acym_translation($automationCondition['membershippro']['type'] === 'in' ? 'ACYM_IN' : 'ACYM_NOT_IN');

        if (empty($automationCondition['membershippro']['plans'])) {
            $finalText .= ' '.acym_translation('ACYM_ANY_PLAN');
        } else {
            acym_arrayToInteger($automationCondition['membershippro']['plans']);
            $plans = acym_loadResultArray('SELECT title FROM `#__osmembership_plans` WHERE id IN ('.implode(', ', $automationCondition['membershippro']['plans']).')');
            $finalText .= ' '.implode(', ', $plans);
        }

        if (empty($automationCondition['membershippro']['payments'])) {
            $finalText .= ' '.acym_translation('ACYM_ANY_PAYMENT_METHOD');
        } else {
            $automationCondition['membershippro']['payments'] = array_map('acym_escapeDB', $automationCondition['membershippro']['payments']);
            $payments = acym_loadResultArray('SELECT title FROM `#__osmembership_plugins` WHERE name IN ('.implode(', ', $automationCondition['membershippro']['payments']).')');
            $finalText .= ' - '.implode(', ', $payments);
        }

        acym_loadLanguageFile('com_osmembership', JPATH_SITE);

        $statuses = [
            '0' => acym_translation('OSM_PENDING'),
            '1' => acym_translation('OSM_ACTIVE'),
            '2' => acym_translation('OSM_EXPIRED'),
            '3' => acym_translation('OSM_CANCELLED_PENDING'),
            '4' => acym_translation('OSM_CANCELLED_REFUNDED'),
        ];

        if (!empty($automationCondition['membershippro']['statuses'])) {
            acym_arrayToInteger($automationCondition['membershippro']['statuses']);
            $statusesText = [];
            foreach ($automationCondition['membershippro']['statuses'] as $oneStatusId) {
                $statusesText[] = $statuses[$oneStatusId];
            }
            $finalText .= '<br/>'.implode(', ', $statusesText);
        }

        $dates = [];
        if (!empty($automationCondition['membershippro']['create_date_min'])) {
            $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['membershippro']['create_date_min'], true);
        }

        if (!empty($automationCondition['membershippro']['create_date_max'])) {
            $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['membershippro']['create_date_max'], true);
        }

        if (!empty($dates)) {
            $finalText .= '<br />'.acym_translation('ACYM_SUBSCRIPTION_DATE').': '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
        }

        $dates = [];
        if (!empty($automationCondition['membershippro']['expiry_date_min'])) {
            $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['membershippro']['expiry_date_min'], true);
        }

        if (!empty($automationCondition['membershippro']['expiry_date_max'])) {
            $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['membershippro']['expiry_date_max'], true);
        }

        if (!empty($dates)) {
            $finalText .= '<br />'.acym_translation('ACYM_END_DATE').': '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
        }

        $automationCondition = $finalText;
    }
}
