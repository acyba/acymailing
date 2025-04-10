<?php

namespace AcyMailing\WpInit;

class Oauth
{
    public function __construct()
    {
        $code = acym_getVar('string', 'code');
        $state = acym_getVar('string', 'state');
        if (!empty($code) && !empty($state)) {
            if ($state === 'acymailingsmtp') {
                acym_redirect(acym_completeLink('configuration&auth_type=smtp&code='.$_GET['code'], false, true));
            }

            if ($state === 'acymailingbounce') {
                acym_redirect(acym_completeLink('configuration&auth_type=bounce&code='.$_GET['code'], false, true));
            }
        }
    }
}
