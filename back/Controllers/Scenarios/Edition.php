<?php

namespace AcyMailing\Controllers\Scenarios;

use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\ScenarioClass;
use AcyMailing\Classes\ScenarioStepClass;
use AcyMailing\Helpers\WorkflowHelper;

trait Edition
{
    public function editScenario(): void
    {
        acym_setVar('layout', 'edit_scenario');
        acym_setVar('step', 'editScenario');

        $data = [
            'workflowHelper' => new WorkflowHelper(),
        ];

        $this->prepareScenario($data);
        $this->prepareFlow($data);
        $this->prepareTriggers($data);
        $this->prepareConditions($data);
        $this->prepareActions($data);
        $this->prepareReturnFromMailCreation($data);
        $this->prepareSendEmailAction($data);
        $this->prepareStepIds($data);
        $this->prepareTemplateModal($data);

        if (!empty($data['scenario']->id)) {
            $this->breadcrumb[$data['scenario']->name] = acym_completeLink('scenarios&task=edit&step=editScenario&scenarioId='.$data['scenario']->id);
        } else {
            $this->breadcrumb[acym_translation('ACYM_NEW_SCENARIO')] = acym_completeLink('scenarios&task=edit&step=editScenario');
        }

        parent::display($data);
    }

    private function prepareTemplateModal(array &$data): void
    {
        $data['defaultTemplates'] = [
            [
                'name' => acym_translation('ACYM_ONBOARDING'),
                'image' => 'onboarding.png',
            ],
            [
                'name' => acym_translation('ACYM_ONBOARDING'),
                'image' => 'onboarding.png',
            ],
            [
                'name' => acym_translation('ACYM_ONBOARDING'),
                'image' => 'onboarding.png',
            ],
            [
                'name' => acym_translation('ACYM_ONBOARDING'),
                'image' => 'onboarding.png',
            ],
        ];

        ob_start();
        include acym_getPartial('scenarios', 'modal_template');
        $data['modalTemplate'] = ob_get_clean();
    }

    private function prepareReturnFromMailCreation(array &$data): void
    {
        $mailId = acym_getVar('int', 'mailId', 0);
        $stepId = acym_getVar('string', 'stepId', '');

        if (empty($mailId) || empty($stepId)) {
            return;
        }

        $data['returnFromMailCreationStepId'] = $stepId;

        $this->changeMailIdInFlowByStepId($data['flow'], $stepId, $mailId);
    }

    private function changeMailIdInFlowByStepId(array &$node, string $stepId, int $mailId): void
    {
        if (!empty($node['slug']) && $node['slug'] === $stepId) {
            $node['params']['option'] = [
                'acym_action[actions][__and__][acy_send_email][mail_id]' => $mailId,
            ];

            return;
        }

        if (!empty($node['children'])) {
            foreach ($node['children'] as &$childNode) {
                $this->changeMailIdInFlowByStepId($childNode, $stepId, $mailId);
            }
        }
    }

    private function prepareSendEmailAction(array &$data): void
    {
        $this->searchForSendEmailAction($data['flow']);
    }

    private function searchForSendEmailAction(array &$node): void
    {
        if (!empty($node['params']['action']) && $node['params']['action'] === 'acy_send_email') {
            $mailId = !empty($node['params']['option']['acym_action[actions][__and__][acy_send_email][mail_id]'])
                ? $node['params']['option']['acym_action[actions][__and__][acy_send_email][mail_id]'] : 0;

            if (!empty($mailId)) {
                $mail = $this->mailClass->getOneById($mailId);

                if (!empty($mail)) {
                    $node['params']['option']['mail'] = $mail;
                }
            }
        }

        if (!empty($node['children'])) {
            foreach ($node['children'] as &$childNode) {
                $this->searchForSendEmailAction($childNode);
            }
        }
    }

    private function prepareScenario(array &$data): void
    {
        $scenarioId = acym_getVar('int', 'scenarioId', 0);

        $scenarioClass = new ScenarioClass();
        $scenario = new \stdClass();
        $scenario->name = '';
        $scenario->active = 0;
        $scenario->trigger_once = 0;
        if (!empty($scenarioId)) {
            $scenario = $scenarioClass->getOneById($scenarioId);
        }
        $data['scenario'] = $scenario;
    }

