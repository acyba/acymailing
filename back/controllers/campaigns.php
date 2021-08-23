<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\QueueClass;
use AcyMailing\Classes\SegmentClass;
use AcyMailing\Classes\TagClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\EditorHelper;
use AcyMailing\Helpers\EntitySelectHelper;
use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\PluginHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Helpers\WorkflowHelper;
use AcyMailing\Libraries\acymController;
use AcyMailing\Types\UploadfileType;

class CampaignsController extends acymController
{
    var $stepContainerClass = '';

    public function __construct()
    {
        $this->defaulttask = 'campaigns';
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_EMAILS')] = acym_completeLink('campaigns');
        $this->loadScripts = [
            'recipients' => ['vue-applications' => ['entity_select']],
            'send_settings' => ['datepicker'],
            'summary' => ['vue-applications' => ['modal_users_summary']],
            'segment' => ['datepicker', 'vue-applications' => ['modal_users_summary']],
        ];
        acym_setVar('edition', '1');
        if (acym_isAdmin()) $this->stepContainerClass = 'xxlarge-9';
        acym_header('X-XSS-Protection:0');
        $this->storeRedirectListing();
    }

    public function listing()
    {
        $this->storeRedirectListing(true);
    }

    public function storeRedirectListing($fromListing = false)
    {
        $isFrontJoomla = !acym_isAdmin() && ACYM_CMS == 'joomla';
        $variableName = $isFrontJoomla ? 'ctrl_stored_front' : 'ctrl_stored';
        acym_session();
        $taskToStore = [
            '',
            'campaigns',
            'campaigns_auto',
            'welcome',
            'unsubscribe',
            'followup',
            'specificListing',
        ];
        $currentTask = acym_getVar('string', 'task', '');
        $type = acym_getVar('string', 'type', '');
        if (!in_array($currentTask, $taskToStore) && !$fromListing) return;

        if ((empty($currentTask) || !in_array($currentTask, $taskToStore)) && !empty($_SESSION[$variableName])) {
            $taskToGo = is_array($_SESSION[$variableName]) ? $_SESSION[$variableName]['task'].'&type='.$_SESSION[$variableName]['type'] : $_SESSION[$variableName];
            $link = $isFrontJoomla ? acym_frontendLink('frontcampaigns&task='.$taskToGo) : acym_completeLink('campaigns&task='.$taskToGo, false, true);
            acym_redirect($link);
        } else {
            if (empty($currentTask) || !in_array($currentTask, $taskToStore)) $currentTask = 'campaigns';
            if ($currentTask == 'specificListing') $currentTask = empty($type) ? 'campaigns' : ['task' => $currentTask, 'type' => $type];
            $_SESSION[$variableName] = $currentTask;
        }

        $taskToCall = is_array($currentTask) ? $currentTask['task'] : $currentTask;
        if ($fromListing && method_exists($this, $taskToCall)) $this->$taskToCall();
    }

    public function setTaskListing($task)
    {
        if (!in_array($task, ['campaigns', 'campaigns_auto', 'welcome', 'unsubscribe',])) return false;

        $isFrontJoomla = !acym_isAdmin() && ACYM_CMS == 'joomla';
        $variableName = $isFrontJoomla ? 'ctrl_stored_front' : 'ctrl_stored';
        acym_session();
        $_SESSION[$variableName] = $task;

        return true;
    }

    private function prepareListingClasses(&$data)
    {
        $data['workflowHelper'] = new WorkflowHelper();
    }

    public function specificListing()
    {
        acym_setVar('layout', 'specific_listing');

        $type = acym_getVar('string', 'type');

        $data = [
            'type' => $type,
            //We set campaign here to generate the statuses in the campaign class in the function getCountStatusFilter
            'campaign_type' => 'campaigns',
        ];
        $this->getAllParamsRequest($data);
        $this->prepareListingClasses($data);
        $this->prepareToolbar($data);

        acym_trigger('onAcymCampaignDataSpecificListing', [&$data, $type]);

        parent::display($data);
    }

    public function followup()
    {
        acym_setVar('layout', 'followup');

        $mailClass = new MailClass();
        $data = [
            'campaign_type' => $mailClass::TYPE_FOLLOWUP,
            'element_to_display' => lcfirst(acym_translation('ACYM_FOLLOW_UP')),
        ];
        $this->getAllParamsRequest($data);
        $this->prepareEmailsListing($data, $data['campaign_type'], 'followup');
        $this->prepareToolbar($data);
        $this->prepareListingClasses($data);
        $this->prepareFollowupListing($data);

        parent::display($data);
    }

    private function prepareFollowupListing(&$data)
    {
        $followupClass = new FollowupClass();
        $triggers = [];
        acym_trigger('getFollowupTriggers', [&$triggers]);
        $data['allTriggers'] = $triggers;
        foreach ($data['allCampaigns'] as $key => $oneFollowup) {
            if (!empty($triggers[$oneFollowup->trigger])) {
                $oneFollowup->condition = json_decode($oneFollowup->condition, true);
                $data['allCampaigns'][$key]->condition = $followupClass->getConditionSummary($oneFollowup->condition, $oneFollowup->trigger);
            }
        }
    }

    public function deleteFollowup()
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

    public function campaigns()
    {
        acym_setVar('layout', 'campaigns');

        $data = [
            'campaign_type' => 'campaigns',
            'element_to_display' => lcfirst(acym_translation('ACYM_CAMPAIGNS')),
        ];
        $this->prepareAllCampaignsListing($data);
        $this->prepareToolbar($data);
        $this->prepareListingClasses($data);

        parent::display($data);
    }

