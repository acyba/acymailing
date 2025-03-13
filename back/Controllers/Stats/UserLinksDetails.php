<?php

namespace AcyMailing\Controllers\Stats;

use AcyMailing\Classes\UrlClickClass;
use AcyMailing\Helpers\ExportHelper;
use AcyMailing\Helpers\PaginationHelper;

trait UserLinksDetails
{
    public function exportUserLinksDetails(): void
    {
        $data = [];
        if (!$this->prepareDefaultPageInfo($data, true)) {
            return;
        }

        $this->prepareUserLinksDetailsListing($data);
        $exportHelper = new ExportHelper();

        $columnsToExport['user.email'] = acym_translation('ACYM_SUBSCRIBER');
        $columnsToExport['user_name'] = acym_translation('ACYM_USER_NAME');
        $columnsToExport['url_name'] = acym_translation('ACYM_URL');
        $columnsToExport['date_click'] = acym_translation('ACYM_CLICK_DATE');
        $columnsToExport['click'] = acym_translation('ACYM_TOTAL_CLICKS');

        $exportHelper->exportStatsFullCSV($data['query'], $columnsToExport, 'user_links_details');
        exit;
    }

    public function userClickDetails(): void
    {
        acym_setVar('layout', 'user_links_details');

        $data = [];

        if (!$this->prepareDefaultPageInfo($data, true)) {
            return;
        }

        $this->prepareUserLinksDetailsListing($data);
        if (count($this->selectedMailIds) == 1) {
            if ($data['isAbTest']) {
                $this->prepareAbTestMails($data);
            } elseif (acym_isMultilingual()) {
                $this->prepareMultilingualMails($data);
            }
        }
        $this->prepareMailFilter($data);

        parent::display($data);
    }

    private function prepareUserLinksDetailsListing(array &$data): void
    {
        $data['search'] = $this->getVarFiltersListing('string', 'user_links_details_search', '');
        $data['ordering'] = $this->getVarFiltersListing('string', 'user_links_details_ordering', 'user_id');
        $data['orderingSortOrder'] = $this->getVarFiltersListing('string', 'user_links_details_ordering_sort_order', 'desc');

        if (base64_encode(base64_decode($data['search'])) === $data['search']) {
            $data['search'] = base64_decode($data['search']);
        }

        if (empty($this->selectedMailIds)) {
            return;
        }

        $pagination = new PaginationHelper();
        $urlClickClass = new UrlClickClass();

        $detailedStatsPerPage = $pagination->getListLimit();
        $page = $this->getVarFiltersListing('int', 'user_links_details_pagination_page', 1);

        $userClicks = $urlClickClass->getUserUrlClicksStats(
            [
                'ordering' => $data['ordering'],
                'search' => $data['search'],
                'detailedStatsPerPage' => $detailedStatsPerPage,
                'offset' => ($page - 1) * $detailedStatsPerPage,
                'ordering_sort_order' => $data['orderingSortOrder'],
                'mail_ids' => $this->selectedMailIds,
            ]
        );

        $this->decode($userClicks['user_links_details']);

        // Prepare the pagination
        $pagination->setStatus((int)$userClicks['total']->total, $page, $detailedStatsPerPage);

        $data['pagination'] = $pagination;
        $data['user_links_details'] = $userClicks['user_links_details'];
        $data['query'] = $userClicks['query'];
    }
}
