<?php

namespace AcyMailing\Controllers\Queue;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\QueueClass;
use AcyMailing\Classes\TagClass;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Helpers\WorkflowHelper;

trait Campaigns
{
    public function campaigns()
    {
        acym_setVar('layout', 'campaigns');

        if (acym_level(ACYM_ESSENTIAL) && $this->config->get('cron_last', 0) < (time() - 43200)) {
            acym_enqueueMessage(
                acym_translation('ACYM_CREATE_CRON_REMINDER').' <a id="acym__queue__configure-cron" href="'.acym_completeLink('configuration&tab=license').'">'.acym_translation(
                    'ACYM_GOTO_CONFIG'
                ).'</a>',
                'warning'
            );
        }

        // Get filters data
        $searchFilter = $this->getVarFiltersListing('string', 'cqueue_search', '');
        $tagFilter = $this->getVarFiltersListing('string', 'cqueue_tag', '');
        $status = $this->getVarFiltersListing('string', 'cqueue_status', '');

        // Get pagination data
        $pagination = new PaginationHelper();
        $campaignsPerPage = $pagination->getListLimit();
        $page = $this->getVarFiltersListing('int', 'cqueue_pagination_page', 1);

        $queueClass = new QueueClass();
        $matchingElements = $queueClass->getMatchingCampaigns(
            [
                'search' => $searchFilter,
                'tag' => $tagFilter,
                'status' => $status,
                'campaignsPerPage' => $campaignsPerPage,
                'offset' => ($page - 1) * $campaignsPerPage,
            ]
        );

        // Prepare the pagination
        $pagination->setStatus($matchingElements['total'], $page, $campaignsPerPage);
        $tagClass = new TagClass();

        $viewData = [
            'allElements' => $matchingElements['elements'],
            'pagination' => $pagination,
            'search' => $searchFilter,
            'tag' => $tagFilter,
            'allTags' => $tagClass->getAllTagsByType('mail'),
            'numberPerStatus' => $matchingElements['status'],
            'status' => $status,
            'campaignClass' => new CampaignClass(),
            'languages' => acym_getLanguages(),
            'workflowHelper' => new WorkflowHelper(),
        ];

        $this->prepareToolbar($viewData);

        $this->breadcrumb[acym_translation('ACYM_MAILS')] = acym_completeLink('queue');
        parent::display($viewData);
    }

    public function prepareToolbar(&$data)
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'cqueue_search', 'ACYM_SEARCH');
        $toolbarHelper->addButton(
            acym_translation('ACYM_EMPTY_QUEUE'),
            [
                'data-task' => 'emptyQueue',
                'data-confirmation-message' => 'ACYM_ARE_YOU_SURE',
            ],
            'close'
        );
        $otherContent = acym_modal(
            '<i class="acymicon-paper-plane-o"></i>'.acym_translation('ACYM_SEND_MAILS_MANUALLY'),
            '',
            null,
            'data-reveal-larger',
            'class="cell medium-6 large-shrink button" data-reload="true" data-ajax="true" data-iframe="&ctrl=queue&task=continuesend&id=0&totalsend=0"'
        );
        $toolbarHelper->addOtherContent($otherContent);
        $toolbarHelper->addFilterByTag($data, 'cqueue_tag', 'acym__queue__filter__tags acym__select');

        $data['toolbar'] = $toolbarHelper;
        if (!empty($data['tag'])) {
            $data['status_toolbar'] = [
                'cqueue_tag' => $data['tag'],
            ];
        }
    }

    public function cancelSending()
    {
        $mailId = acym_getVar('int', 'acym__queue__cancel__mail_id');

        if (!empty($mailId)) {
            $hasStat = acym_loadResult('SELECT COUNT(*) FROM #__acym_user_stat WHERE mail_id = '.intval($mailId));

            acym_query('DELETE FROM #__acym_queue WHERE mail_id = '.intval($mailId));
            acym_query('UPDATE #__acym_mail_stat SET total_subscribers = sent WHERE mail_id = '.intval($mailId));
            acym_query('UPDATE #__acym_campaign SET active = 1 WHERE mail_id = '.intval($mailId));
            if (empty($hasStat)) {
                acym_query('UPDATE #__acym_campaign SET draft = "1", sent = "0", sending_date = NULL WHERE mail_id = '.intval($mailId));
                acym_query('DELETE FROM #__acym_mail_stat WHERE mail_id = '.intval($mailId));
            }
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_QUEUE_CANCEL_CAMPAIGN'), 'error');
        }
    }

    public function cancelCampaignSending()
    {
        $this->cancelSending();
        $this->campaigns();
    }

    public function playPauseSending()
    {
        $active = acym_getVar('int', 'acym__queue__play_pause__active__new_value');
        $campaignId = acym_getVar('int', 'acym__queue__play_pause__campaign_id');

        if (!empty($campaignId)) {
            $queueClass = new QueueClass();
            $queueClass->unpauseCampaign($campaignId, $active);
        } else {
            if (!empty($active)) {
                acym_enqueueMessage(acym_translation('ACYM_ERROR_QUEUE_RESUME'), 'error');
            } else {
                acym_enqueueMessage(acym_translation('ACYM_ERROR_QUEUE_PAUSE'), 'error');
            }
        }

        $this->campaigns();
    }

    public function emptyQueue()
    {
        acym_checkToken();

        $queueClass = new QueueClass();
        $deleted = $queueClass->emptyQueue();
        acym_enqueueMessage(acym_translationSprintf('ACYM_EMAILS_REMOVED_QUEUE', $deleted));

        $this->campaigns();
    }
}
