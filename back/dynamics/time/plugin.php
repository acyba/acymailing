<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'TimeAutomationTriggers.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'TimeInsertion.php';

class plgAcymTime extends acymPlugin
{
    use TimeAutomationTriggers;
    use TimeInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_TIME');
    }
}
