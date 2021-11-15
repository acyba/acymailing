<?php

namespace AcyMailing\Init;

class acyAddons
{
    public function __construct()
    {
        acym_trigger('onAcymInitWordpressAddons');
    }
}

$acyPlugin = new acyAddons();
