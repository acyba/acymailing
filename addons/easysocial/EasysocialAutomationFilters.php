<?php

trait EasysocialAutomationFilters
{
    public function onAcymDeclareFilters(array &$filters): void
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_easysocialgroups(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_easysocialgroups($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_easysocialgroups(&$query, $options, $num)
    {
        $this->processConditionFilter_easysocialgroups($query, $options, $num);
    }

    public function onAcymProcessFilterCount_easysocialprofiles(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_easysocialprofiles($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_easysocialprofiles(&$query, $options, $num)
    {
        $this->processConditionFilter_easysocialprofiles($query, $options, $num);
    }

    public function onAcymProcessFilterCount_easysocialbadge(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_easysocialbadge($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_easysocialbadge(&$query, $options, $num)
    {
        $this->processConditionFilter_easysocialbadge($query, $options, $num);
    }

    public function onAcymProcessFilterCount_easysocialfield(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_easysocialfield($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_easysocialfield(&$query, $options, $num)
    {
        $this->processConditionFilter_easysocialfield($query, $options, $num);
    }

    public function onAcymProcessFilterCount_easysocialevent(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_easysocialevent($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_easysocialevent(&$query, $options, $num)
    {
        $this->processConditionFilter_easysocialevent($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
