<?php

namespace AcyMailing\Libraries;

class acymObject
{
    var $config;
    var $cmsUserVars;

    public function __construct()
    {
        global $acymCmsUserVars;
        $this->cmsUserVars = $acymCmsUserVars;
        $this->config = 'AcyMailing\\Classes\\ConfigurationClass' === get_class($this) ? $this : acym_config();
    }
}
