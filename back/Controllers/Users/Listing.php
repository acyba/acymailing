<?php

namespace AcyMailing\Controllers\Users;

use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\SegmentClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\EntitySelectHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;

trait Listing
{
    public function listing(): void
    {
        acym_setVar('layout', 'listing');

        $data = [];
        $data['ordering'] = $this->getVarFiltersListing('string', 'users_ordering', 'id');
        $data['orderingSortOrder'] = $this->getVarFiltersListing('string', 'users_ordering_sort_order', 'desc');
        $data['pagination'] = new PaginationHelper();

        $this->prepareSegmentField($data);
        $this->prepareListingFilters($data);
        $this->prepareUsersListing($data);
        $this->prepareUsersSubscriptions($data);
        $this->prepareUsersFields($data);
        $this->prepareToolbar($data);

        parent::display($data);
    }

    protected function prepareSegmentField(array &$data): void
    {
        $segmentClass = new SegmentClass();
        $segments = $segmentClass->getAllForSelect();
        $data['segments'] = [];
        foreach ($segments as $id => $name) {
            if (empty($id)) $id = 0;
            $data['segments'][$id] = $name;
        }
    }

    protected function prepareToolbar(array &$data): void
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'users_search');
        $toolbarHelper->addOptionSelect(
            acym_translation('ACYM_SEGMENT'),
            acym_select($data['segments'], 'segment', $data['segment'], ['class' => 'acym__select'])
        );
        $toolbarHelper->addOptionSelect(
            acym_translation('ACYM_LIST'),
            acym_select(
                $data['lists'],
                'users_list',
                $data['list'],
                ['class' => 'acym__select']
            )
        );
        $toolbarHelper->addOptionSelect(
            acym_translation('ACYM_SUBSCRIPTION_STATUS'),
            acym_select($data['list_statuses'], 'list_status', $data['list_status'], ['class' => 'acym__select'])
        );

        $toolbarHelper->addButton(
            'ACYM_ACYCHECKER_CLEAN_USERS',
            [
                'data-task' => 'clean',
                'type' => 'submit',
            ],
            'user-check'
        );

        $exportButton = acym_translation('ACYM_EXPORT');
        $exportButton .= '<span id="acym__users__listing__number_to_export" data-default="'.acym_strtolower(acym_translation('ACYM_ALL')).'">&nbsp;(';
        $exportButton .= acym_strtolower(acym_translation('ACYM_ALL'));
        $exportButton .= ')</span>';
        $toolbarHelper->addButton(
            $exportButton,
            ['data-task' => 'export', 'type' => 'submit'],
            'download'
        );
        $toolbarHelper->addButton(acym_translation('ACYM_IMPORT'), ['data-task' => 'import'], 'upload');
        $entityHelper = new EntitySelectHelper();
        $otherContent = acym_modal(
            '<i class="acymicon-bell"></i>'.acym_translation('ACYM_SUBSCRIBE').' (<span id="acym__users__listing__number_to_add_to_list">0</span>)',
            $entityHelper->entitySelect('list', ['join' => ''], $entityHelper->getColumnsForList(), [
                'text' => acym_translation('ACYM_SUBSCRIBE_USERS_TO_THESE_LISTS'),
                'action' => 'addToList',
            ]),
            null,
            [],
            [
                'class' => 'button button-secondary disabled cell medium-6 large-shrink',
                'id' => 'acym__users__listing__button--add-to-list',
            ]
        );
        $toolbarHelper->addOtherContent($otherContent);
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'edit'], 'user-plus', true);

        $data['toolbar'] = $toolbarHelper;
    }

    protected function prepareListingFilters(array &$data): void
    {
        $data['status'] = $this->getVarFiltersListing('string', 'users_status', '');
        $data['search'] = $this->getVarFiltersListing('string', 'users_search', '');
        $data['list'] = $this->getVarFiltersListing('int', 'users_list', 0);
        $data['list_status'] = $this->getVarFiltersListing('string', 'list_status', 'sub');
        $data['segment'] = $this->getVarFiltersListing('int', 'segment', 0);

        $followupClass = new FollowupClass();
        $listClass = new ListClass();
        $lists = $listClass->getAll('id');
        uasort(
            $lists,
            function ($a, $b) {
                return strtolower($a->name) > strtolower($b->name) ? 1 : -1;
            }
        );

        // This keeps keys
        $data['lists'] = [0 => acym_translation('ACYM_SELECT_A_LIST')];
        foreach ($lists as $oneList) {
            if ($oneList->type === ListClass::LIST_TYPE_FRONT) continue;
            if ($oneList->type === ListClass::LIST_TYPE_FOLLOWUP) {
                $followup = $followupClass->getOneByListId($oneList->id);
                if (empty($followup)) continue;
                $oneList->name = $followup->display_name;
            }

            $data['lists'][$oneList->id] = $oneList->name;
        }

        $data['list_statuses'] = [
            'sub' => acym_translation('ACYM_SUBSCRIBED'),
            'unsub' => acym_translation('ACYM_UNSUBSCRIBED'),
            'none' => acym_translation('ACYM_NO_SUBSCRIPTION_STATUS'),
        ];

        $data['status_toolbar'] = [];
        if (!empty($data['list'])) {
            $data['status_toolbar'] = [
                'users_list' => $data['list'],
                'list_status' => $data['list_status'],
            ];
        }
        if (!empty($data['segment'])) {
            $data['status_toolbar'][] = $data['segment'];
        }
    }

    protected function prepareUsersListing(array &$data): void
    {
        // Prepare the pagination
        $usersPerPage = $data['pagination']->getListLimit();
        $page = $this->getVarFiltersListing('int', 'users_pagination_page', 1);

        $matchingUsers = $this->getMatchingElementsFromData(
            [
                'search' => $data['search'],
                'elementsPerPage' => $usersPerPage,
                'offset' => ($page - 1) * $usersPerPage,
                'status' => $data['status'],
                'ordering' => $data['ordering'],
                'ordering_sort_order' => $data['orderingSortOrder'],
                'list' => $data['list'],
                'segment' => $data['segment'],
                'list_status' => $data['list_status'],
                'cms_username' => true,
            ],
            $data['status'],
            $page
        );

        // Prepare the pagination
        $data['pagination']->setStatus($matchingUsers['total']->total, $page, $usersPerPage);

        $data['allUsers'] = $matchingUsers['elements'];
        $data['userNumberPerStatus'] = $matchingUsers['status'];
    }

    protected function prepareUsersSubscriptions(array &$data): void
    {
        $usersId = [];
        foreach ($data['allUsers'] as $oneUser) {
            $usersId[] = $oneUser->id;
        }

        $subscriptions = [];

        if (!empty($usersId)) {
            $userClass = new UserClass();
            $subscriptionsArray = $userClass->getUsersSubscriptionsByIds($usersId);

            foreach ($subscriptionsArray as $oneSubscription) {
                $subscriptions[$oneSubscription->user_id][$oneSubscription->id] = $oneSubscription;
            }
        }

        $data['usersSubscriptions'] = $subscriptions;
    }

    protected function prepareUsersFields(array &$data): void
    {
        $data['fields'] = [];

        if (empty($data['allUsers'])) return;

        $fieldClass = new FieldClass();
        $fieldsToDisplay = $fieldClass->getAllFieldsBackendListing();
        if (empty($fieldsToDisplay['ids'])) return;

        $userIds = [];
        foreach ($data['allUsers'] as $user) {
            $userIds[] = $user->id;
        }

        $fieldValue = $fieldClass->getAllFieldsListingByUserIds($userIds, $fieldsToDisplay['ids'], 'field.backend_listing = 1');
        $languages = acym_getLanguages();
        $languageFieldId = $fieldClass->getLanguageFieldId();
        foreach ($data['allUsers'] as &$user) {
            $user->fields = [];
            foreach ($fieldsToDisplay['ids'] as $fieldId) {
                if ($fieldId == $languageFieldId) {
                    $user->fields[$fieldId] = empty($languages[$user->language]) ? $user->language : $languages[$user->language]->name;
                } else {
                    $user->fields[$fieldId] = !isset($fieldValue[$fieldId.'-'.$user->id]) ? '' : $fieldValue[$fieldId.'-'.$user->id];
                }
            }
        }

        $data['fields'] = $fieldsToDisplay['names'];
    }

    public function addToList(): void
    {
        $listsSelected = json_decode(acym_getVar('string', 'acym__entity_select__selected', '[]'), true);
        if (empty($listsSelected)) {
            $listsSelected = [];
        }
        $selectedUserIds = acym_getVar('array', 'elements_checked', []);

        $userClass = new UserClass();
        $userClass->onlyManageableUsers($selectedUserIds);
        $userClass->subscribe($selectedUserIds, $listsSelected);

        $this->listing();
    }
}
