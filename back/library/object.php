<?php

class acymObject
{
    var $config;
    var $cmsUserVars;

    public function __construct()
    {
        global $acymCmsUserVars;
        $this->cmsUserVars = $acymCmsUserVars;

        $this->config = 'acymconfigurationClass' === get_class($this) ? $this : acym_config();
    }
}
