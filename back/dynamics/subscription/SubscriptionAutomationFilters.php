<?php

use AcyMailing\Classes\ListClass;

trait SubscriptionAutomationFilters
{
    public function onAcymDeclareFilters(&$filters)
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

        $filters['acy_list'] = new stdClass();
        $filters['acy_list']->name = acym_translation('ACYM_ACYMAILING_LIST');
        $filters['acy_list']->option = '<div class="intext_select_automation cell">';
        $filters['acy_list']->option .= acym_select(
            $list['type'],
            'acym_action[filters][__numor__][__numand__][acy_list][action]',
            null,
            'class="intext_select_automation acym__select"'
        );
        $filters['acy_list']->option .= '</div>';
        $filters['acy_list']->option .= '<div class="intext_select_automation cell">';
        $filters['acy_list']->option .= acym_select(
            $list['lists'],
            'acym_action[filters][__numor__][__numand__][acy_list][list]',
            null,
            'class="intext_select_automation acym__select"'
        );
        $filters['acy_list']->option .= '</div>';
        $filters['acy_list']->option .= '<br><div class="cell grid-x grid-margin-x">';
        $filters['acy_list']->option .= acym_dateField('acym_action[filters][__numor__][__numand__][acy_list][date-min]');
        $filters['acy_list']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $filters['acy_list']->option .= '<div class="intext_select_automation">';
        $filters['acy_list']->option .= acym_select(
            $list['date'],
            'acym_action[filters][__numor__][__numand__][acy_list][date-type]',
            null,
            'class="intext_select_automation acym__select cell"'
        );
        $filters['acy_list']->option .= '</div>';
        $filters['acy_list']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 margin-left-1 margin-right-1"><</span>';
        $filters['acy_list']->option .= acym_dateField('acym_action[filters][__numor__][__numand__][acy_list][date-max]');
        $filters['acy_list']->option .= '</div>';

        if ($this->config->get('require_confirmation', '1') === '1') {
            $filters['unconfirmed'] = new stdClass();
            $filters['unconfirmed']->name = acym_translation('ACYM_UNCONFIRMED_SUBSCRIBERS');
            // The count results doesn't show up if there are no options
            $filters['unconfirmed']->option = '<input type="hidden" name="acym_action[filters][__numor__][__numand__][unconfirmed][countresults]" />';
        }
    }

    public function onAcymProcessFilter_unconfirmed(&$query, &$options, $num)
    {
        $query->where[] = 'user.confirmed = 0';
    }

    public function onAcymProcessFilterCount_unconfirmed(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_unconfirmed($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_acy_list(&$query, &$options, $num)
    {
        $this->_processConditionAcyLists($query, $options, $num);
    }

    public function onAcymProcessFilterCount_acy_list(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_acy_list($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymDeclareSummary_filters(&$automation)
    {
        $this->onAcymDeclareSummary_conditionsFilters($automation, 'ACYM_FILTER_ACY_LIST_SUMMARY', 'ACYM_SUBSCRIBED', 'ACYM_UNSUBSCRIBED', 'ACYM_NOT_SUBSCRIBED');
    }
}
