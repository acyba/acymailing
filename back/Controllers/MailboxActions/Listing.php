<?php

namespace AcyMailing\Controllers\MailboxActions;

use AcyMailing\Classes\MailboxClass;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\WorkflowHelper;

trait Listing
{
    public function prepareMailboxesListing(array &$data): void
    {
        // Prepare the pagination
        $mailboxesPerPage = $data['pagination']->getListLimit();
        $page = $this->getVarFiltersListing('int', 'mailboxes_pagination_page', 1);
        $status = $data['status'];

        // Get the matching mailboxes
        $matchingMailboxes = $this->getMatchingElementsFromData(
            [
                'ordering' => $data['ordering'],
                'search' => $data['search'],
                'elementsPerPage' => $mailboxesPerPage,
                'offset' => ($page - 1) * $mailboxesPerPage,
                'ordering_sort_order' => $data['orderingSortOrder'],
                'status' => $status,
            ],
            $status,
            $page,
            'mailbox'
        );

        $data['allStatusFilters'] = [
            'all' => $matchingMailboxes['total']->total,
            'active' => $matchingMailboxes['total']->totalActive,
            'inactive' => $matchingMailboxes['total']->total - $matchingMailboxes['total']->totalActive,
        ];
        $data['pagination']->setStatus($matchingMailboxes['total']->total, $page, $mailboxesPerPage);
        $data['allMailboxes'] = $matchingMailboxes['elements'];
    }

    public function mailboxes(): void
    {
        acym_setVar('layout', 'mailboxes');
        acym_setVar('task', 'mailboxes');

        $data = [
            'pagination' => new PaginationHelper(),
            'workflowHelper' => new WorkflowHelper(),
        ];
        $this->getAllParamsRequest($data);
        $this->prepareMailboxesListing($data);
        $this->prepareMailboxesActions($data);
        $this->prepareToolbar($data, 'mailboxes');

        parent::display($data);
    }

    public function duplicateMailboxAction(): void
    {
        $mailboxActionSelected = acym_getVar('int', 'elements_checked');
        if (empty($mailboxActionSelected)) {
            return;
        }

        $mailboxClass = new MailboxClass();
        $mailboxClass->duplicate($mailboxActionSelected);

        $this->mailboxes();
    }

    public function deleteMailboxAction(): void
    {
        $mailboxActionSelected = acym_getVar('array', 'elements_checked');
        if (empty($mailboxActionSelected)) {
            return;
        }

        $mailboxClass = new MailboxClass();
        $mailboxClass->delete($mailboxActionSelected);

        $this->mailboxes();
    }

    private function prepareMailboxesActions(array &$data): void
    {
        if (empty($data['allMailboxes'])) {
            return;
        }

        foreach ($data['allMailboxes'] as $key => $oneMailbox) {
            $data['allMailboxes'][$key]->actionsRendered = [];

            $actions = json_decode($oneMailbox->actions, true);
            if (empty($actions)) {
                continue;
            }

            // We build the actions to display in the listing
            $actionsRendered = [];
            foreach ($actions as $action) {
                acym_trigger('onAcymMailboxActionSummaryListing', [&$action, &$actionsRendered]);
            }

            $data['allMailboxes'][$key]->actionsRendered = $actionsRendered;
        }
    }
}
