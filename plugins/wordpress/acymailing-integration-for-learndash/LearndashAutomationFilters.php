<?php

trait LearndashAutomationFilters
{
    public function onAcymDeclareFilters(array &$filters): void
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_learndash_group(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_learndash_group($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_learndash_group(&$query, $options, $num)
    {
        $this->processConditionFilter_learndash_group($query, $options, $num);
    }

    public function onAcymProcessFilterCount_learndash_course(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_learndash_course($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_learndash_course(&$query, $options, $num)
    {
        $this->processConditionFilter_learndash_course($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
