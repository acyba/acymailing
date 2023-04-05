<?php

trait VirtuemartAutomationFilters
{
    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilter_vmgroups(&$query, $options, $num)
    {
        $this->processConditionFilter_vmgroups($query, $options, $num);
    }

    public function onAcymProcessFilter_vmfield(&$query, $options, $num)
    {
        $this->processConditionFilter_vmfield($query, $options, $num);
    }

    public function onAcymProcessFilter_vmreminder(&$query, $options, $num)
    {
        $this->processConditionFilter_vmreminder($query, $options, $num);
    }

    public function onAcymProcessFilter_vmpurchased(&$query, $options, $num)
    {
        $this->processConditionFilter_vmpurchased($query, $options, $num);
    }

    public function onAcymProcessFilterCount_vmgroups(&$query, $options, $num)
    {
        $this->processConditionFilter_vmgroups($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilterCount_vmfield(&$query, $options, $num)
    {
        $this->processConditionFilter_vmfield($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilterCount_vmreminder(&$query, $options, $num)
    {
        $this->processConditionFilter_vmreminder($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilterCount_vmpurchased(&$query, $options, $num)
    {
        $this->processConditionFilter_vmpurchased($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
