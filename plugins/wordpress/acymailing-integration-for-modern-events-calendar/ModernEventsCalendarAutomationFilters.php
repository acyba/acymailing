<?php

trait ModernEventsCalendarAutomationFilters
{
    public function onAcymDeclareFilters(&$filters)
    {
        if (!$this->fullInstalled) return;

        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_moderneventscalendar(&$query, $options, $num)
    {
        if (!$this->fullInstalled) return '';
        $this->onAcymProcessFilter_moderneventscalendar($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_moderneventscalendar(&$query, $options, $num)
    {
        $this->processConditionFilter_moderneventscalendar($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
