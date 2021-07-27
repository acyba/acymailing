<?php

namespace AcyMailing\Controllers;

use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Helpers\UpdateHelper;
use AcyMailing\Helpers\WorkflowHelper;
use AcyMailing\Libraries\acymController;

class OverrideController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_EMAILS_OVERRIDE')] = acym_completeLink('override');
        acym_header('X-XSS-Protection:0');
    }

    public function listing()
    {
        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_setVar('layout', 'splashscreen');

            return parent::display([]);
        }

    }

    protected function prepareToolbar(&$data)
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

    protected function prepareEmailsOverrideListing(&$data)
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

    public function installOverrideEmails()
    {
        $updateHelper = new UpdateHelper();
        $updateHelper->installOverrideEmails();
    }

    public function reInstallOverrideEmails()
    {
        $this->currentClass->cleanEmailsOverride();
        $this->installOverrideEmails();

        return $this->listing();
    }

    public function reset()
    {
        acym_setVar('no_listing', true);
        $this->delete();
        $this->installOverrideEmails();
        $this->listing();
    }
}
