<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'OverrideInsertion.php';

class plgAcymOverride extends acymPlugin
{
	use OverrideInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_OVERRIDES');
    }
}
