<?php

trait EventsManagerAutomationFilters
{
    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_eventsmanager(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_eventsmanager($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_eventsmanager(&$query, $options, $num)
    {
        $this->processConditionFilter_eventsmanager($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
