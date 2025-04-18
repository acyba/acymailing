<?php

namespace AcyMailing\Controllers\Automations;

use AcyMailing\Classes\ActionClass;
use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\ConditionClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\StepClass;
use AcyMailing\Classes\TagClass;
use AcyMailing\Helpers\WorkflowHelper;

trait Action
{
    public function action(): void
    {

        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }
    }

    private function _saveActions(bool $isMassAction = false): array
    {
        if ($isMassAction) {
            acym_session();
        }

        $automationID = acym_getVar('int', 'id');
        $actionId = acym_getVar('int', 'actionId');
        $action = acym_getVar('array', 'acym_action');
        $actionClass = new ActionClass();
        $stepAutomationId = acym_getVar('int', 'stepAutomationId');
        $conditionId = acym_getVar('int', 'conditionId');

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

            return [];
        }

        $action['actions'] = json_encode($action['actions']);

        foreach ($action as $column => $value) {
            acym_secureDBColumn($column);
        }

        //We need an object to save it so we make a object
        $action = (object)$action;

        $action->id = $actionClass->save($action);

        return [
            'automationId' => $automationID,
            'stepId' => $stepAutomationId,
            'actionId' => $action->id,
        ];
    }

    public function saveExitActions(): void
    {
        $this->_saveActions();

        acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'));

        $this->listing();
    }

    public function saveActions(): void
    {
        $ids = $this->_saveActions();

        acym_setVar('id', $ids['automationId']);
        acym_setVar('stepId', $ids['stepId']);
        acym_setVar('actionId', $ids['actionId']);
        $this->filter();
    }

    public function createMail(): void
    {
        $id = acym_getVar('int', 'id');
        $idAdmin = acym_getVar('boolean', 'automation_admin');
        $type = MailClass::TYPE_AUTOMATION;
        if ($idAdmin) {
            $type = 'automation_admin';
        }

        $and = acym_getVar('string', 'and_action');
        $this->_saveActions(empty($id));

        $actions = acym_getVar('array', 'acym_action');
        $mailId = $actions['actions'][$and]['acy_add_queue']['mail_id'];
        $mailId = empty($mailId) ? '' : '&id='.$mailId;

        $favoriteTemplate = $this->config->get('favorite_template', 0);
        $startFrom = empty($favoriteTemplate) || !empty($mailId) ? '' : '&from='.$favoriteTemplate;

        acym_redirect(
            acym_completeLink(
                'mails&task=edit&step=editEmail&type='.$type.$mailId.'&return='.urlencode(
                    acym_completeLink('automation&task=edit&step=action&id='.$id.'&fromMailEditor=1&mailid={mailid}&and='.$and)
                ).$startFrom,
                false,
                true
            )
        );
    }
}
