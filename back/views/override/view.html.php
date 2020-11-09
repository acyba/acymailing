<?php

namespace AcyMailing\Views;

use AcyMailing\Libraries\acymView;

class OverrideViewOverride extends acymView
{
    public function __construct()
    {
        parent::__construct();

        $this->tabs = [];

        $tabs = [
            ACYM_CMS => ACYM_CMS_TITLE,
        ];

        acym_trigger('onAcymGetEmailOverrideSources', [&$tabs]);

        foreach ($tabs as $tabLink => $tabName) {
            $this->tabs['listing&overrideMailSource='.$tabLink] = $tabName;
        }
    }
}
