<?php

namespace AcyMailing\Controllers\Scenarios;

use AcyMailing\Classes\ScenarioClass;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;

trait Listing
{
    public function listing(): void
    {
        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }

        acym_setVar('layout', 'listing');

        $data = [
            'pagination' => new PaginationHelper(),
        ];

        $this->prepareListingFilters($data);
        $this->prepareListingElements($data);
        $this->prepareToolbar($data);

        parent::display($data);
    }

    private function prepareListingFilters(array &$data): void
    {
        $data['search'] = $this->getVarFiltersListing('string', 'scenario_search', '');
        $data['status'] = $this->getVarFiltersListing('string', 'scenarios_status', '');
        $data['ordering'] = $this->getVarFiltersListing('string', 'scenarios_ordering', 'id');
        $data['ordering_sort_order'] = $this->getVarFiltersListing('string', 'scenarios_ordering_sort_order', 'asc');
        $data['page'] = $this->getVarFiltersListing('int', 'scenarios_pagination_page', 1);
    }

    private function prepareListingElements(array &$data): void
    {
        // Prepare the pagination
        $scenarioPerPage = $data['pagination']->getListLimit();

        $matchingScenarios = $this->getMatchingElementsFromData(
            [
                'search' => $data['search'],
                'elementsPerPage' => $scenarioPerPage,
                'offset' => ($data['page'] - 1) * $scenarioPerPage,
                'status' => $data['status'],
                'ordering' => $data['ordering'],
                'ordering_sort_order' => $data['ordering_sort_order'],
            ],
            $data['status'],
            $data['page']
        );

        $data['pagination']->setStatus($matchingScenarios['total']->total, $data['page'], $scenarioPerPage);

        $data['scenarios'] = $matchingScenarios['elements'];
        $data['totalOverall'] = $matchingScenarios['totalOverall'];
        $data['scenariosNumberStatus'] = $matchingScenarios['scenariosNumberStatus'];
    }

    protected function prepareToolbar(array &$data): void
    {
        $data['toolbar'] = new ToolbarHelper();
        $data['toolbar']->addSearchBar($data['search'], 'scenario_search');

        $data['toolbar']->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'edit', 'data-step' => 'editScenario'], 'add', true);
    }

    public function duplicate(): void
    {
        acym_checkToken();

        $scenarioIds = acym_getVar('array', 'elements_checked', []);

        acym_arrayToInteger($scenarioIds);

        if (empty($scenarioIds)) {
            $this->listing();
        }

        $scenarioClass = new ScenarioClass();

        foreach ($scenarioIds as $scenarioId) {
            $scenarioClass->duplicate($scenarioId);
        }

        $this->listing();
    }
}
