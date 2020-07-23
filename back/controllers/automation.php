<?php

class AutomationController extends acymController
{
    var $regexSwitches = '#(switch_[0-9]*".*)(data\-switch=")(switch_.+id=")(switch_.+for=")(switch_)#Uis';

    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_AUTOMATION')] = acym_completeLink('automation');
        $this->loadScripts = [
            'info' => ['datepicker'],
            'condition' => ['datepicker'],
            'action' => ['datepicker'],
            'filter' => ['datepicker'],
        ];
        acym_setVar('edition', '1');
    }

    public function listing()
    {
        if (!acym_level(2)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }

    }

    public function prepareToolbar(&$data)
    {
        $toolbarHelper = acym_get('helper.toolbar');
        $toolbarHelper->addSearchBar($data['search'], 'automation_search', 'ACYM_SEARCH');
        $toolbarHelper->addButton(acym_translation('ACYM_NEW_MASS_ACTION'), ['data-task' => 'edit', 'data-step' => 'action'], 'cog');
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'edit', 'data-step' => 'info'], 'add', true);

        $data['toolbar'] = $toolbarHelper;
    }

    public function info()
    {
        if (!acym_level(2)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }

    }

    public function condition()
    {
        if (!acym_level(2)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }

    }

    public function filter()
    {

        if (!acym_level(2)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }
    }

    public function switches($matches)
    {
        return '__numand__'.$matches[1].$matches[2].'__numand__'.$matches[3].'__numand__'.$matches[4].'__numand__'.$matches[5];
    }

    public function action()
    {

        if (!acym_level(2)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }
    }

    public function summary()
    {

        if (!acym_level(2)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }
    }

    private function _saveInfos($isMassAction = false)
    {
        if ($isMassAction) {
            acym_session();
        }

        $automationId = acym_getVar('int', 'id');
        $automation = acym_getVar('array', 'automation');
        $automationClass = acym_get('class.automation');

        $stepAutomationId = acym_getVar('int', 'stepAutomationId');
        $stepAutomation = acym_getVar('array', 'stepAutomation');
        $stepClass = acym_get('class.step');

        if (!empty($automationId)) {
            $automation['id'] = $automationId;
        }

        if (!empty($stepAutomationId)) {
            $stepAutomation['id'] = $stepAutomationId;
        }

        $typeTrigger = acym_getVar('string', 'type_trigger');

        if (empty($automation['admin']) && empty($automation['name'])) {
            return false;
        }

        if (empty($stepAutomation['triggers'][$typeTrigger])) {
            acym_enqueueMessage(acym_translation('ACYM_PLEASE_SELECT_ONE_TRIGGER'), 'error');

            $this->info();

            return false;
        }

        $stepAutomation['triggers'][$typeTrigger]['type_trigger'] = $typeTrigger;
        $stepAutomation['triggers'] = json_encode($stepAutomation['triggers'][$typeTrigger]);

        $stepAutomation['automation_id'] = $automationId;

        foreach ($automation as $column => $value) {
            acym_secureDBColumn($column);
        }

        foreach ($stepAutomation as $stepColumn => $stepValue) {
            acym_secureDBColumn($stepColumn);
        }

        //We need objects to save it so we make objects
        $automation = (object)$automation;
        $stepAutomation = (object)$stepAutomation;

        $automation->id = $automationClass->save($automation);
        $stepAutomation->automation_id = $automation->id;
        $stepAutomation->id = $stepClass->save($stepAutomation);

        $returnIds = [
            "automationId" => $automation->id,
            "stepId" => $stepAutomation->id,
            "typeTrigger" => $typeTrigger,
        ];

        if ($isMassAction) {
            return true;
        } elseif (!empty($returnIds['automationId']) && !empty($returnIds['stepId'])) {
            return $returnIds;
        } else {
            return false;
        }
    }

    private function _saveConditions($isMassAction = false)
    {
        $automationID = acym_getVar('int', 'id');
        $conditionId = acym_getVar('int', 'conditionId');
        $condition = acym_getVar('array', 'acym_condition', []);
        $conditionClass = acym_get('class.condition');

        $stepAutomationId = acym_getVar('int', 'stepAutomationId');

        if (!empty($stepAutomationId)) {
            $stepAutomation['id'] = $stepAutomationId;
        }

        if (!empty($conditionId)) {
            $condition['id'] = $conditionId;
        }

        $condition['conditions']['type_condition'] = acym_getVar('string', 'type_condition');

        if ($isMassAction) {
            acym_session();
            $_SESSION['massAction']['conditions'] = $condition['conditions'];

            return true;
        }

        $condition['conditions'] = json_encode($condition['conditions']);

        $condition['step_id'] = $stepAutomationId;

        foreach ($condition as $column => $value) {
            acym_secureDBColumn($column);
        }

        //We need an object to save it so we make a object
        $condition = (object)$condition;

        $condition->id = $conditionClass->save($condition);

        $returnIds = [
            'automationId' => $automationID,
            'stepId' => $stepAutomationId,
            'conditionId' => $condition->id,
        ];

        return $returnIds;
    }

    private function _saveFilters($isMassAction = false)
    {
        $automationID = acym_getVar('int', 'id');
        $actionId = acym_getVar('int', 'actionId');
        $action = acym_getVar('array', 'acym_action', []);
        $actionClass = acym_get('class.action');
        $conditionId = acym_getVar('int', 'conditionId');

        $stepAutomationId = acym_getVar('int', 'stepAutomationId');

        if (!empty($stepAutomationId)) {
            $stepAutomation['id'] = $stepAutomationId;
        }

        if (!empty($conditionId)) {
            $action['condition_id'] = $conditionId;
        }

        if (!empty($actionId)) {
            $action['id'] = $actionId;
        }

        $action['filters']['type_filter'] = acym_getVar('string', 'type_filter');

        if ($isMassAction) {
            acym_session();
            $_SESSION['massAction']['filters'] = $action['filters'];

            return true;
        }

        $action['filters'] = json_encode($action['filters']);

        $action['order'] = 1;

        foreach ($action as $column => $value) {
            acym_secureDBColumn($column);
        }

        //We need an object to save it so we make a object
        $action = (object)$action;

        $action->id = $actionClass->save($action);

        $returnIds = [
            'automationId' => $automationID,
            'stepId' => $stepAutomationId,
            'actionId' => $action->id,
        ];

        return $returnIds;
    }

    private function _saveActions($isMassAction = false)
    {
        if ($isMassAction) {
            acym_session();
        }

        $automationID = acym_getVar('int', 'id');
        $stepID = acym_getVar('int', 'id');
        $actionId = acym_getVar('int', 'actionId');
        $action = acym_getVar('array', 'acym_action');
        $actionClass = acym_get('class.action');
        $stepAutomationId = acym_getVar('int', 'stepAutomationId');
        $conditionId = acym_getVar('int', 'conditionId');

        if (!empty($stepAutomationId)) {
            $stepAutomation['id'] = $stepAutomationId;
        }

        if ((!empty($conditionId))) {
            $action['condition_id'] = $conditionId;
        }

        if (!empty($actionId)) {
            $action['id'] = $actionId;
        }

        if (empty($action['actions'])) {
            $action['actions'] = [];
        }

        if ($isMassAction) {
            $_SESSION['massAction']['actions'] = $action['actions'];

            return true;
        }

        $action['actions'] = json_encode($action['actions']);

        foreach ($action as $column => $value) {
            acym_secureDBColumn($column);
        }

        //We need an object to save it so we make a object
        $action = (object)$action;

        $action->id = $actionClass->save($action);

        $returnIds = [
            'automationId' => $automationID,
            'stepId' => $stepAutomationId,
            'actionId' => $action->id,
        ];

        return $returnIds;
    }

    private function _saveAutomation($from, $isMassAction = false)
    {
        if ($isMassAction) {
            acym_session();
        }

        $automationId = acym_getVar('int', 'id');
        $automation = acym_getVar('array', 'automation');
        $automationClass = acym_get('class.automation');

        $stepAutomationId = acym_getVar('int', 'stepAutomationId');
        $stepAutomation = acym_getVar('array', 'stepAutomation');
        $stepClass = acym_get('class.step');

        if (!empty($automationId)) {
            $automation['id'] = $automationId;
        }

        if (!empty($stepAutomationId)) {
            $stepAutomation['id'] = $stepAutomationId;
        }

        if ($from == 'info') {
            $typeTrigger = acym_getVar('string', 'type_trigger');

            if (empty($automation['name'])) {
                return false;
            }

            if (empty($stepAutomation['triggers'][$typeTrigger])) {
                acym_enqueueMessage(acym_translation('ACYM_PLEASE_SELECT_ONE_TRIGGER'), 'error');

                $this->info();

                return false;
            }

            $stepAutomation['triggers'][$typeTrigger]['type_trigger'] = $typeTrigger;
            $stepAutomation['triggers'] = json_encode($stepAutomation['triggers'][$typeTrigger]);

            $stepAutomation['automation_id'] = $automationId;

            foreach ($automation as $column => $value) {
                acym_secureDBColumn($column);
            }

            foreach ($stepAutomation as $stepColumn => $stepValue) {
                acym_secureDBColumn($stepColumn);
            }

            //We need objects to save it so we make objects
            $automation = (object)$automation;
            $stepAutomation = (object)$stepAutomation;

            $saveIdStepAutomation = $stepClass->save($stepAutomation);
            $saveIdAutomation = $automationClass->save($automation);

            $returnIds = [
                "automationId" => $saveIdAutomation,
                "stepId" => $saveIdStepAutomation,
            ];

            if ($isMassAction) {
                return true;
            } elseif (!empty($returnIds['automationId']) && !empty($returnIds['stepId'])) {
                return $returnIds;
            } else return false;
        } elseif ($from == 'filters') {
            $stepAutomation['filters']['type_filter'] = acym_getVar('string', 'type_filter');
            if ($isMassAction) {
                $_SESSION['massAction']['filters'] = $stepAutomation['filters'];
            }
            $stepAutomation['filters'] = json_encode($stepAutomation['filters']);
        } elseif ($from == 'actions') {
            if (empty($stepAutomation['actions'])) {
                acym_enqueueMessage(acym_translation('ACYM_PLEASE_SET_ACTIONS'), 'error');
                if (!empty($automationId)) acym_setVar('id', $automationId);
                $this->action();

                return false;
            }
            if ($isMassAction) {
                $_SESSION['massAction']['actions'] = $stepAutomation['actions'];
            }
            $stepAutomation['actions'] = json_encode($stepAutomation['actions']);
        } elseif ($from == 'summary') {
            $automation = $automationClass->getOneById($automationId);
            $automation->active = 1;
        }

        if ($isMassAction) {
            return true;
        } else {
            switch ($from) {
                case 'info':
                case 'summary':
                    foreach ($automation as $column => $value) {
                        acym_secureDBColumn($column);
                    }

                    //We need an object to save it so we make a object
                    $automation = (object)$automation;

                    return $automationClass->save($automation);
                case 'filters':
                case 'actions':
                    $stepAutomation['automation_id'] = $automationId;
                    $stepAutomation['order'] = 1;

                    foreach ($stepAutomation as $column => $value) {
                        acym_secureDBColumn($column);
                    }

                    //We need an object to save it so we make a object
                    $stepAutomation = (object)$stepAutomation;

                    return $stepClass->save($stepAutomation);
                default:
                    return false;
            }
        }
    }

    public function saveExitInfo()
    {
        $ids = $this->_saveInfos();

        if (empty($ids)) {
            return;
        }

        acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');

        acym_setVar('id', $ids['automationId']);
        acym_setVar('stepId', $ids['stepId']);
        $this->listing();
    }

    public function saveInfo()
    {
        $ids = $this->_saveInfos();

        if (empty($ids)) {
            return;
        }

        acym_setVar('id', $ids['automationId']);
        acym_setVar('stepId', $ids['stepId']);
        $this->condition();
    }

    public function saveExitConditions()
    {
        $ids = $this->_saveConditions();

        if (empty($ids)) {
            return;
        }

        acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');

        $this->listing();
    }

    public function saveConditions()
    {
        $ids = $this->_saveConditions();

        if (empty($ids)) {
            return;
        }

        acym_setVar('id', $ids['automationId']);
        acym_setVar('stepId', $ids['stepId']);
        acym_setVar('conditionId', $ids['conditionId']);
        $this->action();
    }

    public function saveExitFilters()
    {
        $ids = $this->_saveFilters();

        if (empty($ids)) {
            return;
        }

        acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');

        $this->listing();
    }

    public function saveFilters()
    {
        $ids = $this->_saveFilters();

        if (empty($ids)) {
            return;
        }

        acym_setVar('id', $ids['automationId']);
        acym_setVar('stepId', $ids['stepId']);
        acym_setVar('actionId', $ids['actionId']);
        $this->summary();
    }

    public function saveExitActions()
    {
        $ids = $this->_saveActions();

        if (empty($ids)) {
            return;
        }

        acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');

        $this->listing();
    }

    public function saveActions()
    {
        $ids = $this->_saveActions();

        if (empty($ids)) {
            return;
        }

        acym_setVar('id', $ids['automationId']);
        acym_setVar('stepId', $ids['stepId']);
        acym_setVar('actionId', $ids['actionId']);
        $this->filter();
    }

    public function activeAutomation()
    {
        $automationClass = acym_get('class.automation');
        $automation = $automationClass->getOneById(acym_getVar('int', 'id'));
        $automation->active = 1;
        $saved = $automationClass->save($automation);
        if (!empty($saved)) {
            acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');
            $this->listing();
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
            $this->listing();
        }
    }

    //MASS ACTION

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
        $automationClass = acym_get('class.automation');
        $massAction = empty($_SESSION['massAction']) ? '' : $_SESSION['massAction'];
        if (!empty($massAction)) {
            $automation = new stdClass();
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

    public function createMail()
    {
        $id = acym_getVar('int', 'id');
        $idAdmin = acym_getVar('boolean', 'automation_admin');
        $type = 'automation';
        if ($idAdmin) $type = 'automation_admin';
        $and = acym_getVar('string', 'and_action');
        $this->_saveActions(empty($id));
        $actions = acym_getVar('array', 'acym_action');
        $mailId = $actions['actions'][$and]['acy_add_queue']['mail_id'];
        acym_redirect(acym_completeLink('mails&task=edit&step=editEmail&type='.$type.'&from='.$mailId.'&return='.urlencode(acym_completeLink('automation&task=edit&step=action&id='.$id.'&fromMailEditor=1&mailid={mailid}&and='.$and)), false, true));
    }

    //Ajax calls

    public function countresults()
    {
        $or = acym_getVar('int', 'or');
        $and = acym_getVar('int', 'and');
        $stepAutomation = acym_getVar('array', 'acym_action');

        if (empty($stepAutomation['filters'][$or][$and])) die(acym_translation('ACYM_AUTOMATION_NOT_FOUND'));

        $query = acym_get('class.query');
        $messages = '';

        foreach ($stepAutomation['filters'][$or][$and] as $filterName => $options) {
            $messages = acym_trigger('onAcymProcessFilterCount_'.$filterName, [&$query, &$options, &$and]);
            break;
        }

        echo implode(' | ', $messages);
        exit;
    }

    public function countResultsOrTotal()
    {
        $or = acym_getVar('int', 'or');
        $stepAutomation = acym_getVar('array', 'acym_action');

        $query = acym_get('class.query');

        if (!empty($stepAutomation) && !empty($stepAutomation['filters'][$or])) {
            foreach ($stepAutomation['filters'][$or] as $and => $andValues) {
                $and = intval($and);
                foreach ($andValues as $filterName => $options) {
                    acym_trigger('onAcymProcessFilter_'.$filterName, [&$query, &$options, &$and]);
                }
            }
        }

        $result = $query->count();

        echo acym_translation_sprintf('ACYM_SELECTED_USERS_TOTAL', $result);
        exit;
    }
}
