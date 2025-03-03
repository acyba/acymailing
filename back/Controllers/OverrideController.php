<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\OverrideClass;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Helpers\UpdateHelper;
use AcyMailing\Helpers\WorkflowHelper;
use AcyMailing\Core\AcymController;

class OverrideController extends AcymController
{
    public function __construct()
    {
        parent::__construct();

        $this->breadcrumb[acym_translation('ACYM_EMAILS_OVERRIDE')] = acym_completeLink('override');
        acym_header('X-XSS-Protection:0');
    }

    public function listing(): void
    {
        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_setVar('layout', 'splashscreen');

            parent::display([]);

            return;
        }

    }

    protected function prepareToolbar(array &$data): void
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addButton(
            'ACYM_RESET_OVERRIDE',
            [
                'data-task' => 'reInstallOverrideEmails',
                'data-confirmation-message' => 'ACYM_RESET_OVERRIDES_CONFIRMATION',
            ]
        );

        $data['toolbar'] = $toolbarHelper;
    }

    protected function prepareEmailsOverrideListing(array &$data): void
    {
        // Prepare the pagination
        $emailsOverridePerPage = $data['pagination']->getListLimit();
        $page = $this->getVarFiltersListing('int', 'override_pagination_page', 1);

        $this->installOverrideEmails();

        // Get the matching lists
        $matchingEmailsOverride = $this->getMatchingElementsFromData(
            [
                'search' => $data['search'],
                'ordering' => $data['ordering'],
                'ordering_sort_order' => $data['orderingSortOrder'],
                'elementsPerPage' => $emailsOverridePerPage,
                'offset' => ($page - 1) * $emailsOverridePerPage,
                'source' => $data['source'],
                'status' => $data['status'],
            ],
            $data['status'],
            $page
        );

        $data['pagination']->setStatus($matchingEmailsOverride['total']->total, $page, $emailsOverridePerPage);
        $data['workflowHelper'] = new WorkflowHelper();

        $data['allEmailsOverride'] = $matchingEmailsOverride['elements'];
        $data['overrideNumberPerStatus'] = [
            'all' => $matchingEmailsOverride['total']->total,
            'active' => $matchingEmailsOverride['total']->totalActive,
            'inactive' => $matchingEmailsOverride['total']->total - $matchingEmailsOverride['total']->totalActive,
        ];
    }

    public function installOverrideEmails(): void
    {
        $updateHelper = new UpdateHelper();
        $updateHelper->installOverrideEmails();
    }

    public function reInstallOverrideEmails(): void
    {
        $overrideClass = new OverrideClass();
        $overrideClass->cleanEmailsOverride();
        $this->installOverrideEmails();

        $this->listing();
    }

    public function reset(): void
    {
        acym_setVar('no_listing', true);
        $this->delete();
        $this->installOverrideEmails();
        $this->listing();
    }
}
