<?php

trait JeventsAutomationFilters
{
    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_jeventsregistration(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_jeventsregistration($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_jeventsregistration(&$query, $options, $num)
    {
        $this->processConditionFilter_jeventsregistration($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
