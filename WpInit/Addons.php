<?php

namespace AcyMailing\WpInit;

class Addons
{
    public function __construct()
    {
        acym_trigger('onAcymInitWordpressAddons');
    }
}
