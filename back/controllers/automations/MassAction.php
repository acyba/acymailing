<?php

namespace AcyMailing\Controllers\Automations;

use AcyMailing\Classes\AutomationClass;

trait MassAction
{
    public function setFilterMassAction()
    {
        $this->_saveFilters(true);
        $this->summary();
    }

    public function setActionMassAction()
    {
        $res = $this->_saveActions(true);
        if (!$res) return false;
        $this->filter();
    }

    public function processMassAction()
    {
        acym_session();
        $automationClass = new AutomationClass();
        $massAction = empty($_SESSION['massAction']) ? '' : $_SESSION['massAction'];
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
