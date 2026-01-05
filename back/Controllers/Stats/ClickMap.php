<?php

namespace AcyMailing\Controllers\Stats;

trait ClickMap
{
    public function clickMap(): void
    {
        acym_setVar('layout', 'click_map');

        $data = [];

        if (!$this->prepareDefaultPageInfo($data, true)) {
            return;
        }

        if (count($data['selectedMailid']) > 1) {
            $this->globalStats();

            return;
        }

        $this->prepareClickStats($data);
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
