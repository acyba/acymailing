<?php

namespace AcyMailing\Controllers\Segments;

use AcyMailing\Classes\SegmentClass;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;

trait Listing
{
    public function listing(): void
    {
        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }

        //__START__enterprise_
        if (acym_level(ACYM_ENTERPRISE)) {
            acym_setVar('layout', 'listing');
            $pagination = new PaginationHelper();
            $searchFilter = $this->getVarFiltersListing('string', 'segments_search', '');
            $status = $this->getVarFiltersListing('string', 'segments_status', '');
            $ordering = $this->getVarFiltersListing('string', 'segments_ordering', 'id');
            $orderingSortOrder = $this->getVarFiltersListing('string', 'segments_ordering_sort_order', 'asc');

            // Get pagination data
            $formsPerPage = $pagination->getListLimit();
            $page = $this->getVarFiltersListing('int', 'forms_pagination_page', 1);


            $requestData = [
                'ordering' => $ordering,
                'search' => $searchFilter,
                'elementsPerPage' => $formsPerPage,
                'offset' => ($page - 1) * $formsPerPage,
                'ordering_sort_order' => $orderingSortOrder,
                'status' => $status,
            ];

            $matchingSegments = $this->getMatchingElementsFromData($requestData, $status, $page);

            // Prepare the pagination
            $pagination->setStatus($matchingSegments['total']->total, $page, $formsPerPage);

            $filters = [
                'all' => $matchingSegments['total']->total,
                'active' => $matchingSegments['total']->totalActive,
                'inactive' => $matchingSegments['total']->total - $matchingSegments['total']->totalActive,
            ];

            $data = [
                'segments' => $matchingSegments['elements'],
                'pagination' => $pagination,
                'search' => $searchFilter,
                'ordering' => $ordering,
                'status' => $status,
                'orderingSortOrder' => $orderingSortOrder,
                'segmentsNumberPerStatus' => $filters,
            ];

            $this->prepareToolbar($data);

            parent::display($data);
        }
        //__END__enterprise_
    }

    private function prepareToolbar(array &$data): void
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'segments_search');
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'edit'], 'add', true);

        $data['toolbar'] = $toolbarHelper;
    }

    public function duplicate(): void
    {
        $ids = acym_getVar('array', 'elements_checked', []);

        $segmentClass = new SegmentClass();

        foreach ($ids as $id) {
            $segment = $segmentClass->getOneById($id);

            if (empty($segment)) {
                continue;
            }

            unset($segment->id);
            $segment->name .= ' - '.acym_translation('ACYM_COPY');
            $segment->creation_date = acym_date('now', 'Y-m-d H:i:s');
            $segment->filters = json_encode($segment->filters);

            $segmentClass->save($segment);
        }

        $this->listing();
    }
}
