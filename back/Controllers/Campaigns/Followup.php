<?php

namespace AcyMailing\Controllers\Campaigns;

use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\SegmentClass;
use AcyMailing\Helpers\WorkflowHelper;
use stdClass;


trait Followup
{
    public function followup(): void
    {
        acym_setVar('layout', 'followup');

        $data = [
            'campaign_type' => MailClass::TYPE_FOLLOWUP,
            'element_to_display' => lcfirst(acym_translation('ACYM_FOLLOW_UP')),
        ];
        $this->getAllParamsRequest($data);
        $this->prepareEmailsListing($data, $data['campaign_type'], 'followup');
        $this->prepareToolbar($data);
        $this->prepareListingClasses($data);
        $this->prepareFollowupListing($data);

        parent::display($data);
    }

    private function prepareFollowupListing(array &$data): void
    {
        $followupClass = new FollowupClass();
        $triggers = [];
        acym_trigger('getFollowupTriggers', [&$triggers]);
        $data['allTriggers'] = $triggers;

        foreach ($data['allCampaigns'] as $key => $oneFollowup) {
            if (!empty($triggers[$oneFollowup->trigger])) {
                $oneFollowup->condition = json_decode($oneFollowup->condition, true);
                $data['allCampaigns'][$key]->condition = $followupClass->getConditionSummary($oneFollowup->condition, $oneFollowup->trigger);
                $data['allCampaigns'][$key]->mail_ids = $followupClass->getEmailsByIds($oneFollowup->id);
            }
        }
    }

    public function deleteFollowup(): void
    {
        acym_checkToken();
        $ids = acym_getVar('array', 'elements_checked', []);
        $allChecked = acym_getVar('string', 'checkbox_all');
        $currentPage = explode('_', acym_getVar('string', 'page'));
        $pageNumber = $this->getVarFiltersListing('int', end($currentPage).'_pagination_page', 1);

        if (!empty($ids)) {
            $followupClass = new FollowupClass();
            $followupClass->delete($ids);
            if ($allChecked == 'on') {
                $this->setVarFiltersListing(end($currentPage).'_pagination_page', $pageNumber - 1);
            }
        }

        $this->listing();
    }

    public function followupTrigger(): void
    {
        acym_setVar('layout', 'followup_trigger');

        $id = acym_getVar('int', 'id', 0);

        if (!empty($id)) {
            $followupClass = new FollowupClass();
            $followup = $followupClass->getOneById($id);
        } else {
            $followup = new stdClass();
        }

        $this->setTaskListing('followup');

        $data = [
            'workflowHelper' => new WorkflowHelper(),
            'followup' => $followup,
        ];

        $linkId = empty($id) ? '' : '&id='.$id;

        $this->breadcrumb[empty($id) ? acym_translation('ACYM_NEW_FOLLOW_UP') : $followup->name] = acym_completeLink('campaigns&task=edit&step=followupTrigger'.$linkId);

        parent::display($data);
    }

