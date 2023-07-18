<?php

trait SubscriberAutomationFilters
{
    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);

        $filters['random'] = new stdClass();
        $filters['random']->name = acym_translationSprintf('ACYM_RANDOMLY_SELECT_X_SUBSCRIBERS', 'X');
        $filters['random']->option = '<div class="cell">';
        $filters['random']->option .= acym_translationSprintf(
            'ACYM_RANDOMLY_SELECT_X_SUBSCRIBERS',
            '<input type="number" class="intext_input_automation" style="width:60px" value="30" name="acym_action[filters][__numor__][__numand__][random][number]" />'
        );
        $filters['random']->option .= '</div>';
    }

    public function onAcymProcessFilter_acy_field(&$query, &$options, $num)
    {
        $this->_processAcyField($query, $options, $num);
    }

    public function onAcymProcessFilter_random(&$query, &$options, $num)
    {
        $numberOfUsers = intval($options['number']);
        if (empty($numberOfUsers)) return;

        $query->limit = $numberOfUsers;
        $query->orderBy = 'RAND()';
    }

    public function onAcymProcessFilterCount_acy_field(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_acy_field($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilterCount_random(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_random($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymDeclareSummary_filters(&$automation)
    {
        $this->onAcymDeclareSummary_conditionsFilters($automation, 'ACYM_FILTER_ACY_FIELD_SUMMARY');
    }
}
