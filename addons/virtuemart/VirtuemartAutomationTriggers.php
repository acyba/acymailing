<?php

trait VirtuemartAutomationTriggers
{
    public function onAcymDeclareTriggers(&$triggers, &$defaultValues)
    {
        $triggers['user']['vmorder'] = new stdClass();
        $triggers['user']['vmorder']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'VirtueMart', acym_translation('ACYM_WHEN_ORDER'));
        $triggers['user']['vmorder']->option = '<input type="hidden" name="[triggers][user][vmorder][]" value="">';
    }

    public function onAcymDeclareTriggersScenario(&$triggers, &$defaultValues)
    {
        $this->onAcymDeclareTriggers($triggers, $defaultValues);
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
