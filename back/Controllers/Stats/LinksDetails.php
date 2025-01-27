<?php

namespace AcyMailing\Controllers\Stats;

use AcyMailing\Helpers\ExportHelper;

trait LinksDetails
{
    public function linksDetails()
    {
        acym_setVar('layout', 'links_details');

        $data = [];

        if (!$this->prepareDefaultPageInfo($data, true)) return;

        $this->prepareLinksDetailsListing($data);
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

    public function exportLinksDetails()
    {
        if (!$this->prepareDefaultPageInfo($data, true)) return;

        $this->prepareLinksDetailsListing($data);
        $exportHelper = new ExportHelper();

        $columnsToExport['url.name'] = acym_translation('ACYM_URL');
        $columnsToExport['total_click'] = acym_translation('ACYM_TOTAL_CLICKS');
        $columnsToExport['unique_click'] = acym_translation('ACYM_UNIQUE_CLICKS');

        $exportHelper->exportStatsFullCSV($data['query'], $columnsToExport, 'links_details');
        exit;
    }
}
