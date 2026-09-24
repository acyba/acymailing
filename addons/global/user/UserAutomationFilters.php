<?php

trait UserAutomationFilters
{
    protected function initAutomationFilters(): void
    {
        $this->automationFilters = [
            'acy_group' => acym_translation('ACYM_GROUP'),
            'acy_cmsfield' => acym_translation('ACYM_ACCOUNT_USER_FIELD'),
        ];
    }

    /**
     * Same fields as the matching condition, only the field name differs
     */
    public function displayFilterOptions_acy_group(string $fieldName): void
    {
        $this->displayConditionOptions_acy_group($fieldName);
    }

    public function displayFilterOptions_acy_cmsfield(string $fieldName): void
    {
        $this->displayConditionOptions_acy_cmsfield($fieldName);
    }

    public function onAcymProcessFilter_acy_group(&$query, $options, $num)
    {
        $this->processAcyGroup($query, $options, $num);
    }

    public function onAcymProcessFilterCount_acy_group(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_acy_group($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilter_acy_cmsfield(&$query, $options, $num)
    {
        $this->processAcyCMSField($query, $options, $num);
    }

    public function onAcymProcessFilterCount_acy_cmsfield(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_acy_cmsfield($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymDeclareSummary_filters(&$automation)
    {
        $this->summaryGroup($automation);

        if (!empty($automation['acy_cmsfield'])) {
            $automation = acym_translationSprintf(
                'ACYM_FILTER_ACY_CMS_FIELD_SUMMARY',
                $automation['acy_cmsfield']['field'],
                $automation['acy_cmsfield']['operator'],
                $automation['acy_cmsfield']['value']
            );
        }
    }

    public function onAcymProcessFilter_birthday(&$query, $options, $num = null)
    {
        if ($options['plugin'] !== get_class($this) || ACYM_CMS !== 'joomla' || !ACYM_J37) return;

        $dateToCheck = $this->processDateToCheck($options);

        $query->join['j_fields'.$num] = '#__fields_values AS jf'.$num.' ON jf'.$num.'.item_id = user.cms_id AND jf'.$num.'.field_id = '.intval($options['field']);
        $query->where[] = 'user.cms_id != 0 ';
        $query->where[] = 'jf'.$num.'.value LIKE '.acym_escapeDB('%'.date_format($dateToCheck, '-m-d').'%');
    }
}