    public function prepareToolbar(&$data)
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'campaigns_search', 'ACYM_SEARCH');
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'newEmail'], 'add', true);
        $mailClass = new MailClass();
        if (empty($data['campaign_type']) || $data['campaign_type'] !== $mailClass::TYPE_FOLLOWUP) {
            $toolbarHelper->addFilterByTag($data, 'campaigns_tag', 'acym__campaigns__filter__tags acym__select');
        }

        $data['toolbar'] = $toolbarHelper;
    }

    public function campaigns_auto()
    {
        if (!acym_level(ACYM_ENTERPRISE)) {
            $this->campaigns();
        }
    }

    public function welcome()
    {
        acym_setVar('layout', 'welcome');
        $mailClass = new MailClass();
        $data = [
            'cleartask' => 'welcome',
            'email_type' => $mailClass::TYPE_WELCOME,
            'element_to_display' => lcfirst(acym_translation('ACYM_WELCOME_EMAILS')),
        ];

        $this->prepareWelcomeUnsubListing($data);
        $this->prepareToolbar($data);
        $this->prepareListingClasses($data);

        parent::display($data);
    }

    public function unsubscribe()
    {
        acym_setVar('layout', 'unsubscribe');
        $mailClass = new MailClass();
        $data = [
            'cleartask' => 'unsubscribe',
            'email_type' => $mailClass::TYPE_UNSUBSCRIBE,
            'element_to_display' => lcfirst(acym_translation('ACYM_UNSUBSCRIBE_EMAILS')),
        ];

        $this->prepareWelcomeUnsubListing($data);
        $this->prepareToolbar($data);
        $this->prepareListingClasses($data);

        parent::display($data);
    }

    private function prepareWelcomeUnsubListing(&$data)
    {
        $this->getAllParamsRequest($data);
        $this->prepareEmailsListing($data, $data['email_type'], 'Mail');
    }

    private function getAllParamsRequest(&$data)
    {
        $tagClass = new TagClass();
        $data['search'] = $this->getVarFiltersListing('string', 'campaigns_search', '');
        $data['tag'] = $this->getVarFiltersListing('string', 'campaigns_tag', '');
        $data['allTags'] = $tagClass->getAllTagsByType('mail');
        $data['pagination'] = new PaginationHelper();
        $data['status'] = '';
        if (isset($data['campaign_type'])) {
            $data['status'] = $this->getVarFiltersListing('string', $data['campaign_type'].'_status', '');
            $data['ordering'] = $this->getVarFiltersListing('string', $data['campaign_type'].'_ordering', 'id');
            $data['orderingSortOrder'] = $this->getVarFiltersListing('string', $data['campaign_type'].'_ordering_sort_order', 'desc');
        } elseif (isset($data['email_type'])) {
            $data['ordering'] = $this->getVarFiltersListing('string', $data['email_type'].'_ordering', 'id');
            $data['orderingSortOrder'] = $this->getVarFiltersListing('string', $data['email_type'].'_ordering_sort_order', 'desc');
        }

        if (!empty($data['tag'])) {
            $data['status_toolbar'] = [
                'campaigns_tag' => $data['tag'],
            ];
        }
    }

    private function prepareAllCampaignsListing(&$data)
    {
        $this->getAllParamsRequest($data);
        $this->prepareEmailsListing($data, $data['campaign_type']);
        if ($data['campaign_type'] == 'campaigns_auto') {
            $this->getAutoCampaignsFrequency($data);
            $this->getIsPendingGenerated($data);
        }
    }

    private function getAutoCampaignsFrequency(&$data)
    {
        foreach ($data['allCampaigns'] as $key => $campaign) {
            if (empty($campaign->sending_params)) continue;
            $textToDisplay = new \stdClass();
            $textToDisplay->triggers = $campaign->sending_params;
            acym_trigger('onAcymDeclareSummary_triggers', [&$textToDisplay], 'plgAcymTime');
            $textToDisplay = array_values($textToDisplay->triggers);
            $data['allCampaigns'][$key]->sending_params['trigger_text'] = empty($textToDisplay[0]) ? acym_translation('ACYM_ERROR_WHILE_RECOVERING_TRIGGERS') : $textToDisplay[0];
        }
    }

    public function prepareEmailsListing(&$data, $campaignType = '', $class = '')
    {
        // Prepare the pagination
        $campaignsPerPage = $data['pagination']->getListLimit();
        $page = $this->getVarFiltersListing('int', 'campaigns_pagination_page', 1);
        $status = $data['status'];

        // Get the matching campaigns
        $campaignClass = new CampaignClass();
        $matchingCampaigns = $this->getMatchingElementsFromData(
            [
                'element_tab' => $campaignType,
                'ordering' => $data['ordering'],
                'search' => $data['search'],
                'elementsPerPage' => $campaignsPerPage,
                'offset' => ($page - 1) * $campaignsPerPage,
                'tag' => $data['tag'],
                'ordering_sort_order' => $data['orderingSortOrder'],
                'status' => $data['status'],
                'creator_id' => acym_isAdmin() ? 0 : acym_currentUserId(),
            ],
            $status,
            $page,
            empty($class) ? '' : $class
        );

        if (empty($class)) {
            foreach ($matchingCampaigns['elements'] as $key => $campaign) {
                $matchingCampaigns['elements'][$key]->scheduled = $campaignClass::SENDING_TYPE_SCHEDULED == $campaign->sending_type;
            }
        }

        // End pagination
        if (empty($class)) {
            $data['allStatusFilter'] = $this->getCountStatusFilter($matchingCampaigns['total'], $data['campaign_type']);
            $totalElement = empty($status) ? $data['allStatusFilter']->all : $data['allStatusFilter']->$status;
            $data['statusAuto'] = $campaignClass::SENDING_TYPE_AUTO;
        } else {
            $totalElement = $matchingCampaigns['total'];
        }

        $data['pagination']->setStatus($totalElement, $page, $campaignsPerPage);
        $data['allCampaigns'] = $matchingCampaigns['elements'];
    }

    private function getIsPendingGenerated(&$data)
    {
        $campaignClass = new CampaignClass();
        $campaingsGenerated = $campaignClass->getAllCampaignsGeneratedWaiting();
        $data['generatedPending'] = !empty($campaingsGenerated);
    }

    public function followupTrigger()
    {
        acym_setVar('layout', 'followup_trigger');

        $id = acym_getVar('int', 'id', 0);

        if (!empty($id)) {
            $followupClass = new FollowupClass();
            $followup = $followupClass->getOneById($id);
        } else {
            $followup = new \stdClass();
        }

        $data = [
            'workflowHelper' => new WorkflowHelper(),
            'followup' => $followup,
        ];

        $linkId = empty($id) ? '' : '&id='.$id;

        $this->breadcrumb[empty($id) ? acym_translation('ACYM_NEW_FOLLOW_UP') : $followup->name] = acym_completeLink('campaigns&task=edit&step=followupTrigger'.$linkId);

        parent::display($data);
    }

    public function followupCondition()
    {
        acym_setVar('layout', 'followup_condition');

        $id = acym_getVar('int', 'id', 0);
        $trigger = acym_getVar('string', 'trigger', '');

        if (!empty($id)) {
            $followupClass = new FollowupClass();
            $followup = $followupClass->getOneById($id);
        } else {
            $followup = new \stdClass();
        }

        if (empty($trigger) && empty($followup->trigger)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_LOAD_DATA'), 'error');
            $this->listing();

            return false;
        }

        $listClass = new ListClass();
        $lists = $listClass->getAllForSelect(false);

        $segmentClass = new SegmentClass();
        $segments = $segmentClass->getAllForSelect(false);

        $statusArray = [
            '' => '',
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
                    'class="acym__select"'
                ).'</span>',
            'select_status_segments' => '<span class="cell xxlarge-1 medium-2 acym__followup__condition__select__in-text">'.acym_select(
                    $statusArray,
                    'followup[condition][segments_status]',
                    !empty($followup->condition) && !empty($followup->condition['segments_status']) ? $followup->condition['segments_status'] : '',
                    'class="acym__select"'
                ).'</span>',
            'lists_subscribe_translation' => $actualTrigger == 'user_subscribe' ? 'ACYM_FOLLOW_UP_CONDITION_USER_SUBSCRIBING' : 'ACYM_FOLLOW_UP_CONDITION_USER_SUBSCRIBE',
        ];

        $linkId = empty($id) ? '&trigger='.$trigger : '&id='.$id;

        $this->breadcrumb[empty($followup->name) ? acym_translation('ACYM_NEW_FOLLOW_UP') : $followup->name] = acym_completeLink(
            'campaigns&task=edit&step=followupCondition'.$linkId
        );

        parent::display($data);
    }

    public function followupEmail()
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

        $data = [
            'workflowHelper' => new WorkflowHelper(),
            'followup' => $followup,
            'linkNewEmail' => acym_completeLink(
                'mails&task=edit&step=editEmail&type=followup&followup_id='.$id.'&return='.urlencode(acym_completeLink('campaigns&task=edit&step=followupEmail&id='.$id)),
                false,
                true
            ),
        ];

        $this->breadcrumb[empty($followup->name) ? acym_translation('ACYM_NEW_FOLLOW_UP') : $followup->name] = acym_completeLink(
            'campaigns&task=edit&step=followupEmail&id='.$followup->id
        );

        // Ask the user if he wants to add the new email to the queue for the users that already triggered the followup
        if ($followup->active === '1') {
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

    public function followupDuplicateMail()
    {
        $mailId = acym_getVar('int', 'action_mail_id', 0);
        $id = acym_getVar('int', 'id', 0);
        $followupClass = new FollowupClass();
        if (!$followupClass->duplicateMail($mailId, $id)) acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_DUPLICATE_MAIL'), 'error');

        $this->followupEmail();
    }

    public function followupDeleteMail()
    {
        $mailId = acym_getVar('int', 'action_mail_id', 0);
        $followupClass = new FollowupClass();
        if (!$followupClass->deleteMail($mailId)) acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_DELETE_MAIL'), 'error');

        $step = acym_getVar('cmd', 'step', 'followupEmail');
        $this->$step();
    }

    public function followupDraft()
    {
        $this->followupFinalize(0);
    }

    public function followupActivate()
    {
        $this->followupFinalize(1);
    }

    public function followupFinalize($status)
    {
        $followupId = acym_getVar('int', 'id', 0);
        $followupClass = new FollowupClass();
        $followup = $followupClass->getOneById($followupId);
        $followup->active = $status;
        $followupClass->save($followup);

        $this->followup();
    }

    public function saveFollowupCondition()
    {
        $id = acym_getVar('int', 'id', 0);
        $trigger = acym_getVar('string', 'trigger', '');
        $followupData = acym_getVar('array', 'followup', []);

        $followupClass = new FollowupClass();

        if (!empty($id)) {
            $followup = $followupClass->getOneById($id);
            $followup->condition = json_encode($followupData['condition']);
        } else {
            $followup = new \stdClass();
            $followup->name = '';
            $followup->display_name = '';
            $followup->creation_date = date('Y-m-d H:i:s', time());
            $followup->trigger = $trigger;
            $followup->condition = json_encode($followupData['condition']);
            $followup->active = 0;
            $followup->send_once = 1;
        }

        $followup->id = $followupClass->save($followup);
        acym_setVar('id', $followup->id);

        return $this->edit();
    }

    public function saveFollowupEmail($redirect = true)
    {
        $id = acym_getVar('int', 'id', 0);
        $followupData = acym_getVar('array', 'followup', []);
        if (empty($id) || empty($followupData)) return false;

        $followupClass = new FollowupClass();
        $followup = $followupClass->getOneById($id);

        if (empty($followup)) return false;

        foreach ($followupData as $key => $data) {
            if (!isset($followup->$key)) continue;
            $followup->$key = $data;
        }

        $followup->id = $followupClass->save($followup);
        acym_setVar('id', $followup->id);

        if ($redirect) {
            return $this->edit();
        }

        return true;
    }

    public function newEmail()
    {
        acym_setVar('layout', 'new_email');

        $listClass = new ListClass();
        $mailClass = new MailClass();
        if (acym_isAdmin()) {
            $returnUrl = urlencode(base64_encode(acym_completeLink('campaigns')));
            $data = [
                'lists' => $listClass->getAllForSelect(),
                'campaign_link' => acym_completeLink('campaigns&task=edit&step=chooseTemplate&campaign_type=now'),
                'campaign_auto_link' => acym_completeLink('campaigns&task=edit&step=chooseTemplate&campaign_type=auto'),
                'followup_link' => acym_completeLink('campaigns&task=edit&step=followupTrigger'),
                'campaign_scheduled_link' => acym_completeLink('campaigns&task=edit&step=chooseTemplate&campaign_type=scheduled'),
                'welcome_email_link' => acym_completeLink('mails&task=edit&type='.$mailClass::TYPE_WELCOME.'&list_id={dataid}&type_editor=acyEditor&return='.$returnUrl),
                'unsubscribe_email_link' => acym_completeLink('mails&task=edit&type='.$mailClass::TYPE_UNSUBSCRIBE.'&list_id={dataid}&type_editor=acyEditor&return='.$returnUrl),
            ];
        } else {
            $returnUrl = urlencode(base64_encode(acym_frontendLink('frontcampaigns')));
            $data = [
                'lists' => $listClass->getAllForSelect(true, acym_currentUserId()),
                'campaign_link' => acym_frontendLink('frontcampaigns&task=edit&step=chooseTemplate&campaign_type=now'),
                'campaign_auto_link' => acym_frontendLink('frontcampaigns&task=edit&step=chooseTemplate&campaign_type=auto'),
                'campaign_scheduled_link' => acym_frontendLink('frontcampaigns&task=edit&step=chooseTemplate&campaign_type=scheduled'),
                'welcome_email_link' => acym_frontendLink('frontmails&task=edit&type='.$mailClass::TYPE_WELCOME.'&list_id={dataid}&type_editor=acyEditor&return='.$returnUrl),
                'unsubscribe_email_link' => acym_frontendLink(
                    'frontmails&task=edit&type='.$mailClass::TYPE_UNSUBSCRIBE.'&list_id={dataid}&type_editor=acyEditor&return='.$returnUrl
                ),
            ];
        }

        parent::display($data);
    }

    private function prepareSegmentDisplay(&$data, $sendingParams)
    {
        $data['displaySegmentTab'] = empty($sendingParams) ? false : array_key_exists('segment', $sendingParams);
    }

    public function chooseTemplate()
    {
        acym_setVar('layout', 'choose_email');
        acym_setVar('step', 'chooseTemplate');
        $pagination = new PaginationHelper();

        // Get filters data
        $campaignId = acym_getVar('int', 'id', 0);
        $campaignClass = new CampaignClass();
        $searchFilter = $this->getVarFiltersListing('string', 'mailchoose_search', '');
        $tagFilter = $this->getVarFiltersListing('string', 'mailchoose_tag', '');
        $ordering = $this->getVarFiltersListing('string', 'mailchoose_ordering', 'creation_date');
        $orderingSortOrder = $this->getVarFiltersListing('string', 'mailchoose_ordering_sort_order', 'DESC');
        $campaign = $campaignClass->getOneByIdWithMail($campaignId);
        $campaignType = $this->getVarFiltersListing('string', 'campaign_type', 'now');

        $this->setTaskListing($campaignType == 'auto' ? 'campaigns_auto' : 'campaigns');

        if (!empty($campaign)) {
            $this->breadcrumb[acym_escape($campaign->name)] = '';
        } else {
            $this->breadcrumb[acym_translation('ACYM_NEW_CAMPAIGN')] = '';
        }

        // Get pagination data
        $mailsPerPage = $pagination->getListLimit();
        $page = $this->getVarFiltersListing('int', 'mailchoose_pagination_page', 1);

        $mailClass = new MailClass();
        $matchingMails = $mailClass->getMatchingElements(
            [
                'ordering' => $ordering,
                'ordering_sort_order' => $orderingSortOrder,
                'search' => $searchFilter,
                'elementsPerPage' => $mailsPerPage,
                'offset' => ($page - 1) * $mailsPerPage,
                'tag' => $tagFilter,
                'onlyStandard' => true,
                'creator_id' => $this->setFrontEndParamsForTemplateChoose(),
            ]
        );

        // Prepare the pagination
        $pagination->setStatus($matchingMails['total'], $page, $mailsPerPage);

        $tagClass = new TagClass();

        $data = [
            'allMails' => $matchingMails['elements'],
            'allTags' => $tagClass->getAllTagsByType('mail'),
            'pagination' => $pagination,
            'search' => $searchFilter,
            'tag' => $tagFilter,
            'ordering' => $ordering,
            'campaignID' => $campaignId,
            'campaign_type' => $campaignType,
        ];
        $this->prepareListingClasses($data);
        $this->prepareSegmentDisplay($data, empty($campaign->sending_params) ? false : $campaign->sending_params);


        parent::display($data);
    }

    protected function setFrontEndParamsForTemplateChoose()
    {
        return '';
    }

    private function prepareEditCampaign(&$data)
    {
        $campaignId = acym_getVar('int', 'id', 0);
        $mailId = acym_getVar('int', 'from', 0);
        $mailClass = new MailClass();
        $data['mailClass'] = $mailClass;
        // Check autosave only if mail already saved in campaign and not just selected template (from parameteer)
        $checkAutosave = empty($mailId);
        $editLink = 'campaigns&task=edit&step=editEmail';

        if (empty($campaignId)) {
            $data['mailInformation'] = new \stdClass();
            $data['mailInformation']->id = 0;
            $data['mailInformation']->name = '';
            $data['mailInformation']->tags = [];
            $data['mailInformation']->subject = '';
            $data['mailInformation']->preheader = '';
            $data['mailInformation']->body = '';
            $data['mailInformation']->settings = null;
            $data['mailInformation']->links_language = '';
            $data['mailInformation']->visible = 1;

            $editLink .= '&from='.$mailId;
        } else {
            $campaignClass = new CampaignClass();
            $data['mailInformation'] = $campaignClass->getOneByIdWithMail($campaignId);
            if (empty($mailId)) {
                $mailId = $data['mailInformation']->mail_id;
            }
            $editLink .= '&id='.$campaignId;
        }

        if ($mailId == -1 || (empty($campaignId) && empty($mailId))) {
            $data['mailInformation']->name = '';
            $data['mailInformation']->tags = [];
            $data['mailInformation']->subject = '';
            $data['mailInformation']->preheader = '';
            $data['mailInformation']->body = '';
            $data['mailInformation']->settings = null;
            $data['mailInformation']->attachments = [];
            $data['mailInformation']->stylesheet = '';
            $data['mailInformation']->headers = '';
            $data['typeEditor'] = 'acyEditor';
        } elseif (!empty($mailId)) {
            $mail = $mailClass->getOneById($mailId);
            if (!acym_isAdmin() && ACYM_CMS == 'joomla' && acym_isPluginActive('sef', 'system')) {
                $mail->body = str_replace(['url(&quot;', '&quot;)'], ["url('", "')"], $mail->body);
            }
            $data['mailInformation']->tags = $mail->tags;
            $data['mailInformation']->subject = $mail->subject;
            $data['mailInformation']->preheader = $mail->preheader;
            $data['mailInformation']->body = $mail->body;
            $data['mailInformation']->settings = $mail->settings;
            $data['mailInformation']->stylesheet = $mail->stylesheet;
            $data['mailInformation']->headers = $mail->headers;
            $data['mailInformation']->attachments = empty($mail->attachments) ? [] : json_decode($mail->attachments);
            $data['mailInformation']->links_language = $mail->links_language;

            if ($checkAutosave) {
                $data['mailInformation']->autosave = $mail->autosave;
            }
        }
        $data['mailId'] = $mailId;
        $data['campaignID'] = $data['mailInformation']->id;

        $pluginHelper = new PluginHelper();
        $pluginHelper->cleanHtml($data['mailInformation']->body);

        $editLink .= '&type_editor='.$data['typeEditor'];
        $this->breadcrumb[acym_escape(empty($data['mailInformation']->name) ? acym_translation('ACYM_NEW_CAMPAIGN') : $data['mailInformation']->name)] = acym_completeLink(
            $editLink
        );
    }

    private function prepareEditor(&$data)
    {
        $data['editor'] = new EditorHelper();
        $data['editor']->content = $data['mailInformation']->body;
        $data['editor']->autoSave = !empty($data['mailInformation']->autosave) ? $data['mailInformation']->autosave : '';
        if (!empty($data['mailInformation']->settings)) {
            $data['editor']->settings = $data['mailInformation']->settings;
        }

        if (!empty($data['mailInformation']->stylesheet)) {
            $data['editor']->stylesheet = $data['mailInformation']->stylesheet;
        }

        if (empty($data['typeEditor']) && strpos($data['editor']->content, 'acym__wysid__template') !== false) {
            $data['typeEditor'] = 'acyEditor';
        }

        $data['editor']->editor = $data['typeEditor'];
        if ($data['editor']->editor != 'acyEditor' || empty($data['editor']->editor)) {
            if (!isset($data['mailInformation']->stylesheet)) $data['mailInformation']->stylesheet = '';
            $data['needDisplayStylesheet'] = '<input type="hidden" name="editor_stylesheet" value="'.acym_escape($data['mailInformation']->stylesheet).'">';
        } else {
            $data['needDisplayStylesheet'] = '';
        }

        $data['editor']->mailId = empty($data['mailId']) ? 0 : $data['mailId'];

        if ($data['editor']->isDragAndDrop()) {
            $this->loadScripts['edit_email'][] = 'editor-wysid';
            $this->loadScripts['edit_email']['vue-applications'] = ['custom_view'];
        }
    }

    public function prepareMaxUpload(&$data)
    {
        $maxupload = ini_get('upload_max_filesize');
        $maxpost = ini_get('post_max_size');
        $data['maxupload'] = acym_bytes($maxupload) > acym_bytes($maxpost) ? $maxpost : $maxupload;
    }

    private function prepareMultilingual(&$data, $editor = true)
    {
        $data['multilingual'] = 0;

    }

    private function prepareAllMailsForMultilingual(&$data)
    {
        $mailClass = new MailClass();

        $mails = $mailClass->getMultilingualMails($data['mailId']);

        if (empty($mails)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_LOAD_MULTILINGUAL_MAILS'), 'error');

            return;
        }

        foreach ($mails as $key => $oneMail) {
            $mails[$key] = $this->prepareMailDataSummary($data, $oneMail->id);
        }

        $data['multilingual_mails'] = $mails;
    }

    public function editEmail()
    {
        acym_setVar('layout', 'edit_email');
        acym_setVar('numberattachment', '0');
        acym_setVar('step', 'editEmail');

        $tagClass = new TagClass();

        $data = [
            'containerClass' => $this->stepContainerClass,
            'social_icons' => $this->config->get('social_icons', '{}'),
            'allTags' => $tagClass->getAllTagsByType('mail'),
            'campaign_type' => acym_getVar('string', 'campaign_type', 'now'),
            'typeEditor' => acym_getVar('string', 'type_editor', ''),
            'uploadFileType' => new UploadfileType(),
        ];

        $this->prepareEditCampaign($data);
        $this->prepareEditor($data);
        $this->prepareMaxUpload($data);
        $this->prepareMultilingual($data);
        $this->prepareListingClasses($data);
        $this->prepareSegmentDisplay($data, empty($data['mailInformation']->sending_params) ? false : $data['mailInformation']->sending_params);

        $data['before-save'] = $data['editor']->editor != 'acyEditor' ? '' : 'acym-data-before="acym_editorWysidMultilingual.storeCurrentValues(true);"';

        $data['before-save'] = $data['editor']->editor != 'acyEditor' ? '' : 'acym-data-before="acym_editorWysidMultilingual.storeCurrentValues(true);"';

        parent::display($data);
    }

    public function recipients()
    {
        acym_setVar('layout', 'recipients');
        $campaignId = acym_getVar('int', 'id');
        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();
        acym_setVar('step', 'recipients');

        if (!empty($campaignId)) {
            $currentCampaign = $campaignClass->getOneByIdWithMail($campaignId);
            $this->breadcrumb[acym_escape($currentCampaign->name)] = acym_completeLink('campaigns&task=edit&step=recipients&id='.$campaignId);
        } else {
            $currentCampaign = new \stdClass();
            $this->breadcrumb[acym_translation('ACYM_NEW_CAMPAIGN')] = acym_completeLink('campaigns&task=edit&step=recipients');
        }

        $campaign = [
            'campaignInformation' => $campaignId,
            'currentCampaign' => $currentCampaign,
            'containerClass' => $this->stepContainerClass,
            'entitySelectHelper' => new EntitySelectHelper(),
        ];

        // Get saved data if edition of a campaign
        if (!empty($currentCampaign->mail_id)) {
            $campaignLists = $mailClass->getAllListsByMailId($currentCampaign->mail_id);
            $campaign['campaignListsId'] = array_keys($campaignLists);
            acym_arrayToInteger($campaign['campaignListsId']);
            $campaign['campaignListsSelected'] = json_encode($campaign['campaignListsId']);
        }
        $this->prepareListingClasses($campaign);
        $this->prepareSegmentDisplay($campaign, $campaign['currentCampaign']->sending_params);

        parent::display($campaign);
    }

    public function segment()
    {
        acym_setVar('layout', 'segment');
        acym_setVar('step', 'segment');

        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();
        $campaignId = acym_getVar('int', 'id');

        if (empty($campaignId)) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_NOT_FOUND'), 'error');
            $this->listing();

            return;
        }

        $campaign = $campaignClass->getOneById($campaignId);

        $mail = $mailClass->getOneById($campaign->mail_id);
        $data = [
            'campaign' => $campaign,
            'containerClass' => $this->stepContainerClass,
            'displaySegmentTab' => true,
            'workflowHelper' => new WorkflowHelper(),
        ];


        $this->breadcrumb[acym_escape($mail->name)] = acym_completeLink(acym_completeLink('campaigns&task=edit&step=recipients&id='.$campaign->id));
        parent::display($data);
    }

    public function sendSettings()
    {
        acym_setVar('layout', 'send_settings');
        acym_setVar('step', 'sendSettings');
        $campaignId = acym_getVar('int', 'id');
        $campaignClass = new CampaignClass();
        $campaignInformation = empty($campaignId) ? null : $campaignClass->getOneById($campaignId);

        if (is_null($campaignInformation)) {
            acym_enqueueMessage(acym_translation('ACYM_CANT_GET_CAMPAIGN_INFORMATION'), 'error');
            $this->listing();

            return;
        }

        //To know if we create or modify the campaign
        $from = acym_getVar('string', 'from');

        $campaignClass = new CampaignClass();
        $currentCampaign = $campaignClass->getOneByIdWithMail($campaignId);
        $this->breadcrumb[acym_escape($currentCampaign->name)] = acym_completeLink('campaigns&task=edit&step=sendSettings&id='.$campaignId);

        if (!empty($currentCampaign->sent) && empty($currentCampaign->active)) {
            $currentCampaign->sending_date = '';
        }

        $campaign = [];

        $campaign['currentCampaign'] = $currentCampaign;
        $campaign['from'] = $from;
        $campaign['suggestedDate'] = acym_date('1534771620', 'j M Y H:i');
        $campaign['senderInformations'] = new \stdClass();
        $campaign['config_values'] = new \stdClass();
        $campaign['currentCampaign']->send_now = $currentCampaign->sending_type == $campaignClass::SENDING_TYPE_NOW;
        $campaign['currentCampaign']->send_scheduled = $currentCampaign->sending_type == $campaignClass::SENDING_TYPE_SCHEDULED;
        $campaign['currentCampaign']->send_auto = $currentCampaign->sending_type == $campaignClass::SENDING_TYPE_AUTO;
        $campaign['campaignClass'] = $campaignClass;

        // Handle special emails
        $campaign['currentCampaign']->send_specific = [];
        if (!in_array($currentCampaign->sending_type, $campaignClass::SENDING_TYPES)) {
            acym_trigger(
                'getCampaignSpecificSendSettings',
                [$currentCampaign->sending_type, $currentCampaign->sending_params, &$campaign['currentCampaign']->send_specific]
            );
        }

        $campaign['senderInformations']->from_name = empty($currentCampaign->from_name) ? '' : $currentCampaign->from_name;
        $campaign['senderInformations']->from_email = empty($currentCampaign->from_email) ? '' : $currentCampaign->from_email;
        $campaign['senderInformations']->reply_to_name = empty($currentCampaign->reply_to_name) ? '' : $currentCampaign->reply_to_name;
        $campaign['senderInformations']->reply_to_email = empty($currentCampaign->reply_to_email) ? '' : $currentCampaign->reply_to_email;

        $campaign['config_values']->from_name = $this->config->get('from_name', '');
        $campaign['config_values']->from_email = $this->config->get('from_email', '');
        $campaign['config_values']->reply_to_name = $this->config->get('replyto_name', '');
        $campaign['config_values']->reply_to_email = $this->config->get('replyto_email', '');

        $triggers = [];

        acym_trigger('onAcymDeclareTriggers', [&$triggers, &$currentCampaign->sending_params], 'plgAcymTime');
        $triggers = $triggers['classic'];

        $campaign['triggers_select'] = [];
        $campaign['triggers_display'] = [];

        foreach ($triggers as $key => $trigger) {
            $campaign['triggers_select'][$key] = $trigger->name;
            $campaign['triggers_display'][$key] = $trigger->option;
        }

        if (!empty($campaign['currentCampaign']->sending_params) && empty($campaign['currentCampaign']->sending_params['trigger_type'])) {
            foreach (array_keys($triggers) as $oneTrigger) {
                if (!empty($campaign['currentCampaign']->sending_params[$oneTrigger])) $campaign['currentCampaign']->sending_params['trigger_type'] = $oneTrigger;
            }
        }

        $campaign['containerClass'] = $this->stepContainerClass;
        $campaign['langChoice'] = acym_isMultilingual() ? '' : acym_languageOption($campaign['currentCampaign']->links_language, 'senderInformation[links_language]');
        $this->prepareListingClasses($campaign);
        $this->prepareSegmentDisplay($campaign, $campaign['currentCampaign']->sending_params);
        $this->prepareMultilingualOption($campaign);

        return parent::display($campaign);
    }

    public function saveEditEmail($ajax = false)
    {
        acym_checkToken();

        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();
        $formData = acym_getVar('array', 'mail', []);
        $multilingual = acym_getVar('array', 'multilingual', [], 'REQUEST', ACYM_ALLOWRAW);
        $allowedFields = acym_getColumns('mail');
        $campaignId = acym_getVar('int', 'id', 0);
        $campaignType = acym_getVar('string', 'campaign_type', 'now');

        $types = [
            'now' => $campaignClass::SENDING_TYPE_NOW,
            'auto' => $campaignClass::SENDING_TYPE_AUTO,
            'scheduled' => $campaignClass::SENDING_TYPE_SCHEDULED,
        ];

        acym_trigger('getCampaignTypes', [&$types]);

        if (empty($campaignId)) {
            $mail = new \stdClass();
            $mail->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
            $mail->type = $mailClass::TYPE_STANDARD;
            $mail->library = 0;

            $campaign = new \stdClass();
            $campaign->draft = 1;
            $campaign->active = 0;
            $campaign->sending_type = $types[$campaignType];
            $campaign->sent = 0;
            $campaign->sending_params = [];
        } else {
            $campaign = $campaignClass->getOneById($campaignId);
            $mail = $mailClass->getOneById($campaign->mail_id);
        }
        $campaign->visible = acym_getVar('int', 'visible', 1);

        // Get the name and subject
        foreach ($formData as $name => $data) {
            if (!in_array($name, $allowedFields)) {
                continue;
            }
            $mail->{acym_secureDBColumn($name)} = $data;
        }

        // Name is mandatory. If empty copy subject (can't be an empty field)
        if (empty($mail->name)) $mail->name = empty($mail->subject) ? acym_translation('ACYM_CAMPAIGN_NAME') : $mail->subject;

        if (empty($mail->subject)) $mail->subject = acym_translation('ACYM_EMAIL_SUBJECT');

        $mail->body = acym_getVar('string', 'editor_content', '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->settings = acym_getVar('string', 'editor_settings', '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->stylesheet = acym_getVar('string', 'editor_stylesheet', '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->headers = acym_getVar('string', 'editor_headers', '', 'REQUEST', ACYM_ALLOWRAW);
        $mail->drag_editor = strpos($mail->body, 'acym__wysid__template') === false ? 0 : 1;
        $mail->attachments = empty($mail->attachments) ? [] : json_decode($mail->attachments, true);

        $mail->tags = acym_getVar('array', 'template_tags', []);

        $mailController = new MailsController();
        $mailController->setAttachmentToMail($mail);

        if (!empty($multilingual)) {
            if (!empty($multilingual['main']['subject'])) $mail->subject = $multilingual['main']['subject'];
            if (!empty($multilingual['main']['preview'])) $mail->preheader = $multilingual['main']['preview'];
            if (!empty($multilingual['main']['content'])) $mail->body = $multilingual['main']['content'];
            if (!empty($multilingual['main']['content'])) $mail->settings = $multilingual['main']['settings'];
            if (!empty($multilingual['main']['content'])) $mail->stylesheet = $multilingual['main']['stylesheet'];
            $mail->links_language = $this->config->get('multilingual_default');
            unset($multilingual['main']);
        }

        if ($mailID = $mailClass->save($mail)) {
            if (acym_getVar('string', 'nextstep', '') == 'listing') {
                acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');
            }
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
            if (!empty($mailClass->errors)) {
                acym_enqueueMessage($mailClass->errors, 'error');
            }

            if (!$ajax) {
                return $this->listing();
            } else {
                return false;
            }
        }

        if (!empty($multilingual)) {
            foreach ($multilingual as $langCode => $translation) {
                if (empty($translation['subject'])) {
                    $mailClass->delete($mailClass->getTranslationId($mailID, $langCode));
                    continue;
                }

                unset($mail->id);
                $translationId = $mailClass->getTranslationId($mailID, $langCode);
                if (!empty($translationId)) $mail->id = $translationId;

                $mail->subject = $translation['subject'];
                $mail->preheader = $translation['preview'];
                $mail->body = $translation['content'];
                $mail->links_language = $langCode;
                $mail->language = $langCode;
                $mail->parent_id = $mailID;
                $mail->settings = $translation['settings'];
                $mail->stylesheet = $translation['stylesheet'];

                $mailClass->save($mail);
            }
        }

        $campaign->mail_id = $mailID;
        $campaign->id = $campaignClass->save($campaign);

        if ($ajax) {
            return $campaign->id;
        }

        acym_setVar('id', $campaign->id);

        return $this->edit();
    }

    public function saveRecipients()
    {
        $allLists = json_decode(acym_getVar('string', 'acym__entity_select__selected'));
        $allListsUnselected = json_decode(acym_getVar('string', 'acym__entity_select__unselected'));
        $campaignId = acym_getVar('int', 'id');
        $addSegmentStep = acym_getVar('int', 'add_segment_step');

        $campaignClass = new CampaignClass();
        $currentCampaign = $campaignClass->getOneByIdWithMail($campaignId);

        if ($currentCampaign->sent && !$currentCampaign->active) {
            $mailStatClass = new MailStatClass();
            $listClass = new ListClass();
            $mailStat = $mailStatClass->getOneRowByMailId($currentCampaign->mail_id);
            $mailStat->total_subscribers = $listClass->getTotalSubCount($allLists);
            $mailStatClass->save($mailStat);
        } elseif (!empty($currentCampaign->mail_id)) {
            $campaignClass->manageListsToCampaign($allLists, $currentCampaign->mail_id, $allListsUnselected);
            if (acym_getVar('string', 'nextstep', '') == 'listing') {
                acym_enqueueMessage(acym_translationSprintf('ACYM_LIST_IS_SAVED', $currentCampaign->name), 'success');
            }
        }

        if (!empty($addSegmentStep)) {
            $currentCampaign->sending_params['segment'] = [];
            $campaignClass->save($currentCampaign);
            acym_setVar('nextstep', 'segment');
            $this->segment();

            return;
        }

        if (isset($currentCampaign->sending_params['segment'])) unset($currentCampaign->sending_params['segment']);
        $campaignClass->save($currentCampaign);

        $this->edit();
    }

    public function saveSegment()
    {
        $segmentSelected = acym_getVar('int', 'segment_selected', 0);
        $filters = acym_getVar('array', 'acym_action', []);
        $campaignId = acym_getVar('int', 'id', 0);

        if (empty($campaignId)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
            $this->listing();

            return;
        }

        $segmentSavedId = acym_getVar('int', 'saved_segment_id', 0);

        if (!empty($segmentSavedId)) {
            $segmentSelected = $segmentSavedId;
        }

        $campaignClass = new CampaignClass();
        $campaign = $campaignClass->getOneById($campaignId);

        if (empty($campaign)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
            $this->listing();

            return;
        }

        if (empty($segmentSelected) && empty($filters)) {
            $campaign->sending_params['segment'] = [];
        } elseif (!empty($segmentSelected)) {
            $campaign->sending_params['segment'] = ['segment_id' => $segmentSelected];
        } else {
            $campaign->sending_params['segment'] = ['filters' => (object)$filters['filters']];
        }

        $campaignClass->save($campaign);

        $this->edit();
    }

    public function saveSendSettings()
    {
        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();
        $campaignId = acym_getVar('int', 'id');
        $senderInformation = acym_getVar('', 'senderInformation');
        $sendingDate = acym_getVar('string', 'sendingDate');
        $sendingType = acym_getVar('string', 'sending_type', $campaignClass::SENDING_TYPE_NOW);
        $sendingParams = acym_getVar('array', 'sending_params', []);
        $specificSendingParams = [];
        $isScheduled = $campaignClass::SENDING_TYPE_SCHEDULED == $sendingType;

        $currentCampaign = $campaignClass->getOneById($campaignId);

        if (empty($currentCampaign)) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_DOESNT_EXISTS'), 'error');

            $this->listing();

            return;
        }

        if ($campaignClass::SENDING_TYPE_AUTO == $sendingType) {
            $triggerType = acym_getVar('string', 'acym_triggers', '');
            if (empty($triggerType)) {
                acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
                $this->listing();

                return;
            }

            $needConfirmToSend = acym_getVar('int', 'need_confirm', 0);

            $specificSendingParams = [
                $triggerType => acym_getVar('array', $triggerType, ''),
                'need_confirm_to_send' => $needConfirmToSend,
                'trigger_type' => $triggerType,
            ];

            $startDate = acym_getVar('string', 'start_date', 0);
            if (!empty($startDate)) {
                $specificSendingParams['start_date'] = acym_date(acym_getTime($startDate), 'Y-m-d H:i:s', false);
            }

            if (!empty($currentCampaign->sending_params['number_generated'])) {
                $specificSendingParams['number_generated'] = $currentCampaign->sending_params['number_generated'];
            }

            // Clean old settings saved in the campaign
            $triggers = [];
            $fakeSettings = [];
            acym_trigger('onAcymDeclareTriggers', [&$triggers, &$fakeSettings], 'plgAcymTime');
            $settings = array_merge(array_keys($triggers['classic']), ['trigger_type', 'need_confirm_to_send']);
            foreach ($settings as $oneSetting) {
                unset($currentCampaign->sending_params[$oneSetting]);
            }
        }

        // Handle special emails
        if (!in_array($sendingType, $campaignClass::SENDING_TYPES)) {
            $specialSendings = [];
            acym_trigger('saveCampaignSpecificSendSettings', [$currentCampaign->sending_type, &$specialSendings]);
            if (!empty($specialSendings)) {
                $specificSendingParams = $specialSendings[0];
            }
        }

        if (!empty($currentCampaign->mail_id)) {
            $currentMail = $mailClass->getOneById($currentCampaign->mail_id);
        }

        $currentCampaign->sending_type = $sendingType;
        if (empty($currentCampaign->sending_params)) $currentCampaign->sending_params = [];
        $currentCampaign->sending_params = array_merge($currentCampaign->sending_params, $sendingParams, $specificSendingParams);
        $currentCampaign->sending_date = null;

        if (empty($currentMail) || empty($senderInformation)) {
            $this->listing();

            return;
        }

        $currentMail->from_name = $senderInformation['from_name'];
        $currentMail->from_email = $senderInformation['from_email'];
        $currentMail->reply_to_name = $senderInformation['reply_to_name'];
        $currentMail->reply_to_email = $senderInformation['reply_to_email'];
        $currentMail->bcc = $senderInformation['bcc'];
        $currentMail->tracking = $senderInformation['tracking'];
        $currentMail->translation = empty($senderInformation['translation']) ? '' : $senderInformation['translation'];
        if (isset($senderInformation['links_language'])) $currentMail->links_language = $senderInformation['links_language'];

        $mailClass->save($currentMail);

        if ($isScheduled && !empty($sendingDate)) {
            $currentCampaign->sending_date = acym_date(acym_getTime($sendingDate), 'Y-m-d H:i:s', false);
            if ($currentCampaign->sending_date < acym_date('now', 'Y-m-d H:i:s', false)) acym_enqueueMessage(acym_translation('ACYM_BE_CAREFUL_SENDING_DATE_IN_PAST'), 'warning');
        }

        if ($campaignClass->save($currentCampaign)) {
            if (acym_getVar('string', 'nextstep', '') == 'listing') {
                acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');
            }
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
            if (!empty($campaignClass->errors)) {
                acym_enqueueMessage($campaignClass->errors, 'error');
            }

            $this->listing();

            return;
        }

        $this->edit();
    }

    public function duplicate()
    {
        //We get the id of campaign checked
        $campaignsSelected = acym_getVar('int', 'elements_checked');

        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();
        $campaignId = 0;

        foreach ($campaignsSelected as $campaignSelected) {
            //we get the campaign
            $campaign = $campaignClass->getOneById($campaignSelected);

            //remove id and set to draft and not sent
            unset($campaign->id);
            unset($campaign->sending_date);
            $campaign->draft = 1;
            $campaign->sent = 0;
            $campaign->active = 0;
            if (!empty($campaign->sending_params['resendTarget'])) {
                unset($campaign->sending_params['resendTarget']);
            }

            //We get the mail to duplicate it
            $mail = $mailClass->getOneById($campaign->mail_id);
            $oldMailId = $mail->id;
            unset($mail->id);
            $mail->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
            $mail->name .= '_copy';
            $idNewMail = $mailClass->save($mail);

            $translations = $mailClass->getTranslationsById($oldMailId, true);
            foreach ($translations as $oneTranslation) {
                unset($oneTranslation->id);
                $oneTranslation->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
                $oneTranslation->name .= '_copy';
                $oneTranslation->parent_id = $idNewMail;
                $mailClass->save($oneTranslation);
            }

            //we set the new mail id and save campaign
            $campaign->mail_id = $idNewMail;
            $campaignId = $campaignClass->save($campaign);

            //We get the lists
            $allLists = $campaignClass->getListsForCampaign($oldMailId);

            $campaignClass->manageListsToCampaign($allLists, $idNewMail);
        }

        acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_DUPLICATED_SUCCESS'), 'success');

        if (count($campaignsSelected) == 1 && acym_getVar('string', 'step', '') == 'summary') {
            acym_setVar('id', $campaignId);
            $this->editEmail();
        } else {
            $this->listing();
        }
    }

    /**
     * Needed for the steps system
     */
    public function saveSummary()
    {
        $this->edit();
    }

    public function summary()
    {
        acym_setVar('step', 'summary');
        acym_setVar('layout', 'summary');

        $data = [
            'mailClass' => new MailClass(),
            'campaignClass' => $this->currentClass,
            'containerClass' => $this->stepContainerClass,
        ];

        $this->prepareCurrentUserSummary($data);
        if (!$this->prepareCampaignSummary($data)) {
            acym_enqueueMessage(acym_translation('ACYM_CANT_GET_CAMPAIGN_INFORMATION'), 'error');
            $this->listing();

            return;
        }
        $data['mailInformation'] = $this->prepareMailDataSummary($data, $data['campaignInformation']->mail_id);
        $this->prepareReceiversSummary($data);
        $this->prepareMultilingual($data, false);
        $this->prepareAllMailsForMultilingual($data);
        $this->prepareListingClasses($data);
        $this->prepareSegmentData($data);
        $this->prepareSegmentDisplay($data, $data['campaignInformation']->sending_params);

        $this->breadcrumb[$data['campaignInformation']->name] = acym_completeLink('campaigns&task=edit&step=summary&id='.$data['campaignInformation']->id);
        parent::display($data);
    }

    private function prepareSegmentData(&$data)
    {
        if (empty($data['campaignInformation']->sending_params['segment'])) return;

        $segmentParams = $data['campaignInformation']->sending_params['segment'];

        $segmentController = new SegmentsController();

        if (!empty($segmentParams['segment_id'])) {
            $segmentClass = new SegmentClass();
            $segment = $segmentClass->getOneById($segmentParams['segment_id']);

            $data['segment'] = [
                'name' => $segment->name,
                'count' => $segmentController->countSegmentById($segment->id, $data['listsIds'], false),
            ];
        } else {
            $data['segment'] = [
                'name' => acym_translation('ACYM_YOUR_CUSTOM_SEGMENT'),
                'count' => $segmentController->countSegmentByParams($segmentParams, $data['listsIds']),
            ];
        }
    }

    protected function prepareCurrentUserSummary(&$data)
    {
        $userClass = new UserClass();
        $currentUserEmail = acym_currentUserEmail();
        $data['receiver'] = $userClass->getOneByEmail($currentUserEmail);
        if (empty($data['receiver'])) {
            $receiver = new \stdClass();
            $receiver->email = $currentUserEmail;
            $newID = $userClass->save($receiver);
            $data['receiver'] = $userClass->getOneById($newID);
        }
    }

    protected function prepareCampaignSummary(&$data)
    {
        $campaignId = acym_getVar('int', 'id');
        $campaign = empty($campaignId) ? null : $this->currentClass->getOneByIdWithMail($campaignId);
        if (is_null($campaign)) return false;

        $campaign->isAuto = $campaign->sending_type == $this->currentClass->getConstAuto();

        $startDate = '';
        if ($campaign->isAuto) {
            $textToDisplay = new \stdClass();
            $textToDisplay->triggers = $campaign->sending_params;
            acym_trigger('onAcymDeclareSummary_triggers', [&$textToDisplay], 'plgAcymTime');
            $textToDisplay = $textToDisplay->triggers;
            if (!empty($campaign->sending_params['start_date'])) {
                $startDate = $campaign->sending_params['start_date'];
            }
        }

        $data['automatic'] = [
            'isAuto' => $campaign->isAuto,
            'text' => empty($textToDisplay) ? '' : acym_translation('ACYM_THIS_WILL_GENERATE_CAMPAIGN_AUTOMATICALLY').' '.acym_strtolower($textToDisplay[key($textToDisplay)]),
            'startDate' => $startDate,
        ];
        $data['campaignInformation'] = $campaign;
        $data['mailId'] = $campaign->mail_id;

        return true;
    }

    protected function prepareMailDataSummary($data, $mailId)
    {
        $mailData = $data['mailClass']->getOneById($mailId);
        $mailData->from_name = empty($mailData->from_name) ? $this->config->get('from_name') : $mailData->from_name;
        $mailData->from_email = empty($mailData->from_email) ? $this->config->get('from_email') : $mailData->from_email;

        $useFromInReply = $this->config->get('from_as_replyto');
        $replytoName = $this->config->get('replyto_name');
        $replytoEmail = $this->config->get('replyto_email');

        if (!empty($mailData->reply_to_name)) {
            $replytoName = $mailData->reply_to_name;
        } elseif ($useFromInReply != 0 || empty($replytoName)) {
            $replytoName = $this->config->get('from_name');
        }

        if (!empty($mailData->reply_to_email)) {
            $replytoEmail = $mailData->reply_to_email;
        } elseif ($useFromInReply != 0 || empty($replytoEmail)) {
            $replytoEmail = $this->config->get('from_email');
        }

        $mailData->reply_to_name = $replytoName;
        $mailData->reply_to_email = $replytoEmail;

        acym_trigger('replaceContent', [&$mailData, false]);
        acym_trigger('replaceUserInformation', [&$mailData, &$data['receiver'], false]);

        $editorHelper = new EditorHelper();
        $mailData->settings = json_decode($mailData->settings, true);
        $mailData->stylesheet .= $editorHelper->getSettingsStyle($mailData->settings);

        return $mailData;
    }

    protected function prepareReceiversSummary(&$data)
    {
        $nbSubscribers = 0;
        $campaignLists = $data['mailClass']->getAllListsWithCountSubscribersByMailIds([$data['campaignInformation']->mail_id]);
        $listsIds = [];

        if (!empty($campaignLists)) {
            foreach ($campaignLists as $oneList) {
                $listsIds[] = $oneList->list_id;
            }
            if (empty($data['campaignInformation']->sending_params)) {
                $listClass = new ListClass();
                $nbSubscribers = $listClass->getSubscribersCount($listsIds);
            } else {
                $campaignClass = new CampaignClass();
                $nbSubscribers = $campaignClass->countUsersCampaign($data['campaignInformation']->id);
            }
        }

        $data['listsReceiver'] = $campaignLists;
        $data['listsIds'] = $listsIds;
        $data['nbSubscribers'] = $nbSubscribers;

        if (!empty($data['campaignInformation']->sent) && !empty($data['campaignInformation']->active)) {
            $queueClass = new QueueClass();
            $data['mailInformation']->sending_params = $data['campaignInformation']->sending_params;
            $automationHelper = $queueClass->getMailReceivers($data['mailInformation'], true);
            $data['receiversNew'] = acym_loadResult($automationHelper->getQuery(['COUNT(`user`.id)']));

            $automationHelper = $queueClass->getMailReceivers($data['mailInformation'], false);
            $data['receiversAll'] = acym_loadResult($automationHelper->getQuery(['COUNT(`user`.id)']));
        }
    }

    public function unpause_campaign()
    {
        $id = acym_getVar('int', 'id', 0);
        if (empty($id)) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_NOT_FOUND'), 'error');
            $this->listing();

            return;
        }

        acym_redirect(acym_completeLink('queue', false, true).'&task=playPauseSending&acym__queue__play_pause__active__new_value=1&acym__queue__play_pause__campaign_id='.$id);
    }

    private function _stopAction($action)
    {
        acym_checkToken();

        $campaignID = acym_getVar('int', $action);
        $campaignClass = new CampaignClass();

        if (!empty($campaignID)) {
            $campaign = new \stdClass();
            $campaign->id = $campaignID;
            $campaign->active = 0;
            $campaign->draft = 1;

            $campaignId = $campaignClass->save($campaign);
            if (empty($campaignId)) {
                acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_CANT_BE_SAVED'), 'error');
            } else {
                acym_enqueueMessage(acym_translation('ACYM_SUCCESSFULLY_SAVED'), 'success');
            }
        } else {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_CANT_BE_SAVED'), 'error');
        }
        $this->listing();
    }

    public function stopSending()
    {
        $this->_stopAction('stopSendingCampaignId');
    }

    public function stopScheduled()
    {
        $this->_stopAction('stopScheduledCampaignId');
    }

    public function confirmCampaign()
    {
        $campaignId = acym_getVar('int', 'id');
        $campaignSendingDate = acym_getVar('string', 'sending_date');
        $resendTarget = acym_getVar('cmd', 'resend_target', '');
        $campaignClass = new CampaignClass();

        $campaign = new \stdClass();
        $campaign->id = $campaignId;
        $campaign->draft = 0;
        $campaign->active = 1;
        $campaign->sent = 0;

        if (!empty($resendTarget)) {
            $currentCampaign = $campaignClass->getOneById($campaignId);
            $currentCampaign->sending_params['resendTarget'] = $resendTarget;
            $campaign->sending_params = $currentCampaign->sending_params;
        }

        $resultSave = $campaignClass->save($campaign);

        if ($resultSave) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_CONFIRMED_CAMPAIGN', acym_date($campaignSendingDate, 'j F Y H:i')), 'success');
        } else {
            acym_enqueueMessage(acym_translation('ACYM_CANT_CONFIRM_CAMPAIGN').' : '.end($campaignClass->errors), 'error');
        }

        $this->listing();
    }

    public function activeAutoCampaign()
    {
        $campaignId = acym_getVar('int', 'id');
        $campaignClass = new CampaignClass();

        $campaign = new \stdClass();
        $campaign->id = $campaignId;
        $campaign->draft = 0;
        $campaign->active = 1;
        $campaign->sent = 0;

        $resultSave = $campaignClass->save($campaign);

        if ($resultSave) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_IS_ACTIVE'), 'success');
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
        }

        $this->listing();
    }

    public function saveAsDraftCampaign()
    {
        $campaignId = acym_getVar('int', 'id');
        $campaignClass = new CampaignClass();

        $campaign = new \stdClass();
        $campaign->id = $campaignId;
        $campaign->draft = 1;
        $campaign->active = 0;

        $resultSave = $campaignClass->save($campaign);

        if ($resultSave) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_SUCCESSFULLY_SAVE_AS_DRAFT'), 'success');
        } else {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_CANT_BE_SAVED').' : '.end($campaignClass->errors), 'error');
        }

        $this->listing();
    }

    public function toggleActivateColumnCampaign()
    {

        $campaignId = acym_getVar('int', 'id');
        $campaignClass = new CampaignClass();

        $campaign = $campaignClass->getOneById($campaignId);
        if (empty($campaign)) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_CANT_BE_SAVED').' : '.end($campaignClass->errors), 'error');
            $this->listing();

            return;
        }

        $campaign->active = empty($campaign->active) ? 1 : 0;

        $resultSave = $campaignClass->save($campaign);

        if ($resultSave) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_SUCCESSFULLY_SAVE_AS_DRAFT'), 'success');
        } else {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_CANT_BE_SAVED').' : '.end($campaignClass->errors), 'error');
        }

        $this->listing();
    }

    /**
     * @return mixed
     */
    public function getAll()
    {
        $campaignClass = new CampaignClass();
        $listClass = new ListClass();

        $allCampaigns = $campaignClass->getAll();

        foreach ($allCampaigns as $campaign) {
            $campaign->tags = $campaignClass->getAllTagsByCampaignId($campaign->id);
            $lists = $campaignClass->getAllListsByCampaignId($campaign->id)[0]->name;
            if (!empty($lists)) {
                $campaign->lists = $campaignClass->getAllListsByCampaignId($campaign->id);
                $campaign->subscribers = 0;
                foreach ($campaign->lists as $list) {
                    $campaign->subscribers += $listClass->getSubscribersCountByListId($list->id);
                }
            }

            $campaign->trigger = $campaignClass->getAllTriggerByCampaignId($campaign->id);
            if (empty($campaign->trigger->automation_id)) {
                $campaign->trigger = null;
            }

            $campaign->sending = 0;
        }

        return $allCampaigns;
    }

    /**
     * @param $allCampaigns
     * @param $type
     *
     * @return stdClass
     */
    public function getCountStatusFilter($allCampaigns, $type)
    {
        $campaignClass = new CampaignClass();
        $allCountStatus = new \stdClass();

        if ($type == 'campaigns') {
            $this->getCountStatusFilterCampaigns($allCampaigns, $allCountStatus, $campaignClass);
        } else {
            $this->getCountStatusFilterCampaignsAuto($allCampaigns, $allCountStatus, $campaignClass);
        }

        return $allCountStatus;
    }

    private function getCountStatusFilterCampaigns($allCampaigns, &$allCountStatus, &$campaignClass)
    {
        $allCountStatus->all = 0;
        $allCountStatus->scheduled = 0;
        $allCountStatus->sent = 0;
        $allCountStatus->draft = 0;

        foreach ($allCampaigns as $campaign) {
            if (empty($campaign->parent_id)) {
                $allCountStatus->all += 1;
                if ($campaignClass::SENDING_TYPE_SCHEDULED == $campaign->sending_type) $allCountStatus->scheduled += 1;
                $allCountStatus->sent += $campaign->sent;
                $allCountStatus->draft += $campaign->draft;
            }
        }
    }

    private function getCountStatusFilterCampaignsAuto($allCampaigns, &$allCountStatus, &$campaignClass)
    {
        $allCountStatus->all = 0;
        $allCountStatus->generated = 0;

        if (!empty($allCampaigns)) $allCountStatus->all = count($allCampaigns);

        $generatedCampaigns = $this->currentClass->getAllCampaignsGenerated();
        if (!empty($generatedCampaigns)) $allCountStatus->generated = count($generatedCampaigns);
    }

    public function cancelDashboardAndGetCampaignsAjax()
    {
        $campaignId = acym_getVar('int', 'id');
        $campaignClass = new CampaignClass();

        if (!empty($campaignId)) {
            $campaign = new \stdClass();
            $campaign->id = $campaignId;
            $campaign->active = 0;
            $campaign->draft = 1;

            $campaignId = $campaignClass->save($campaign);
            if (empty($campaignId)) {
                echo 'error';
                exit;
            }

            $campaigns = $campaignClass->getCampaignForDashboard();

            if (empty($campaigns)) {
                echo '<h1 class="acym__dashboard__active-campaigns__none">'.acym_translation('ACYM_NONE_OF_YOUR_CAMPAIGN_SCHEDULED_GO_SCHEDULE_ONE').'</h1>';
                exit;
            }

            $echo = '';

            foreach ($campaigns as $campaign) {
                $echo .= '<div class="cell grid-x acym__dashboard__active-campaigns__one-campaign">
                        <a class="acym__dashboard__active-campaigns__one-campaign__title medium-4 small-12" href="'.acym_completeLink(
                        'campaigns&task=edit&step=editEmail&id='
                    ).$campaign->id.'">'.$campaign->name.'</a>
                        <div class="acym__dashboard__active-campaigns__one-campaign__state medium-2 small-12 acym__background-color__blue text-center"><span>'.acym_translation(
                        'ACYM_SCHEDULED'
                    ).' : '.acym_getDate($campaign->sending_date, 'ACYM_DATE_FORMAT_LC3').'</span></div>
                        <div class="medium-6 small-12"><p id="'.$campaign->id.'" class="acym__dashboard__active-campaigns__one-campaign__action acym__color__dark-gray">'.acym_translation(
                        'ACYM_CANCEL_SCHEDULING'
                    ).'</p></div>
                    </div>
                    <hr class="cell small-12">';
            }
            echo $echo;
            exit;
        } else {
            echo 'error';
            exit;
        }
    }

    public function addQueue()
    {
        acym_checkToken();

        $campaignID = acym_getVar('int', 'id', 0);

        if (empty($campaignID)) {
            acym_enqueueMessage(acym_translation('ACYM_CAMPAIGN_NOT_FOUND'), 'error');
        } else {
            $campaignClass = new CampaignClass();
            $campaign = $campaignClass->getOneByIdWithMail($campaignID);

            $resendTarget = acym_getVar('cmd', 'resend_target', '');
            if (!empty($resendTarget)) {
                $currentCampaign = $campaignClass->getOneById($campaignID);
                $currentCampaign->sending_params['resendTarget'] = $resendTarget;
                $campaignClass->save($currentCampaign);
            }

            $status = $campaignClass->send($campaignID);

            if ($status) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_CAMPAIGN_ADDED_TO_QUEUE', $campaign->name), 'info');
            } else {
                if (empty($campaignClass->errors)) {
                    acym_enqueueMessage(acym_translationSprintf('ACYM_ERROR_QUEUE_CAMPAIGN', $campaign->name), 'error');
                } else {
                    acym_enqueueMessage($campaignClass->errors, 'error');
                }
            }
        }

        $this->_redirectAfterQueued();
    }

    private function _redirectAfterQueued()
    {
        if (acym_isAdmin() && (!acym_level(ACYM_ESSENTIAL) || $this->config->get('cron_last', 0) < (time() - 43200))) {
            acym_redirect(acym_completeLink('queue&task=campaigns', false, true));
        } else {
            $this->listing();
        }
    }

    public function countNumberOfRecipients()
    {
        $listsSelected = acym_getVar('array', 'listsSelected', []);
        if (empty($listsSelected)) {
            echo 0;
            exit;
        }

        $listClass = new ListClass();
        echo $listClass->getTotalSubCount($listsSelected);
        exit;
    }

    public function deleteAttach()
    {
        $mailid = acym_getVar('int', 'mail', 0);
        $attachid = acym_getVar('int', 'id', 0);

        if (!empty($mailid) && $attachid >= 0) {
            $mailClass = new MailClass();

            if ($mailClass->deleteOneAttachment($mailid, $attachid)) {
                echo json_encode(['message' => acym_translation('ACYM_ATTACHMENT_WELL_DELETED')]);
                exit;
            }
        }

        echo json_encode(['error' => acym_translation('ACYM_COULD_NOT_DELETE_ATTACHMENT')]);
        exit;
    }

    public function test()
    {
        $result = new \stdClass();
        $result->type = 'info';
        $result->timer = 5000;
        $result->message = '';

        $campaignId = acym_getVar('int', 'id', 0);

        $campaignClass = new CampaignClass();
        $campaign = $campaignClass->getOneById($campaignId);

        if (empty($campaign)) {
            $result->type = 'error';
            $result->timer = '';
            $result->message = acym_translation('ACYM_CAMPAIGN_NOT_FOUND');
            exit;
        }

        $mailerHelper = new MailerHelper();
        $mailerHelper->autoAddUser = true;
        $mailerHelper->checkConfirmField = false;
        $mailerHelper->report = false;


        $report = [];
        $testNote = acym_getVar('string', 'test_note', '');

        $testEmails = explode(',', acym_getVar('string', 'test_emails'));
        foreach ($testEmails as $oneAddress) {
            if (!$mailerHelper->sendOne($campaign->mail_id, $oneAddress, true, $testNote)) {
                $result->type = 'error';
                $result->timer = '';
            }

            if (!empty($mailerHelper->reportMessage)) {
                $report[] = $mailerHelper->reportMessage;
            }
        }

        $result->message = implode('<br/>', $report);
        echo json_encode($result);
        exit;
    }

    public function tests()
    {
        $campaignClass = new CampaignClass();
        acym_setVar('step', 'tests');
        acym_setVar('layout', 'tests');
        $campaignId = acym_getVar('int', 'id', 0);

        $campaign = $campaignClass->getOneByIdWithMail($campaignId);

        if (empty($campaign->id)) {
            acym_enqueueMessage(acym_translation('ACYM_CANT_GET_CAMPAIGN_INFORMATION'), 'error');
            $this->listing();

            return;
        }

        $testEmails = acym_getVar('array', 'test_emails', [acym_currentUserEmail()]);
        foreach ($testEmails as $oneEmail) {
            $defaultEmails[$oneEmail] = $oneEmail;
        }

        $data = [
            'id' => $campaign->id,
            'test_emails' => $defaultEmails,
            'upgrade' => !acym_level(ACYM_ESSENTIAL),
            'version' => 'enterprise',
        ];
        //TODO AcyChecker online
        if (false && !acym_isAcyCheckerInstalled()) {
            $lists = $campaignClass->getListsForCampaign($campaign->mail_id);
            $listClass = new ListClass();
            $data['recipients'] = $listClass->getTotalSubCount($lists);
        }

        $this->prepareListingClasses($data);
        $this->prepareSegmentDisplay($data, $campaign->sending_params);

        $this->breadcrumb[acym_escape($campaign->name)] = acym_completeLink('campaigns&task=edit&step=tests&id='.$campaign->id);
        parent::display($data);
    }

    public function saveTests()
    {
        $this->edit();
    }

    public function checkContent()
    {
        $campaignId = acym_getVar('int', 'id', 0);
        $campaignClass = new CampaignClass();
        $campaign = $campaignClass->getOneByIdWithMail($campaignId);

        $spamWords = [
            '4U',
            'you are a winner',
            'For instant access',
            'Accept credit cards',
            'Claims you registered with',
            'For just $',
            'Act now!',
            'Don’t hesitate!',
            'Click below',
            'Free',
            'income',
            'Click here',
            'Click to remove',
            'All natural',
            'Amazing',
            'Compare rates',
            'Apply Online',
            'your business',
            'As seen on',
            'all orders',
            'Auto email removal',
            'bankruptcy',
            'debt',
            'Be amazed',
            'Copy accurately',
            'Be your own boss',
            'Being a member',
            'Big bucks',
            'Credit card',
            'Bill',
            'Cures baldness',
            'Billing address',
            'Billion dollars',
            'Dear friend',
            'Brand new pager',
            'Bulk email',
            'Different reply to',
            'Buy direct',
            'Dig up dirt',
            'Full refund',
            'Buying judgments',
            'Direct email',
            'Get It Now',
            'Cable converter',
            'Direct marketing',
            'Get paid',
            'Get started now',
            'Call now',
            'Do it today',
            'Gift certificate',
            'Calling creditors',
            'Don’t delete',
            'Great offer',
            'Can’t live without',
            'Drastically reduced',
            'Guarantee',
            'Cancel at any time',
            'Earn per week',
            'Have you been turned down?',
            'Easy terms',
            'Hidden assets',
            'Eliminate bad credit',
            'Home employment',
            'Cash',
            'Email harvest',
            'Human growth hormone',
            'Casino',
            'Email marketing',
            'Expect to earn',
            'In accordance with laws',
            'Fantastic deal',
            'Increase sales',
            'Viagra',
            'Increase traffic',
            'Insurance',
            'Find out anything',
            'Investment decision',
            'it\'s legal',
            'It\'s effective',
            'Join millions of',
            'No questions asked',
            'Reverses aging',
            'No selling',
            'Risk',
            'Limited time only',
            'No strings attached',
            'Round the world',
            'Not intended',
            'Lose weight',
            'Off shore',
            'Safeguard notice',
            'Lower interest rates',
            'Offer expires',
            'Satisfaction guaranteed',
            'Lower monthly payment',
            'coupon',
            'Save $',
            'Lowest price',
            'Luxury car',
            'Save up to',
            'Once in a lifetime',
            'Score with babes',
            'Marketing solutions',
            'Mass email',
            'guaranteed',
            'See for yourself',
            'Meet singles',
            'One time mailing',
            'Sent in compliance',
            'Member stuff',
            'opportunity',
            'Online pharmacy',
            'Serious only',
            'MLM',
            'Only $',
            'Shopping spree',
            'Social security number',
            'trial offer',
            'Special promotion',
            'More Internet traffic',
            'Stock alert',
            'Outstanding values',
            'Pennies a day',
            'Stock pick',
            'New customers only',
            'money',
            'Stop snoring',
            'New domain extensions',
            'Please read',
            'Strong buy',
            'Potential earnings',
            'Stuff on sale',
            'No age restrictions',
            'Subject to credit',
            'No catch',
            'Supplies are limited',
            'No claim forms',
            'Produced and sent out',
            'Take action now',
            'No cost',
            'Profits',
            'hidden charges',
            'No credit check',
            'Promise you',
            'No disappointment',
            'Pure profit',
            'Real thing',
            'No fees',
            'Refinance home',
            'The best rates',
            'No gimmick',
            'The following form',
            'No inventory',
            'No investment',
            'giving it away',
            'No medical exams',
            'Removes wrinkles',
            'This isn’t junk',
            'No middleman',
            'This isn’t spam',
            'No obligation',
            'initial investment',
            'University diplomas',
            'No purchase necessary',
            'Reserves the right',
            'Unlimited',
            'We honor all',
            'Will not believe your eyes',
            'Urgent',
            'Winner',
            'US dollars',
            'What are you waiting for?',
            'Winning',
            'While supplies last',
            'Work at home',
            'drugs',
            'While you sleep',
            'You have been selected',
            'We hate spam',
            'Why pay more?',
        ];

        $errors = [];
        foreach ($spamWords as $oneWord) {
            if ((bool)preg_match('#'.preg_quote($oneWord, '#').'#Uis', $campaign->subject.$campaign->body)) {
                $errors[] = $oneWord;
            }
        }

        if (count($errors) > 2) {
            echo acym_translation('ACYM_TESTS_CONTENT_DESC');
            echo '<ul class="acym__ul"><li>'.implode('</li><li>', $errors).'</li></ul>';
        }
        exit;
    }

    public function checkLinks()
    {
        $campaignId = acym_getVar('int', 'id', 0);
        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();
        $campaign = $campaignClass->getOneById($campaignId);
        $mail = $mailClass->getOneById($campaign->mail_id);

        acym_trigger('replaceContent', [&$mail, false]);
        $userClass = new UserClass();
        $receiver = $userClass->getOneByEmail(acym_currentUserEmail());
        if (empty($receiver)) {
            $receiver = new \stdClass();
            $receiver->email = acym_currentUserEmail();
            $newID = $userClass->save($receiver);
            $receiver = $userClass->getOneById($newID);
        }
        acym_trigger('replaceUserInformation', [&$mail, &$receiver, false]);

        preg_match_all('# (href|src)="([^"]+)"#Uis', acym_absoluteURL($mail->body), $URLs);

        $errors = [];
        $processed = [];
        foreach ($URLs[2] as $oneURL) {
            if (in_array($oneURL, $processed)) continue;
            if (0 === strpos($oneURL, 'mailto:')) continue;
            if (strlen($oneURL) > 1 && (0 === strpos($oneURL, '#') || false !== strpos($oneURL, 'unsubscribe'))) continue;

            $processed[] = $oneURL;

            $headers = @get_headers($oneURL);
            $headers = is_array($headers) ? implode("\n ", $headers) : $headers;

            if (empty($headers) || preg_match('#^HTTP/.*\s+[(200|301|302|304)]+\s#i', $headers) !== 1) {
                $errors[] = '<a target="_blank" href="'.$oneURL.'">'.(strlen($oneURL) > 50 ? substr($oneURL, 0, 25).'...'.substr($oneURL, strlen($oneURL) - 20) : $oneURL).'</a>';
            }
        }

        if (!empty($errors)) {
            echo '<ul class="acym__ul"><li>'.implode('</li><li>', $errors).'</li></ul>';
        }

        exit;
    }

    public function checkSPAM()
    {
        $result = new \stdClass();
        $result->type = 'error';
        $result->message = '';

        $campaignId = acym_getVar('int', 'id', 0);
        $campaignClass = new CampaignClass();
        $campaign = $campaignClass->getOneByIdWithMail($campaignId);

        if (empty($campaign->mail_id)) {
            $result->message = acym_translation('ACYM_CAMPAIGN_NOT_FOUND');
        } else {
            ob_start();
            $urlSite = trim(base64_encode(preg_replace('#https?://(www2?\.)?#i', '', ACYM_LIVE)), '=/');
            $url = ACYM_SPAMURL.'spamTestSystem&component=acymailing&level='.strtolower($this->config->get('level', 'starter')).'&urlsite='.$urlSite;
            $spamtestSystem = acym_fileGetContent($url, 30);
            $warnings = ob_get_clean();

            // Could not load the information
            if (empty($spamtestSystem) || !empty($warnings)) {
                $result->message = acym_translation('ACYM_ERROR_LOAD_FROM_ACYBA').(!empty($warnings) && acym_isDebug() ? $warnings : '');
            } else {
                $decodedInformation = json_decode($spamtestSystem, true);
                if (!empty($decodedInformation['messages']) || !empty($decodedInformation['error'])) {
                    $msgError = empty($decodedInformation['messages']) ? '' : $decodedInformation['messages'].'<br />';
                    $msgError .= empty($decodedInformation['error']) ? '' : $decodedInformation['error'];
                    $result->message = $msgError;
                } else {
                    if (empty($decodedInformation['email'])) {
                        $result->message = acym_translation('ACYM_SPAMTEST_MISSING_EMAIL');
                    } else {
                        $mailerHelper = new MailerHelper();
                        $mailerHelper->checkConfirmField = false;
                        $mailerHelper->checkEnabled = false;
                        $mailerHelper->loadedToSend = true;
                        $mailerHelper->report = false;

                        //send a message to acy-WEBSITE-randnumber@mail-tester.com
                        $receiver = new \stdClass();
                        $receiver->id = 0;
                        $receiver->email = $decodedInformation['email'];
                        $receiver->name = $decodedInformation['name'];
                        $receiver->confirmed = 1;
                        $receiver->enabled = 1;
                        $mailerHelper->isSpamTest = true;

                        if ($mailerHelper->sendOne($campaign->mail_id, $receiver)) {
                            $result->type = 'success';
                            $result->message = 'https://mailtester.acyba.com/'.(substr($decodedInformation['email'], 0, strpos($decodedInformation['email'], '@')));
                            $result->lang = acym_getLanguageTag(true);
                        } else {
                            $result->message = $mailerHelper->reportMessage;
                        }
                    }
                }
            }
        }

        echo json_encode($result);
        exit;
    }

    public function saveAjax()
    {
        $return = $this->saveEditEmail(true);
        echo json_encode(['error' => !$return ? acym_translation('ACYM_ERROR_SAVING') : '', 'data' => $return]);
        exit;
    }

    public function saveAsTmplAjax()
    {
        $mailController = new MailsController();
        $isWellSaved = $mailController->store(true);
        acym_sendAjaxResponse($isWellSaved ? '' : acym_translation('ACYM_ERROR_SAVING'), $isWellSaved, $isWellSaved);
    }

    /**
     * Search user emails to suggest (autocomplete on send a test)
     */
    public function searchTestReceivers()
    {
        $search = acym_getVar('string', 'search', '');
        $userClass = new UserClass();
        $users = $userClass->getUsersLikeEmail($search);

        $return = [];
        foreach ($users as $oneUser) {
            $return[] = [$oneUser->id, $oneUser->email];
        }
        echo json_encode($return);
        exit;
    }

    public function summaryGenerated()
    {
        $campaignId = acym_getVar('int', 'id', 0);
        $mailClass = new MailClass();

        acym_setVar('layout', 'summary_generated');

        $generatedCampaign = $this->_loadCampaignMail($campaignId);

        if (!$generatedCampaign) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_LOAD_CAMPAIGN'), 'error');
            $this->listing();

            return;
        }

        $campaign = $generatedCampaign['campaign'];
        $mail = $generatedCampaign['mail'];

        $lists = $mailClass->getAllListsByMailId($mail->id);

        if (empty($lists)) {
            $this->listing();

            return;
        }

        $parentCampaign = $this->_loadCampaignMail($campaign->parent_id);
        if (!$parentCampaign) {
            $parentCampaign = ['campaign' => false, 'mail' => false];
        }

        //if campaign wait for confirmation
        $campaign->waiting_confirmation = false;
        if ($campaign->draft && $campaign->active) {
            $campaign->waiting_confirmation = true;
        }
        //if campaign canceled
        $campaign->canceled = false;
        if (!$campaign->draft && !$campaign->active) {
            $campaign->canceled = true;
        }

        $data = [
            'campaign' => $campaign,
            'mailId' => $campaign->mail_id,
            'mail' => $mail,
            'lists' => $lists,
            'parent_campaign' => $parentCampaign['campaign'],
            'parent_mail' => $parentCampaign['mail'],
            'mailClass' => $mailClass,
        ];

        $this->prepareMultilingual($data, false);
        $this->prepareAllMailsForMultilingual($data);

        $this->breadcrumb[acym_escape($mail->name)] = acym_completeLink('campaigns&task=summaryGenerated&id='.$campaign->id);
        parent::display($data);
    }

    protected function changeStatusGeneratedCampaign($statusToApply = 'disable')
    {
        $campaignId = acym_getVar('int', 'id', 0);
        $campaignClass = new CampaignClass();

        $campaign = $this->_loadCampaignMail($campaignId);

        if (!$campaign) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_LOAD_CAMPAIGN'), 'error');
            $this->listing();

            return;
        }

        $campaign = $campaign['campaign'];

        if ('disable' === $statusToApply) {
            $campaign->sent = 0;
            $campaign->active = 0;
            $campaign->draft = 0;
            $successMsg = acym_translation('ACYM_CAMPAIGN_HAS_BEEN_DISABLED');
        } else {
            $campaign->active = 1;
            $campaign->draft = 1;
            $successMsg = acym_translation('ACYM_CAMPAIGN_HAS_BEEN_ENABLED');
        }

        if ($campaignClass->save($campaign)) {
            acym_enqueueMessage($successMsg, 'success');
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
        }

        if ('enable' === $statusToApply) {
            acym_setVar('id', $campaignId);
            $this->summaryGenerated();
        } else {
            acym_setVar('campaigns_status', 'generated');
            $this->listing();
        }
    }

    public function disableGeneratedCampaign()
    {
        $this->changeStatusGeneratedCampaign('disable');
    }

    public function enableGeneratedCampaign()
    {
        $this->changeStatusGeneratedCampaign('enable');
    }

    private function _loadCampaignMail($campaignId)
    {
        if (empty($campaignId)) return false;

        $campaignClass = new CampaignClass();
        $mailClass = new MailClass();

        $campaign = $campaignClass->getOneById($campaignId);
        if (empty($campaign)) return false;

        $mail = $mailClass->getOneById($campaign->mail_id);
        if (empty($mail)) return false;


        if (empty($mail->from_name)) $mail->from_name = $this->config->get('from_name');
        if (empty($mail->from_email)) $mail->from_email = $this->config->get('from_email');
        if (empty($mail->reply_to_name)) $mail->reply_to_name = $this->config->get('replyto_name');
        if (empty($mail->reply_to_email)) $mail->reply_to_email = $this->config->get('replyto_email');

        return ['campaign' => $campaign, 'mail' => $mail];
    }

    public function followupSummary()
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

    public function createNewFollowupMail()
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
