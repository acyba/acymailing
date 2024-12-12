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

        if (ACYM_CMS === 'joomla') {
            acym_loadLanguageFile('com_contact');
            $tabs['com_contact'] = acym_translation('ACYM_CONTACT');
        }

        acym_trigger('onAcymGetEmailOverrideSources', [&$tabs]);

        foreach ($tabs as $tabLink => $tabName) {
            $this->tabs['listing&overrideMailSource='.$tabLink] = $tabName;
        }
    }
}
