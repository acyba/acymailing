<?php

class acyUltimatemember extends acyHook
{
    public function __construct()
    {
        add_action('um_after_register_fields', [$this, 'addRegistrationFields']);
    }


    public function addRegistrationFields($externalPluginConfig = '')
    {
        parent::addRegistrationFields('regacy_use_ultimate_member');
    }
}

new acyUltimatemember();