    private function prepareFlow(array &$data): void
    {
        if (empty($data['scenario']->id)) {
            return;
        }

        $flow = [
            'condition' => false,
            'params' => $data['scenario']->trigger_params,
            'children' => [],
        ];

        $scenarioStepClass = new ScenarioStepClass();
        $steps = $scenarioStepClass->getAllByScenarioId($data['scenario']->id);

        $nodeReferences = [];

        foreach ($steps as $item) {
            $nodeReferences[$item->id] = [
                'slug' => $item->id,
                'params' => $item->params,
                'condition' => $item->type == ScenarioClass::TYPE_CONDITION,
                'children' => [],
                'conditionValid' => $item->condition_valid,
            ];
        }

        foreach ($steps as $item) {
            if (empty($item->previous_id)) {
                $flow['children'][] = &$nodeReferences[$item->id];
            } else {
                $key = 0;
                if (!is_null($item->condition_valid)) {
                    $key = empty($item->condition_valid) ? 1 : 0;
                }
                $nodeReferences[$item->previous_id]['children'][$key] = &$nodeReferences[$item->id];
            }
        }

        $data['flow'] = $flow;
    }

    private function prepareTriggers(array &$data): void
    {
        $defaultValues = [];
        $triggers = ['classic' => [], 'user' => []];
        acym_trigger('onAcymDeclareTriggersScenario', [&$triggers, &$defaultValues]);

        $triggersFormatted = [];
        foreach ($triggers['user'] as $key => $trigger) {
            $trigger->key = $key;
            $trigger->name = strip_tags($trigger->name);
            $triggersFormatted[] = $trigger;
        }

        $data['triggers'] = $triggersFormatted;
    }

    private function prepareConditions(array &$data): void
    {
        $conditions = ['user' => [], 'classic' => []];
        acym_trigger('onAcymDeclareConditionsScenario', [&$conditions]);

        $conditionsFormatted = [];
        foreach ($conditions['user'] as $key => $condition) {
            $condition->key = $key;
            $condition->name = strip_tags($condition->name);
            $conditionsFormatted[] = $condition;
        }

        $data['conditions'] = $conditionsFormatted;
    }

    private function prepareActions(array &$data): void
    {
        $actions = [];
        acym_trigger('onAcymDeclareActionsScenario', [&$actions]);

        $actionsFormatted = [];
        foreach ($actions as $key => $action) {
            $action->key = $key;
            $action->name = strip_tags($action->name);
            $actionsFormatted[] = $action;
        }

        $data['actions'] = $actionsFormatted;
    }

    private function saveInner(): int
    {
        $id = acym_getVar('int', 'scenarioId', 0);
        $scenario = acym_getVar('array', 'scenario', []);

        if (empty($scenario)) {
            return 0;
        }

        $scenario = (object)$scenario;

        if (empty($scenario->name)) {
            $scenario->name = acym_translation('ACYM_NEW_SCENARIO');
        }

        if (!empty($id)) {
            $scenario->id = $id;
        }

        $scenarioClass = new ScenarioClass();

        return $scenarioClass->save($scenario);
    }

    public function saveExit(): void
    {
        $this->saveInner();
        $this->listing();
    }

    public function save(): void
    {
        $scenarioId = $this->saveInner();

        acym_setVar('scenarioId', $scenarioId);

        $this->performances();
    }

    public function createMail(): void
    {
        $options = acym_getVar('array', 'send_mail', []);

        if (empty($options['step_id'])) {
            return;
        }

        $scenarioId = $this->saveInner();

        if (empty($scenarioId)) {
            return;
        }

        $type = MailClass::TYPE_SCENARIO;

        $urlParams = [
            'task' => 'edit',
            'step' => 'editEmail',
            'type' => $type,
            'return' => acym_completeLink('scenarios&task=edit&step=editScenario&mailId={mailid}&scenarioId='.$scenarioId.'&stepId='.$options['step_id']),
        ];

        // Conditional mail ID
        if (!empty($options['mail_id'])) {
            $urlParams['id'] = $options['mail_id'];
        }

        $redirectLink = acym_completeLink('mails&'.http_build_query($urlParams), false, true);

        acym_redirect($redirectLink);
    }

    public function prepareStepIds(array &$data): void
    {
        $scenarioStepClass = new ScenarioStepClass();
        $data['allScenarioStepIds'] = $scenarioStepClass->getAllStepIds();
    }
}
