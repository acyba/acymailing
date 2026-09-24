<?php

trait VirtuemartAutomationTriggers
{
    protected function initAutomationTriggers(): void
    {
        $this->automationTriggers['user']['vmorder'] = acym_translationSprintf(
            'ACYM_COMBINED_TRANSLATIONS',
            'VirtueMart',
            acym_translation('ACYM_WHEN_ORDER')
        );
    }

    public function displayTriggerOptions_vmorder(string $fieldName): void
    {
        echo '<input type="hidden" name="'.acym_escape($fieldName.'[]').'" value="">';
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        if (empty($data['userId'])) return;

        $triggers = $step->triggers;

        if (!empty($triggers['vmorder'])) {
            $execute = true;
        }
    }

    public function onAcymDeclareSummary_triggers(object $automation): void
    {
        if (!empty($automation->triggers['vmorder'])) {
            $automation->triggers['vmorder'] = acym_translation('ACYM_WHEN_ORDER');
        }
    }
}
