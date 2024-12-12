<?php

namespace AcyMailing\Controllers\Queue;

use AcyMailing\Classes\QueueClass;
use AcyMailing\Classes\TagClass;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Helpers\WorkflowHelper;

trait Detailed
{
    public function detailed()
    {
        acym_setVar('layout', 'detailed');
        $pagination = new PaginationHelper();

        // Get filters data
        $searchFilter = $this->getVarFiltersListing('string', 'dqueue_search', '');
        $tagFilter = $this->getVarFiltersListing('string', 'dqueue_tag', '');

        // Get pagination data
        $elementsPerPage = $pagination->getListLimit();
        $page = $this->getVarFiltersListing('int', 'dqueue_pagination_page', 1);

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
            'allTags' => $tagClass->getAllTagsByType(TagClass::TYPE_MAIL),
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
}
