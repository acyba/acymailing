<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'OnlineInsertion.php';

class plgAcymOnline extends acymPlugin
{
    use OnlineInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_WEBSITE_LINKS');
    }
}
