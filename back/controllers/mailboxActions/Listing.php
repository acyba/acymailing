<?php

namespace AcyMailing\Controllers\MailboxActions;

use AcyMailing\Classes\MailboxClass;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\WorkflowHelper;

trait Listing
{
    private function prepareMailboxesActions(&$data)
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

    public function prepareMailboxesListing(&$data)
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
            $page
        );

        // End pagination
        $totalElement = $matchingMailboxes['total'];
        $data['allStatusFilters'] = [
            'all' => $matchingMailboxes['total']->total,
            'active' => $matchingMailboxes['total']->totalActive,
            'inactive' => $matchingMailboxes['total']->total - $matchingMailboxes['total']->totalActive,
        ];
        $data['pagination']->setStatus($totalElement->total, $page, $mailboxesPerPage);
        $data['allMailboxes'] = $matchingMailboxes['elements'];
    }

    public function mailboxes()
    {
        acym_setVar('layout', 'mailboxes');
        acym_setVar('task', 'mailboxes');
        $this->currentClass = new MailboxClass();

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

    private function mailboxDoListingAction($action)
    {
        $mailboxClass = new MailboxClass();
        if (!method_exists($mailboxClass, $action)) {
            return;
        }

        $mailboxActionSelected = acym_getVar('int', 'elements_checked');

        if (empty($mailboxActionSelected)) {
            return;
        }

        $mailboxClass->$action($mailboxActionSelected);

        $this->mailboxes();
    }

    public function duplicateMailboxAction()
    {
        $this->mailboxDoListingAction('duplicate');
    }

    public function deleteMailboxAction()
    {
        $this->mailboxDoListingAction('delete');
    }
}
