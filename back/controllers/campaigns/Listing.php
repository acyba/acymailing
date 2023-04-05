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
            $link = acym_completeLink(($isFrontJoomla ? 'front' : '').'campaigns&task='.$taskToGo, false, true);
            acym_redirect($link);
        } else {
            if (empty($currentTask) || !in_array($currentTask, $taskToStore)) $currentTask = 'campaigns';
            if ($currentTask == 'specificListing') $currentTask = empty($type) ? 'campaigns' : ['task' => $currentTask, 'type' => $type];
            $_SESSION[$variableName] = $currentTask;
        }

        $taskToCall = is_array($currentTask) ? $currentTask['task'] : $currentTask;
        if ($fromListing && method_exists($this, $taskToCall)) $this->$taskToCall();
    }

    public function setTaskListing($task): bool
    {
        if (!in_array($task, ['campaigns', 'campaigns_auto', 'welcome', 'unsubscribe',])) {
            return false;
        }

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

        $data['menuClass'] = $this->menuClass;

        parent::display($data);
    }

    public function prepareToolbar(&$data)
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'campaigns_search');
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'newEmail'], 'add', true);
        $mailClass = new MailClass();
        if (empty($data['campaign_type']) || $data['campaign_type'] !== $mailClass::TYPE_FOLLOWUP) {
            $toolbarHelper->addFilterByTag($data, 'campaigns_tag', 'acym__campaigns__filter__tags acym__select');
        }

        $data['toolbar'] = $toolbarHelper;
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
            if ('campaigns_auto' === $data['campaign_type'] && 'generated' === $data['status']) {
                $data['allStatusFilter']->all = $campaignClass->getCountCampaignType($campaignClass::SENDING_TYPE_AUTO);
            }
            $totalElement = empty($status) ? $data['allStatusFilter']->all : $data['allStatusFilter']->$status;
            $data['statusAuto'] = $campaignClass::SENDING_TYPE_AUTO;
        } else {
            $totalElement = $matchingCampaigns['total'];
        }

        $data['pagination']->setStatus($totalElement, $page, $campaignsPerPage);
        $data['allCampaigns'] = $matchingCampaigns['elements'];
    }

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
}
