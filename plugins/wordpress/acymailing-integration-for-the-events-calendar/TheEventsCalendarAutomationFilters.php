<?php

trait TheEventsCalendarAutomationFilters
{
    public function onAcymDeclareFilters(array &$filters): void
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_eventscalendar(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_eventscalendar($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_eventscalendar(&$query, $options, $num)
    {
        $this->processConditionFilter_eventscalendar($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
