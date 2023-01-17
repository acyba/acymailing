<?php

namespace AcyMailing\Views;

use AcyMailing\Libraries\acymView;

class BouncesViewBounces extends acymView
{
    public function __construct()
    {
        parent::__construct();

        $this->tabs = [
            'bounces' => 'ACYM_BOUNCE_RULES',
            'mailboxes' => 'ACYM_MAILBOX_ACTIONS',
        ];
    }
}