    public function followupCondition(): void
    {
        acym_setVar('layout', 'followup_condition');

        $id = acym_getVar('int', 'id', 0);
        $trigger = acym_getVar('string', 'trigger', '');

        if (!empty($id)) {
            $followupClass = new FollowupClass();
            $followup = $followupClass->getOneById($id);
        } else {
            $followup = new stdClass();
        }

        if (empty($trigger) && empty($followup->trigger)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_LOAD_DATA'), 'error');
            $this->listing();

            return;
        }

        $listClass = new ListClass();
        $lists = $listClass->getAllForSelect(false);

        $segmentClass = new SegmentClass();
        $segments = $segmentClass->getAllForSelect(false);

        $statusArray = [
            'is' => acym_strtolower(acym_translation('ACYM_IS')),
            'is_not' => acym_strtolower(acym_translation('ACYM_IS_NOT')),
        ];

        $additionalCondition = [];
        acym_trigger('getAcymAdditionalConditionFollowup', [&$additionalCondition, empty($trigger) ? $followup->trigger : $trigger, $followup, $statusArray]);

        $actualTrigger = empty($trigger) ? $followup->trigger : $trigger;

        $data = [
            'workflowHelper' => new WorkflowHelper(),
            'followup' => $followup,
            'additionalCondition' => $additionalCondition,
            'trigger' => $actualTrigger,
            'lists_multiselect' => '<span class="cell large-4 medium-6 acym__followup__condition__select__in-text">'.acym_selectMultiple(
                    $lists,
                    'followup[condition][lists]',
                    !empty($followup->condition) && !empty($followup->condition['lists']) ? $followup->condition['lists'] : [],
                    ['class' => 'acym__select']
                ).'</span>',
            'segments_multiselect' => '<span class="cell large-4 medium-6 acym__followup__condition__select__in-text">'.acym_selectMultiple(
                    $segments,
                    'followup[condition][segments]',
                    !empty($followup->condition) && !empty($followup->condition['segments']) ? $followup->condition['segments'] : [],
                    ['class' => 'acym__select']
                ).'</span>',
            'select_status_lists' => '<span class="cell xxlarge-1 medium-2 acym__followup__condition__select__in-text">'.acym_select(
                    $statusArray,
                    'followup[condition][lists_status]',
                    !empty($followup->condition) && !empty($followup->condition['lists_status']) ? $followup->condition['lists_status'] : '',
                    ['class' => 'acym__select']
                ).'</span>',
            'select_status_segments' => '<span class="cell xxlarge-1 medium-2 acym__followup__condition__select__in-text">'.acym_select(
                    $statusArray,
                    'followup[condition][segments_status]',
                    !empty($followup->condition) && !empty($followup->condition['segments_status']) ? $followup->condition['segments_status'] : '',
                    ['class' => 'acym__select']
                ).'</span>',
            'lists_subscribe_translation' => $actualTrigger == 'user_subscribe' ? 'ACYM_FOLLOW_UP_CONDITION_USER_SUBSCRIBING' : 'ACYM_FOLLOW_UP_CONDITION_USER_SUBSCRIBE',
        ];

        $linkId = empty($id) ? '&trigger='.$trigger : '&id='.$id;

        $this->breadcrumb[empty($followup->name) ? acym_translation('ACYM_NEW_FOLLOW_UP') : $followup->name] = acym_completeLink(
            'campaigns&task=edit&step=followupCondition'.$linkId
        );

        parent::display($data);
    }

    public function followupEmail(): void
    {
        acym_setVar('layout', 'followup_email');

        $id = acym_getVar('int', 'id', 0);

        if (empty($id)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_LOAD_DATA'), 'error');
            $this->listing();

            return;
        }

        $followupClass = new FollowupClass();
        $followup = $followupClass->getOneByIdWithMails($id);

        if (empty($followup)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_LOAD_DATA'), 'error');
            $this->listing();

            return;
        }

        $favoriteTemplate = $this->config->get('favorite_template', 0);
        $startFrom = empty($favoriteTemplate) ? '' : '&from='.$favoriteTemplate;
        $data = [
            'workflowHelper' => new WorkflowHelper(),
            'followup' => $followup,
            'linkNewEmail' => acym_completeLink(
                'mails&task=edit&step=editEmail&type=followup&followup_id='.$id.'&return='.urlencode(
                    acym_completeLink('campaigns&task=edit&step=followupEmail&id='.$id)
                ).$startFrom,
                false,
                true
            ),
        ];

        $this->breadcrumb[empty($followup->name) ? acym_translation('ACYM_NEW_FOLLOW_UP') : $followup->name] = acym_completeLink(
            'campaigns&task=edit&step=followupEmail&id='.$followup->id
        );

        // Ask the user if he wants to add the new email to the queue for the users that already triggered the followup
        if (intval($followup->active) === 1) {
            $newlyCreatedEmail = acym_getVar('int', 'newEmailId');
            if (!empty($newlyCreatedEmail)) {
                $numberOfSubscribers = $followupClass->getNumberSubscribersByListId($followup->list_id, true);
                if (!empty($numberOfSubscribers)) {
                    $message = '<span class="acym__followup__add_queue" data-acym-email-id="'.intval($newlyCreatedEmail).'">';
                    $message .= acym_translationSprintf('ACYM_FOLLOWUP_ADD_QUEUE', $numberOfSubscribers);
                    $message .= '</span>';
                    acym_enqueueMessage($message, 'info', false);
                }
            }
        }

        parent::display($data);
    }

