<?php

namespace AcyMailing\Controllers\Stats;

use AcyMailing\Classes\UserStatClass;
use AcyMailing\Helpers\ExportHelper;
use AcyMailing\Helpers\PaginationHelper;

trait Detailed
{
    public function detailedStats()
    {
        acym_setVar('layout', 'detailed_stats');

        $data = [];

        $this->prepareDefaultPageInfo($data);

        $this->prepareDetailedListing($data);
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

    public function exportDetailed()
    {
        $exportHelper = new ExportHelper();
        $data = [];
        $this->prepareDefaultPageInfo($data);

        $where = '';
        if (!empty($this->selectedMailIds)) $where = 'WHERE userstat.`mail_id` IN ('.implode(',', $this->selectedMailIds).')';

        $groupBy = ' GROUP BY userstat.mail_id, userstat.user_id ';

        $columnsMailStat = acym_getColumns('user_stat');
        $columnsToExport = [];

        $columnsToExport['mail.subject'] = acym_translation('ACYM_EMAIL_SUBJECT');
        $columnsToExport['user.email'] = acym_translation('ACYM_USER_EMAIL');
        $columnsToExport['user.name'] = acym_translation('ACYM_USER_NAME');
        foreach ($columnsMailStat as $column) {
            if (in_array($column, ['user_id', 'mail_id'])) continue;
            $trad = acym_translation('ACYM_'.strtoupper($column).'_COLUMN_STAT');
            if ($column == 'send_date') $trad = acym_translation('ACYM_SEND_DATE');
            if ($column == 'open') $trad = acym_translation('ACYM_OPEN_TOTAL_COLUMN_STAT');
            if ($column == 'bounce') $trad = acym_translation('ACYM_BOUNCE_UNIQUE_COLUMN_STAT');
            $columnsToExport['userstat.'.$column] = $trad;
        }

        $query = 'SELECT '.implode(', ', array_keys($columnsToExport)).', SUM(urlclick.click) AS click FROM #__acym_user_stat AS userstat 
                  LEFT JOIN #__acym_user AS user ON user.id = userstat.user_id 
                  LEFT JOIN #__acym_mail AS mail ON mail.id = userstat.mail_id 
                  LEFT JOIN #__acym_url_click AS urlclick ON urlclick.user_id = userstat.user_id AND userstat.mail_id = urlclick.mail_id  '.$where.$groupBy;
        $columnsToExport['urlclick.click'] = acym_translation('ACYM_TOTAL_CLICK');
        $exportHelper->exportStatsFullCSV($query, $columnsToExport, 'detailed');
        exit;
    }

    private function prepareDetailedListing(&$data)
    {
        $data['search'] = $this->getVarFiltersListing('string', 'detailed_stats_search', '');
        $data['ordering'] = $this->getVarFiltersListing('string', 'detailed_stats_ordering', 'send_date');
        $data['orderingSortOrder'] = $this->getVarFiltersListing('string', 'detailed_stats_ordering_sort_order', 'desc');

        if (empty($this->selectedMailIds)) return;

        $userStatClass = new UserStatClass();
        $pagination = new PaginationHelper();

        $detailedStatsPerPage = $pagination->getListLimit();
        $page = $this->getVarFiltersListing('int', 'detailed_stats_pagination_page', 1);

        $matchingDetailedStats = $userStatClass->getDetailedStats(
            [
                'ordering' => $data['ordering'],
                'ordering_sort_order' => $data['orderingSortOrder'],
                'search' => $data['search'],
                'detailedStatsPerPage' => $detailedStatsPerPage,
                'offset' => ($page - 1) * $detailedStatsPerPage,
                'mail_ids' => $this->selectedMailIds,
            ]
        );

        // Prepare the pagination
        $pagination->setStatus($matchingDetailedStats['total'], $page, $detailedStatsPerPage);

        $data['pagination'] = $pagination;
        $data['detailed_stats'] = $matchingDetailedStats['detailed_stats'];
    }
}
