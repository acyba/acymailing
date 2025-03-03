<?php

namespace AcyMailing\Controllers\Forms;

use AcyMailing\Classes\FormClass;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;

trait Listing
{
    public function listing(): void
    {
        acym_setVar('layout', 'listing');
        $pagination = new PaginationHelper();
        $searchFilter = $this->getVarFiltersListing('string', 'forms_search', '');
        $status = $this->getVarFiltersListing('string', 'forms_status', '');
        $tagFilter = $this->getVarFiltersListing('string', 'forms_tag', '');
        $ordering = $this->getVarFiltersListing('string', 'forms_ordering', 'id');
        $orderingSortOrder = $this->getVarFiltersListing('string', 'forms_ordering_sort_order', 'asc');
        $formClass = new FormClass();

        // Get pagination data
        $formsPerPage = $pagination->getListLimit();
        $page = $this->getVarFiltersListing('int', 'forms_pagination_page', 1);

        $requestData = [
            'ordering' => $ordering,
            'search' => $searchFilter,
            'elementsPerPage' => $formsPerPage,
            'offset' => ($page - 1) * $formsPerPage,
            'tag' => $tagFilter,
            'ordering_sort_order' => $orderingSortOrder,
            'status' => $status,
        ];

        $matchingForms = $this->getMatchingElementsFromData($requestData, $status, $page);

        // Prepare the pagination
        $pagination->setStatus($matchingForms['total']->total, $page, $formsPerPage);

        $filters = [
            'all' => $matchingForms['total']->total,
            'active' => $matchingForms['total']->totalActive,
            'inactive' => $matchingForms['total']->total - $matchingForms['total']->totalActive,
        ];

        $data = [
            'allForms' => $matchingForms['elements'],
            'pagination' => $pagination,
            'search' => $searchFilter,
            'ordering' => $ordering,
            'tag' => $tagFilter,
            'status' => $status,
            'orderingSortOrder' => $orderingSortOrder,
            'formsNumberPerStatus' => $filters,
            'formTypes' => $formClass->getTranslatedTypes(),
            'formClass' => $formClass,
        ];

        $this->prepareToolbar($data);

        parent::display($data);
    }

    public function prepareToolbar(array &$data): void
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'forms_search', 'ACYM_SEARCH');
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'newForm'], 'add', true);

        $data['toolbar'] = $toolbarHelper;
    }
}
