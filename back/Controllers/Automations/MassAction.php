<?php

namespace AcyMailing\Controllers\Automations;

use AcyMailing\Classes\AutomationClass;

trait MassAction
{
    public function setFilterMassAction(): void
    {
        acym_checkToken();

        $this->setSaveFilters(true);
        $this->summary();
    }

    public function setActionMassAction(): void
    {
        acym_checkToken();

        $this->getSaveActions(true);
        $this->filter();
    }

    public function processMassAction(): void
    {
        acym_checkToken();

        $automationClass = new AutomationClass();
        $massAction = acym_getVar('array', 'massAction', [], 'SESSION');
        if (!empty($massAction)) {
            $automation = new \stdClass();
            $automation->filters = json_encode($massAction['filters']);
            $automation->actions = json_encode($massAction['actions']);
            $automationClass->execute($automation);

            if (!empty($automationClass->report)) {
                foreach ($automationClass->report as $oneReport) {
                    acym_enqueueMessage($oneReport, 'info');
                }
            }
        }
        $this->listing();
    }
}
