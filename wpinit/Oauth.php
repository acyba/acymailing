<?php

namespace AcyMailing\Init;

class acyOauth
{
    public function __construct()
    {
        $code = acym_getVar('string', 'code');
        $state = acym_getVar('string', 'state');
        if (!empty($code) && !empty($state) && $state === 'acymailing') {
            acym_redirect(acym_completeLink('configuration&code='.$_GET['code']), false, true);
        }
    }
}

$acyOauth = new acyOauth();
