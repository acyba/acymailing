<?php

namespace AcyMailing\Controllers\Campaigns;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\TagClass;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Helpers\WorkflowHelper;


trait Listing
{
    public function listing()
    {
        $this->storeRedirectListing(true);
    }

    public function storeRedirectListing(bool $fromListing = false): void
    {
        acym_session();
        $isFrontJoomla = !acym_isAdmin() && ACYM_CMS === 'joomla';
        $variableName = $isFrontJoomla ? 'ctrl_stored_front' : 'ctrl_stored';

        $currentTask = acym_getVar('string', 'task', '');
        if (!in_array($currentTask, self::TASKS_TO_REMEMBER_FOR_LISTING) && !$fromListing) {
            return;
        }

        if ((empty($currentTask) || !in_array($currentTask, self::TASKS_TO_REMEMBER_FOR_LISTING)) && !empty($_SESSION[$variableName])) {
            $taskToGo = is_array($_SESSION[$variableName]) ? $_SESSION[$variableName]['task'].'&type='.$_SESSION[$variableName]['type'] : $_SESSION[$variableName];
            $link = acym_completeLink(($isFrontJoomla ? 'front' : '').'campaigns&task='.$taskToGo, false, true);
            acym_redirect($link);
        } else {
            if (empty($currentTask) || !in_array($currentTask, self::TASKS_TO_REMEMBER_FOR_LISTING)) {
                $currentTask = self::TASK_TYPE_CAMPAIGN;
            }

            if ($currentTask === self::TASK_TYPE_SPECIFIC_LISTING) {
                $type = acym_getVar('string', 'type', '');
                $currentTask = empty($type) ? self::TASK_TYPE_CAMPAIGN : ['task' => $currentTask, 'type' => $type];
            }

            $_SESSION[$variableName] = $currentTask;
        }

        $taskToCall = is_array($currentTask) ? $currentTask['task'] : $currentTask;

        if ($fromListing && method_exists($this, $taskToCall)) {
            $this->$taskToCall();
        }
    }

    public function setTaskListing(string $type): bool
    {
        $task = self::CAMPAIGN_TYPE_TO_TASK[$type] ?? self::TASK_TYPE_CAMPAIGN;

        if ($task === self::TASK_TYPE_SPECIFIC_LISTING) {
            $task = [
                'task' => self::TASK_TYPE_SPECIFIC_LISTING,
                'type' => $type,
            ];
        }

        $isFrontJoomla = !acym_isAdmin() && ACYM_CMS === 'joomla';
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

    public function campaigns()
    {
        acym_setVar('layout', self::TASK_TYPE_CAMPAIGN);

        $data = [
            'campaign_type' => 'campaigns',
            'element_to_display' => lcfirst(acym_translation('ACYM_CAMPAIGNS')),
        ];
        $this->prepareAllCampaignsListing($data);
        $this->prepareToolbar($data);
        $this->prepareListingClasses($data);

        $data['menuClass'] = $this->menuClass;

        parent::display($data);
    }

    public function prepareToolbar(&$data)
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'campaigns_search');
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'newEmail'], 'add', true);
        if (acym_isAdmin() && (empty($data['campaign_type']) || $data['campaign_type'] !== MailClass::TYPE_FOLLOWUP)) {
            $toolbarHelper->addFilterByTag($data, 'campaigns_tag', 'acym__campaigns__filter__tags acym__select');
        }

        $data['toolbar'] = $toolbarHelper;
    }

    private function getAllParamsRequest(&$data)
    {
        $tagClass = new TagClass();
        $data['search'] = $this->getVarFiltersListing('string', 'campaigns_search', '');
        $data['tag'] = $this->getVarFiltersListing('string', 'campaigns_tag', '');
        $data['allTags'] = $tagClass->getAllTagsByType(TagClass::TYPE_MAIL);
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
        if ($data['campaign_type'] === self::TASK_TYPE_CAMPAIGN_AUTO) {
            $this->getAutoCampaignsFrequency($data);
            $this->getIsPendingGenerated($data);
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
                $matchingCampaigns['elements'][$key]->scheduled = CampaignClass::SENDING_TYPE_SCHEDULED == $campaign->sending_type;
            }
        }

        // End pagination
        if (empty($class)) {
            $data['allStatusFilter'] = $this->getCountStatusFilter($matchingCampaigns['total'], $data['campaign_type']);
            if (self::TASK_TYPE_CAMPAIGN_AUTO === $data['campaign_type'] && 'generated' === $data['status']) {
                $data['allStatusFilter']->all = $campaignClass->getCountCampaignType(CampaignClass::SENDING_TYPE_AUTO);
            }
            $totalElement = empty($status) ? $data['allStatusFilter']->all : $data['allStatusFilter']->$status;
            $data['statusAuto'] = CampaignClass::SENDING_TYPE_AUTO;
        } else {
            $totalElement = $matchingCampaigns['total'];
        }

        $data['pagination']->setStatus($totalElement, $page, $campaignsPerPage);
        $data['allCampaigns'] = $matchingCampaigns['elements'];
    }

    public function getCountStatusFilter($allCampaigns, $type)
    {
        $allCountStatus = new \stdClass();

        if ($type == 'campaigns') {
            $this->getCountStatusFilterCampaigns($allCampaigns, $allCountStatus);
        } else {
            $this->getCountStatusFilterCampaignsAuto($allCampaigns, $allCountStatus);
        }

        return $allCountStatus;
    }

    private function getCountStatusFilterCampaigns($allCampaigns, &$allCountStatus)
    {
        $allCountStatus->all = 0;
        $allCountStatus->scheduled = 0;
        $allCountStatus->sent = 0;
        $allCountStatus->draft = 0;

        foreach ($allCampaigns as $campaign) {
            if (empty($campaign->parent_id)) {
                $allCountStatus->all += 1;
                if (CampaignClass::SENDING_TYPE_SCHEDULED == $campaign->sending_type) $allCountStatus->scheduled += 1;
                $allCountStatus->sent += $campaign->sent;
                $allCountStatus->draft += $campaign->draft;
            }
        }
    }

    public function mailbox_action()
    {
        acym_setVar('layout', self::TASK_TYPE_MAILBOX_ACTION);

        $data = [
            'campaign_type' => 'campaigns',
            'element_to_display' => lcfirst(acym_translation('ACYM_MAILBOX_ACTION_CAMPAIGN')),
        ];
        $this->prepareAllCampaignsListing($data);
        $this->prepareToolbar($data);
        $this->prepareListingClasses($data);

        $data['menuClass'] = $this->menuClass;

        parent::display($data);
    }
}
