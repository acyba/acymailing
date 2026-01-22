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
