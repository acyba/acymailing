<?php

trait IcagendaAutomationFilters
{
    public function onAcymDeclareFilters(array &$filters): void
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_icagenda(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_icagenda($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_icagenda(&$query, $options, $num)
    {
        $this->processConditionFilter_icagenda($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
