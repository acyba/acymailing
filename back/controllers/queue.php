<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\QueueClass;
use AcyMailing\Classes\TagClass;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\QueueHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Helpers\WorkflowHelper;
use AcyMailing\Libraries\acymController;

class QueueController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_QUEUE')] = acym_completeLink('queue');
        $this->setDefaultTask('campaigns');
    }

    public function campaigns()
    {
        acym_setVar('layout', 'campaigns');
        $pagination = new PaginationHelper();

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
        $campaignsPerPage = $pagination->getListLimit();
        $page = acym_getVar('int', 'cqueue_pagination_page', 1);

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

        $campaignClass = new CampaignClass();

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
            'campaignClass' => $campaignClass,
            'languages' => acym_getLanguages(),
            'workflowHelper' => new WorkflowHelper(),
        ];

        $this->prepareToolbar($viewData);

        $this->breadcrumb[acym_translation('ACYM_CAMPAIGNS')] = acym_completeLink('queue');
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

    public function detailed()
    {
        acym_setVar('layout', 'detailed');
        $pagination = new PaginationHelper();

        // Get filters data
        $searchFilter = $this->getVarFiltersListing('string', 'dqueue_search', '');
        $tagFilter = $this->getVarFiltersListing('string', 'dqueue_tag', '');

        // Get pagination data
        $elementsPerPage = $pagination->getListLimit();
        $page = acym_getVar('int', 'dqueue_pagination_page', 1);

        $queueClass = new QueueClass();
        $matchingElements = $queueClass->getMatchingResults(
            [
                'search' => $searchFilter,
                'tag' => $tagFilter,
                'elementsPerPage' => $elementsPerPage,
                'offset' => ($page - 1) * $elementsPerPage,
            ]
        );

        // Prepare the pagination
        $pagination->setStatus($matchingElements['total'], $page, $elementsPerPage);

        $tagClass = new TagClass();
        $viewData = [
            'allElements' => $matchingElements['elements'],
            'pagination' => $pagination,
            'search' => $searchFilter,
            'tag' => $tagFilter,
            'allTags' => $tagClass->getAllTagsByType('mail'),
            'workflowHelper' => new WorkflowHelper(),
        ];

        $this->prepareToolbarDetailed($viewData);

        $this->breadcrumb[acym_translation('ACYM_QUEUE_DETAILED')] = acym_completeLink('queue&task=detailed');
        parent::display($viewData);
    }

    public function prepareToolbarDetailed(&$data)
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'dqueue_search', 'ACYM_SEARCH');
        $otherContent = acym_modal(
            '<i class="acymicon-paper-plane-o"></i>'.acym_translation('ACYM_SEND_ALL'),
            '',
            null,
            'data-reveal-larger',
            'class="cell medium-shrink button" data-reload="true" data-ajax="true" data-iframe="&ctrl=queue&task=continuesend&id=0&totalsend=0"'
        );
        $toolbarHelper->addOtherContent($otherContent);
        $toolbarHelper->addFilterByTag($data, 'dqueue_tag', 'acym__queue__filter__tags acym__select');

        $data['toolbar'] = $toolbarHelper;
        $data['cleartask'] = 'detailed';
        if (!empty($data['tag'])) {
            $data['status_toolbar'] = [
                'dqueue_tag' => $data['tag'],
            ];
        }
    }

    public function scheduleReady()
    {
        $queueClass = new QueueClass();
        $queueClass->scheduleReady();
    }

    public function continuesend()
    {
        //Are we configured to use the automatic send process only?
        //If so, we don't allow the user to access this feature!
        if ($this->config->get('queue_type') == 'onlyauto') {
            acym_setNoTemplate();
            acym_display(acym_translation('ACYM_ONLYAUTOPROCESS'), 'warning');

            exit;
        }

        //we move the next cron task so that we won't have problem with double send process
        $newcrontime = time() + 120;
        if ($this->config->get('cron_next') < $newcrontime) {
            $newValue = new \stdClass();
            $newValue->cron_next = $newcrontime;
            $this->config->save($newValue);
        }

        $mailid = acym_getCID('id');

        $totalSend = acym_getVar('int', 'totalsend', 0);
        if (empty($totalSend)) {
            $query = 'SELECT COUNT(queue.user_id) FROM #__acym_queue AS queue LEFT JOIN #__acym_campaign AS campaign ON queue.mail_id = campaign.mail_id WHERE (campaign.id IS NULL OR campaign.active = 1) AND queue.sending_date < '.acym_escapeDB(
                    acym_date('now', 'Y-m-d H:i:s', false)
                );
            if (!empty($mailid)) {
                $query .= ' AND queue.mail_id = '.intval($mailid);
            }
            $totalSend = acym_loadResult($query);
        }

        $alreadySent = acym_getVar('int', 'alreadysent', 0);

        $helperQueue = new QueueHelper();
        $helperQueue->id = $mailid;
        $helperQueue->report = true;
        $helperQueue->total = $totalSend;
        $helperQueue->start = $alreadySent;
        $helperQueue->pause = $this->config->get('queue_pause');
        // ->Process will exit the current page if it needs to be continued
        $helperQueue->process();

        //We should never be there... but if the user tries to resume the send process and the messages are not ready to be sent then it will land here...
        acym_setNoTemplate();
        exit;
    }

    public function cancelSending()
    {
        $mailId = acym_getVar('int', 'acym__queue__cancel__mail_id');

        if (!empty($mailId)) {
            $hasStat = acym_loadResult('SELECT COUNT(*) FROM #__acym_user_stat WHERE mail_id = '.intval($mailId));

            $result = [];

            $result[] = acym_query('DELETE FROM #__acym_queue WHERE mail_id = '.intval($mailId));
            $result[] = acym_query('UPDATE #__acym_mail_stat SET total_subscribers = sent WHERE mail_id = '.intval($mailId));
            $result[] = acym_query('UPDATE #__acym_campaign SET active = 1 WHERE mail_id = '.intval($mailId));
            if (empty($hasStat)) {
                $result[] = acym_query('UPDATE #__acym_campaign SET draft = "1", sent = "0", sending_date = NULL WHERE mail_id = '.intval($mailId));
                $result[] = acym_query('DELETE FROM #__acym_mail_stat WHERE mail_id = '.intval($mailId));
            }
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_QUEUE_CANCEL_CAMPAIGN'), 'error');
        }
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
