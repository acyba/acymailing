<?php

namespace AcyMailing\Controllers;

use AcyMailing\Libraries\acymController;
use AcyMailing\Controllers\Bounces\Listing as BouncesListing;
use AcyMailing\Controllers\Bounces\Rule;
use AcyMailing\Controllers\MailboxActions\Listing as MailboxActionsListing;
use AcyMailing\Controllers\MailboxActions\Edition as MailboxActionsEdition;

class BouncesController extends acymController
{
    use BouncesListing;
    use Rule;
    use MailboxActionsListing;
    use MailboxActionsEdition;

    private $runBounce = false;
    private $mailboxReport = [];

    public function __construct()
    {
        $this->defaulttask = 'bounces';
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_MAILBOX_ACTIONS')] = acym_completeLink('bounces');
        $this->storeRedirectListing();
    }

    public function storeRedirectListing($fromListing = false)
    {
        $variableName = 'ctrl_mailboxes_stored';
        acym_session();
        $taskToStore = [
            '',
            'bounces',
            'mailboxes',
        ];
        $currentTask = acym_getVar('string', 'task', '');
        if (!in_array($currentTask, $taskToStore) && !$fromListing) {
            return;
        }

        if ((empty($currentTask) || !in_array($currentTask, $taskToStore)) && !empty($_SESSION[$variableName])) {
            $taskToGo = is_array($_SESSION[$variableName]) ? $_SESSION[$variableName]['task'] : $_SESSION[$variableName];
            $link = acym_completeLink('bounces&task='.$taskToGo, false, true);
            if ($this->runBounce) {
                $link .= '&runBounce=1';
            }

            acym_redirect($link);
        } else {
            if (empty($currentTask) || !in_array($currentTask, $taskToStore)) {
                $currentTask = 'bounces';
            }
            $_SESSION[$variableName] = $currentTask;
        }

        $taskToCall = is_array($currentTask) ? $currentTask['task'] : $currentTask;
        if ($fromListing && method_exists($this, $taskToCall)) {
            $this->$taskToCall();
        }
    }

    private function getAllParamsRequest(&$data)
    {
        $data['search'] = $this->getVarFiltersListing('string', 'mailboxes_search', '');
        $data['status'] = $this->getVarFiltersListing('string', 'mailboxes_status', '');
        $data['ordering'] = $this->getVarFiltersListing('string', 'mailboxes_ordering', 'id');
        $data['orderingSortOrder'] = $this->getVarFiltersListing('string', 'mailboxes_ordering_sort_order', 'desc');
    }
}
