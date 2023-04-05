<?php

trait WooCommerceAutomationFilters
{
    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_woopurchased(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_woopurchased($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_woopurchased(&$query, $options, $num)
    {
        $this->processConditionFilter_woopurchased($query, $options, $num);
    }

    public function onAcymProcessFilterCount_wooreminder(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_wooreminder($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_wooreminder(&$query, $options, $num)
    {
        $this->processConditionFilter_wooreminder($query, $options, $num);
    }

    public function onAcymProcessFilterCount_woosubscription(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_woosubscription($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_woosubscription(&$query, $options, $num)
    {
        $this->processConditionFilter_woosubscription($query, $options, $num);
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }
}
