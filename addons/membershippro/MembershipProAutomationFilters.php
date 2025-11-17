<?php

trait MembershipProAutomationFilters
{
    public function onAcymDeclareFilters(array &$filters): void
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilter_membershippro(&$query, $options, $num)
    {
        $this->processConditionFilter_membershippro($query, $options, $num);
    }

    public function onAcymProcessFilterCount_membershippro(&$query, $options, $num)
    {
        $this->processConditionFilter_membershippro($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
