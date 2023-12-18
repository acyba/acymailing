<?php

namespace AcyMailing\Controllers\Stats;

trait Lists
{
    public function statsByList()
    {
        acym_setVar('layout', 'stats_by_list');

        $data = [];
        if (!$this->prepareDefaultPageInfo($data, true)) return;
        $this->prepareStatByList($data);
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
}
