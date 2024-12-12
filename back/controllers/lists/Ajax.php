<?php

namespace AcyMailing\Controllers\Lists;

use AcyMailing\Classes\ListClass;
use AcyMailing\Helpers\EntitySelectHelper;
use AcyMailing\Helpers\ImportHelper;
use AcyMailing\Helpers\PaginationHelper;

trait Ajax
{
    public function ajaxCreateNewList()
    {
        acym_checkToken();
        $genericImport = acym_getVar('boolean', 'generic', false);
        $selectedListsIds = json_decode(acym_getVar('string', 'selected', '[]'));

        $listClass = new ListClass();
        $listToAdd = new \stdClass();
        $listToAdd->name = acym_getVar('string', 'list_name', '');
        $listToAdd->color = '#'.substr(str_shuffle('ABCDEF0123456789'), 0, 6);
        $listToAdd->visible = 1;
        $listToAdd->active = 1;

        $selectedListsIds[] = $listClass->save($listToAdd);

        $entityHelper = new EntitySelectHelper();
        $importHelper = new ImportHelper();


        $return = $entityHelper->entitySelect(
            'list',
            ['join' => 'join_lists-'.implode(',', $selectedListsIds)],
            $entityHelper->getColumnsForList('lists.list_id', true),
            [],
            true,
            $importHelper->additionalDataUsersImport($genericImport)
        );

        echo $return;
        exit;
    }

    public function usersSummary()
    {
        $id = acym_getVar('int', 'list_id', 0);
        $offset = acym_getVar('int', 'offset', 0);
        $limit = acym_getVar('int', 'limit', 50);
        $search = acym_getVar('string', 'modal_search', '');

        if (empty($id)) {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_RETRIEVE_DATA'), [], false);
        }

        $listClass = new ListClass();
        if (!$listClass->hasUserAccess($id)) {
            acym_sendAjaxResponse(acym_translation('ACYM_UNAUTHORIZED_ACCESS'), [], false);
        }

        acym_sendAjaxResponse('', ['users' => $listClass->getUsersForSummaryModal($id, $offset, $limit, $search)]);
    }

    public function setAjaxListing()
    {
        $showSelected = acym_getVar('string', 'show_selected');
        $matchingListsData = new \stdClass();
        $matchingListsData->ordering = 'name';
        $matchingListsData->searchFilter = acym_getVar('string', 'search_lists');
        $matchingListsData->listsPerPage = acym_getVar('string', 'listsPerPage');
        $matchingListsData->idsSelected = json_decode(acym_getVar('string', 'selectedLists'));
        $matchingListsData->idsAlready = json_decode(acym_getVar('string', 'alreadyLists'));
        $matchingListsData->page = acym_getVar('int', 'pagination_page_ajax');
        if (empty($matchingListsData->page)) $matchingListsData->page = 1;

        $matchingListsData->needDisplaySub = acym_getVar('int', 'needDisplaySub');
        $matchingListsData->displayNonActive = acym_getVar('int', 'nonActive');

        $params = [
            'ordering' => $matchingListsData->ordering,
            'search' => $matchingListsData->searchFilter,
            'listsPerPage' => $matchingListsData->listsPerPage,
            'offset' => ($matchingListsData->page - 1) * $matchingListsData->listsPerPage,
            'already' => $matchingListsData->idsAlready,
        ];

        if ($showSelected == 'true') {
            $params['ids'] = $matchingListsData->idsSelected;
        }

        $listClass = new ListClass();
        $lists = $listClass->getListsWithIdNameCount($params);

        $return = '';

        if (empty($lists['lists'])) {
            $return .= '<h1 class="cell acym__listing__empty__search__modal text-center">'.acym_translation('ACYM_NO_RESULTS_FOUND').'</h1>';
        }

        foreach ($lists['lists'] as $list) {
            if (!empty($matchingListsData->displayNonActive) && $list->active == 0) continue;

            $return .= '<div class="grid-x modal__pagination__listing__lists__in-form__list cell">';

            $return .= '<div class="cell shrink"><input type="checkbox" id="modal__pagination__listing__lists__list'.acym_escape($list->id).'" value="'.acym_escape(
                    $list->id
                ).'" class="modal__pagination__listing__lists__list--checkbox" name="lists_checked[]"';

            if (!empty($matchingListsData->idsSelected) && in_array($list->id, $matchingListsData->idsSelected)) {
                $return .= 'checked';
            }

            $return .= '></div><i class="cell shrink acymicon-circle" style="color:'.acym_escape(
                    $list->color
                ).'"></i><label class="cell auto" for="modal__pagination__listing__lists__list'.acym_escape($list->id).'"> ';

            $return .= '<span class="modal__pagination__listing__lists__list-name">'.acym_escape($list->name).'</span>';

            if (!empty($matchingListsData->needDisplaySub)) {
                $return .= '<span class="modal__pagination__listing__lists__list-subscribers">('.acym_escape($list->subscribers).')</span>';
            }

            $return .= '</label></div>';
        }

        $pagination = new PaginationHelper();
        $pagination->setStatus($lists['total'], $matchingListsData->page, $matchingListsData->listsPerPage);

        $return .= $pagination->displayAjax();

        echo $return;
        exit;
    }

    public function loadMoreSubscribers()
    {
        acym_checkToken();
        $listClass = new ListClass();

        $listId = acym_getVar('int', 'listId');

        if (!acym_isAdmin()) {
            $manageableLists = $listClass->getManageableLists();
            if (!in_array($listId, $manageableLists)) {
                die('Access denied for this list');
            }
        }

        $offset = acym_getVar('int', 'offset');
        $perCalls = acym_getVar('int', 'perCalls');
        $status = acym_getVar('int', 'status');
        $orderBy = acym_getVar('string', 'orderBy', 'id');
        $orderingSortOrder = acym_getVar('string', 'orderByOrdering', 'desc');
        $subscribers = $listClass->getSubscribersForList(
            [
                'listIds' => [$listId],
                'offset' => $offset,
                'limit' => $perCalls,
                'status' => $status,
                'orderBy' => $orderBy,
                'orderBySort' => $orderingSortOrder,
            ]
        );
        foreach ($subscribers as &$oneSub) {
            if ($oneSub->subscription_date == '0000-00-00 00:00:00') continue;
            $oneSub->subscription_date = acym_date(strtotime($oneSub->subscription_date), acym_translation('ACYM_DATE_FORMAT_LC2'));
        }
        acym_sendAjaxResponse('', ['subscribers' => $subscribers]);
    }
}