    public function followupDuplicateMail(): void
    {
        $mailId = acym_getVar('int', 'action_mail_id', 0);
        $id = acym_getVar('int', 'id', 0);
        $followupClass = new FollowupClass();
        if (!$followupClass->duplicateMail($mailId, $id)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_DUPLICATE_MAIL'), 'error');
        }

        $this->followupEmail();
    }

    public function followupDeleteMail(): void
    {
        $mailId = acym_getVar('int', 'action_mail_id', 0);
        $followupClass = new FollowupClass();
        if (!$followupClass->deleteMail($mailId)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_DELETE_MAIL'), 'error');
        }

        $step = acym_getVar('cmd', 'step', 'followupEmail');
        $this->$step();
    }

    public function followupDraft(): void
    {
        $this->followupFinalize(0);
    }

    public function followupActivate(): void
    {
        $this->followupFinalize(1);
    }

    public function followupFinalize(int $status): void
    {
        $followupId = acym_getVar('int', 'id', 0);
        $followupClass = new FollowupClass();
        $followup = $followupClass->getOneById($followupId);
        $followup->active = $status;
        $followupClass->save($followup);

        $this->followup();
    }

    public function saveFollowupCondition(): void
    {
        if (!acym_isAdmin()) {
            die('Access denied for follow-ups');
        }

        $id = acym_getVar('int', 'id', 0);
        $trigger = acym_getVar('string', 'trigger', '');
        $followupData = acym_getVar('array', 'followup', []);

        $followupClass = new FollowupClass();

        if (!empty($id)) {
            $followup = $followupClass->getOneById($id);
            $followup->condition = json_encode($followupData['condition']);
        } else {
            $followup = new stdClass();
            $followup->name = '';
            $followup->display_name = '';
            $followup->creation_date = date('Y-m-d H:i:s', time() - date('Z'));
            $followup->trigger = $trigger;
            $followup->condition = json_encode($followupData['condition']);
            $followup->active = 0;
            $followup->send_once = 1;
        }

        $followup->id = $followupClass->save($followup);
        if (!empty($followup->id)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING').': '.acym_getDBError(), 'error');
            $this->listing();

            return;
        }

        acym_setVar('id', $followup->id);

        $this->edit();
    }

    public function saveFollowupEmail(bool $redirect = true): void
    {
        if (!acym_isAdmin()) {
            die('Access denied for follow-ups');
        }

        $id = acym_getVar('int', 'id', 0);
        $followupData = acym_getVar('array', 'followup', []);
        if (empty($id) || empty($followupData)) {
            return;
        }

        $followupClass = new FollowupClass();
        $followup = $followupClass->getOneById($id);

        if (empty($followup)) {
            return;
        }

        foreach ($followupData as $key => $data) {
            if (!isset($followup->$key)) continue;
            $followup->$key = $data;
        }

        $followup->id = $followupClass->save($followup);
        acym_setVar('id', $followup->id);

        if ($redirect) {
            $this->edit();
        }
    }

    public function followupSummary(): void
    {
        acym_setVar('layout', 'followup_summary');

        $id = acym_getVar('int', 'id', 0);

        if (empty($id)) {
            acym_enqueueMessage(acym_translation('ACYM_FOLLOWUP_NOT_FOUND'), 'error');
            $this->listing();

            return;
        }

        $followupClass = new FollowupClass();
        $followup = $followupClass->getOneByIdWithMails($id);

        $data = [
            'workflowHelper' => new WorkflowHelper(),
            'followup' => $followup,
            'condition' => $followupClass->getConditionSummary($followup->condition, $followup->trigger),
        ];

        $this->breadcrumb[empty($followup->name) ? acym_translation('ACYM_NEW_FOLLOW_UP') : $followup->name] = acym_completeLink(
            'campaigns&task=edit&step=followupCondition'.$followup->id
        );

        parent::display($data);
    }

    public function createNewFollowupMail(): void
    {
        $this->saveFollowupEmail(false);
        $linkNewEmail = acym_getVar('string', 'linkNewEmail', '');

        if (empty($linkNewEmail)) {
            $this->edit();
        } else {
            acym_redirect($linkNewEmail);
        }
    }
}
