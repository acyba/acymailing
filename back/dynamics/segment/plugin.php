<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'SegmentAutomationFilters.php';

class plgAcymSegment extends acymPlugin
{
    use SegmentAutomationFilters;

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_SEGMENT');
    }
}
