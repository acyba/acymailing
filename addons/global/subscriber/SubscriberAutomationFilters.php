<?php

trait SubscriberAutomationFilters
{
    public function onAcymDeclareFilters(array &$filters): void
    {
        $this->filtersFromConditions($filters);
        parent::onAcymDeclareFilters($filters);
    }

    protected function initAutomationFilters(): void
    {
        $this->automationFilters['random'] = acym_translationSprintf('ACYM_RANDOMLY_SELECT_X_SUBSCRIBERS', 'X');
    }

    public function displayFilterOptions_random(string $fieldName): void
    {
        $numberInput = '<input type="number" class="intext_input_automation" style="width:60px" value="30" name="'.acym_escape($fieldName.'[number]').'" />';
        ?>
		<div class="cell">
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The field is built and escaped just above, and its position in the sentence depends on the language.
            echo acym_translationSprintf('ACYM_RANDOMLY_SELECT_X_SUBSCRIBERS', $numberInput);
            ?>
		</div>
        <?php
    }

    public function onAcymProcessFilter_acy_field(&$query, &$options, $num)
    {
        $this->processAcyField($query, $options, $num);
    }

    public function onAcymProcessFilter_random(&$query, &$options, $num)
    {
        $numberOfUsers = intval($options['number']);
        if (empty($numberOfUsers)) return;

        $query->limit = (string)$numberOfUsers;
        $query->orderBy = 'RAND()';
    }

    public function onAcymProcessFilterCount_acy_field(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_acy_field($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilterCount_random(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_random($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymDeclareSummary_filters(&$automation)
    {
        $this->onAcymDeclareSummary_conditionsFilters($automation, 'ACYM_FILTER_ACY_FIELD_SUMMARY');
    }
}
