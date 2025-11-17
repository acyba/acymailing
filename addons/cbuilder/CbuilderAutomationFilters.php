<?php

trait CbuilderAutomationFilters
{
    public function onAcymDeclareFilters(array &$filters): void
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_cbfield(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_cbfield($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_cbfield(&$query, $options, $num)
    {
        $this->processConditionFilter_cbfield($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
