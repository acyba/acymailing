<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'OnlineInsertion.php';

class plgAcymOnline extends AcymPlugin
{
    use OnlineInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->pluginDescription->name = acym_translation('ACYM_WEBSITE');
    }
}
