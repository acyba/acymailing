<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'UserAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'UserAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'UserInsertion.php';

class plgAcymUser extends acymPlugin
{
    use UserAutomationConditions;
    use UserAutomationFilters;
    use UserInsertion;

    public function __construct()
    {
        parent::__construct();

        global $acymCmsUserVars;
        $this->cmsUserVars = $acymCmsUserVars;

        $this->pluginDescription->name = acym_translationSprintf('ACYM_CMS_USER', '{__CMS__}');
    }
}
