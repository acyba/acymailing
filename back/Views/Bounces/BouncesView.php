<?php

namespace AcyMailing\Views\Bounces;

use AcyMailing\Core\AcymView;

class BouncesView extends AcymView
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
