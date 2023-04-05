<?php

namespace AcyMailing\Controllers\Stats;

trait ClickMap
{
    public function clickMap()
    {
        acym_setVar('layout', 'click_map');

        $data = [];

        if (!$this->prepareDefaultPageInfo($data, true)) return;

        $this->prepareClickStats($data);
        if (acym_isMultilingual() && count($this->selectedMailIds) == 1) $this->prepareMultilingualMails($data);
        $this->prepareMailFilter($data);

        parent::display($data);
    }
}
