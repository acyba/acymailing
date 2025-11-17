<?php

trait EventBookingAutomationFilters
{
    public function onAcymDeclareFilters(array &$filters): void
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_ebregistration(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_ebregistration($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_ebregistration(&$query, $options, $num)
    {
        $this->processConditionFilter_ebregistration($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
