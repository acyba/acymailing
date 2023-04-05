<?php

trait HikashopAutomationFilters
{
    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_hikapurchased(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_hikapurchased($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_hikapurchased(&$query, $options, $num)
    {
        $this->processConditionFilter_hikapurchased($query, $options, $num);
    }

    public function onAcymProcessFilterCount_hikareminder(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_hikareminder($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_hikareminder(&$query, $options, $num)
    {
        $this->processConditionFilter_hikareminder($query, $options, $num);
    }

    public function onAcymProcessFilterCount_hikawishlist(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_hikawishlist($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_hikawishlist(&$query, $options, $num)
    {
        $this->processConditionFilter_hikawishlist($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
