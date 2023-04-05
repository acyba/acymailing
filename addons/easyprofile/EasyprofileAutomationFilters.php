<?php

trait EasyprofileAutomationFilters
{
    public function onAcymDeclareFilters(&$filters)
    {
        return $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_epfield(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_epfield($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_epfield(&$query, $options, $num)
    {
        $this->processConditionFilter_epfield($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
