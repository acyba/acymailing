<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Classes\MailClass;
use AcyMailing\Controllers\DynamicsController;

class FrontdynamicsController extends DynamicsController
{
    public function __construct()
    {
        parent::__construct();

        $this->menuAlias = [
            'index.php?option=com_acym&view=frontcampaigns&layout=listing' => 'index.php?option=com_acym&view=frontcampaigns&layout=campaigns',
        ];
        $this->allowedTasks = [
            'index.php?option=com_acym&view=frontlists&layout=listing' => [
                'trigger',
                'replaceDummy',
            ],
            'index.php?option=com_acym&view=frontcampaigns&layout=campaigns' => [
                'trigger',
                'replaceDummy',
            ],
        ];
    }

    public function replaceDummy(): void
    {
        // Make sure the current user has access to the specified email
        $mailId = acym_getVar('int', 'mailId', 0);
        if ($mailId > 0) {
            $mailClass = new MailClass();
            if (!$mailClass->hasUserAccess($mailId)) {
                die('Access denied for this preview');
            }
        }

        parent::replaceDummy();
    }
}
