<?php

use AcyMailing\Classes\SegmentClass;
use AcyMailing\Helpers\AutomationHelper;

trait SegmentAutomationFilters
{
    protected function initAutomationFilters(): void
    {
        $this->automationFilters['acy_segment'] = acym_translation('ACYM_ACYMAILING_SEGMENT');
    }

    public function displayFilterOptions_acy_segment(string $fieldName): void
    {
        $segmentClass = new SegmentClass();
        $segments = $segmentClass->getAll();
        $selectOptionSegment = [];
        foreach ($segments as $oneSegment) {
            $selectOptionSegment[] = acym_selectOption($oneSegment->id, $oneSegment->name);
        }
        ?>
		<div class="intext_select_automation cell">
            <?php acym_select(
                $selectOptionSegment,
                $fieldName.'[id]',
                null,
                ['class' => 'intext_select_automation acym__select'],
                'value',
                'text',
                null,
                false,
                true
            ); ?>
		</div>
        <?php
    }

    public function onAcymProcessFilterCount_acy_segment(&$query, &$options, &$num)
    {
        $this->onAcymProcessFilter_acy_segment($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_acy_segment(&$query, &$options, $num)
    {
        $segmentClass = new SegmentClass();
        $oneSegment = $segmentClass->getOneById($options['id']);

        $automationHelpers = [];
        if (!empty($oneSegment->filters)) {
            foreach ($oneSegment->filters as $or => $orValues) {
                if (empty($orValues)) continue;

                $automationHelpers[$or] = new AutomationHelper();
                foreach ($orValues as $and => $andValues) {
                    $and = intval($and);
                    foreach ($andValues as $filterName => $filterOptions) {
                        acym_trigger('onAcymProcessFilter_'.$filterName, [&$automationHelpers[$or], &$filterOptions, $and.'_'.$or]);
                    }
                }
            }
        }

        $whereClauses = [];
        foreach ($automationHelpers as $automationHelper) {
            if (!empty($automationHelper->where)) {
                $whereClauses[] = ' ('.implode(') AND (', $automationHelper->where).')';
            }
            if (!empty($automationHelper->join)) {
                $query->join = array_merge($query->join, $automationHelper->join);
            }
            if (!empty($automationHelper->leftjoin)) {
                $query->leftjoin = array_merge($query->leftjoin, $automationHelper->leftjoin);
            }
        }

        if (!empty($whereClauses)) {
            $query->where = array_merge($query->where, [implode(' OR ', $whereClauses)]);
        }
    }

    public function onAcymDeclareSummary_filters(&$automation)
    {
        if (empty($automation['acy_segment'])) return;
        $oneSegment = (new SegmentClass())->getOneById($automation['acy_segment']['id']);
        $automation = acym_translationSprintf('ACYM_FILTER_ACY_SEGMENT_SUMMARY', $oneSegment->name);
    }
}
