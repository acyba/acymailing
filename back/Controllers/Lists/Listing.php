<?php

namespace AcyMailing\Controllers\Lists;

use AcyMailing\Classes\TagClass;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;

trait Listing
{
    public function listing()
    {
        acym_setVar('layout', 'listing');

        $tagClass = new TagClass();
        $data = [];
        $data['search'] = $this->getVarFiltersListing('string', 'lists_search', '');
        $data['tag'] = $this->getVarFiltersListing('string', 'lists_tag', '');
        $data['ordering'] = $this->getVarFiltersListing('string', 'lists_ordering', 'id');
        $data['orderingSortOrder'] = $this->getVarFiltersListing('string', 'lists_ordering_sort_order', 'desc');
        $data['status'] = $this->getVarFiltersListing('string', 'lists_status', '');
        $data['allTags'] = $tagClass->getAllTagsByType(TagClass::TYPE_LIST);
        $data['pagination'] = new PaginationHelper();

        if (!empty($data['tag'])) {
            $data['status_toolbar'] = [
                'lists_tag' => $data['tag'],
            ];
        }

        $this->prepareListsListing($data);
        $this->prepareToolbar($data);

        parent::display($data);
    }

    protected function prepareToolbar(&$data)
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'lists_search', 'ACYM_SEARCH');
        $toolbarHelper->addFilterByTag($data, 'lists_tag', 'acym__lists__filter__tags acym__select');

        $toolbarHelper->addButton(
            'ACYM_ACYCHECKER_CLEAN_LISTS',
            [
                'data-task' => 'clean',
                'type' => 'submit',
            ],
            'user-check'
        );

        $toolbarHelper->addButton(
            acym_translation('ACYM_EXPORT').' (<span id="acym__lists__listing__number_to_export" data-default="0"></span>)',
            ['data-task' => 'export', 'type' => 'submit', 'data-ctrl' => 'users', 'id' => 'acym__list__export'],
            'download'
        );
        $toolbarHelper->addOtherContent('<input type="hidden" name="preselectList" value="1" />');
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'settings'], 'playlist_add', true);

        $data['toolbar'] = $toolbarHelper;
    }

    public function clean()
    {
        if (acym_isAcyCheckerInstalled()) {
            if (ACYM_CMS === 'joomla') {
                acym_redirect(acym_route('index.php?option=com_acychecker', false));
            } else {
                acym_redirect(admin_url().'admin.php?page=acychecker_dashboard');
            }
        } else {
            acym_redirect(acym_completeLink('dashboard&task=acychecker', false, true));
        }
    }

    protected function prepareListsListing(&$data)
    {
        // Prepare the pagination
        $listsPerPage = $data['pagination']->getListLimit();
        $page = $this->getVarFiltersListing('int', 'lists_pagination_page', 1);

        // Get the matching lists
        $matchingLists = $this->getMatchingElementsFromData(
            [
                'search' => $data['search'],
                'tag' => $data['tag'],
                'ordering' => $data['ordering'],
                'ordering_sort_order' => $data['orderingSortOrder'],
                'elementsPerPage' => $listsPerPage,
                'offset' => ($page - 1) * $listsPerPage,
                'status' => $data['status'],
            ],
            $data['status'],
            $page
        );
        $data['pagination']->setStatus($matchingLists['total'], $page, $listsPerPage);

        $data['lists'] = $matchingLists['elements'];
        $data['listNumberPerStatus'] = $matchingLists['status'];
    }
}
