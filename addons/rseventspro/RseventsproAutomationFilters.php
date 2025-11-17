<?php

trait RseventsproAutomationFilters
{
    public function onAcymDeclareFilters(array &$filters): void
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_rseventspro(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_rseventspro($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_rseventspro(&$query, $options, $num)
    {
        $this->processConditionFilter_rseventspro($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
