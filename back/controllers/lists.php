<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\TagClass;
use AcyMailing\Classes\UrlClickClass;
use AcyMailing\Helpers\EntitySelectHelper;
use AcyMailing\Helpers\ImportHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Libraries\acymController;

class ListsController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_LISTS')] = acym_completeLink('lists');
        $this->loadScripts = [
            'settings' => ['colorpicker', 'vue-applications' => ['list_subscribers', 'entity_select']],
        ];
    }

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
        $data['allTags'] = $tagClass->getAllTagsByType('list');
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
            acym_translation('ACYM_EXPORT').' (<span id="acym__lists__listing__number_to_export" data-default="0"></span>)',
            ['data-task' => 'export', 'type' => 'submit', 'data-ctrl' => 'users', 'id' => 'acym__list__export'],
            'upload'
        );
        $toolbarHelper->addOtherContent('<input type="hidden" name="preselectList" value="1" />');
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'settings'], 'playlist_add', true);

        $data['toolbar'] = $toolbarHelper;
    }

    public function settings()
    {
        acym_setVar('layout', 'settings');

        $data = [];
        $data['svg'] = acym_loaderLogo(false);

        $listId = acym_getVar('int', 'id', 0);

        if (!$this->prepareListSettings($data, $listId)) return;
        $this->prepareTagsSettings($data, $listId);
        $this->prepareSubscribersSettings($data, $listId);
        $this->prepareSubscribersEntitySelect($data, $listId);
        $this->prepareListStat($data, $listId);
        $this->prepareListStatEvolution($data, $listId);
        $this->prepareWelcomeUnsubData($data);

        parent::display($data);
    }

    protected function prepareListsListing(&$data)
    {
        // Prepare the pagination
        $listsPerPage = $data['pagination']->getListLimit();
        $page = acym_getVar('int', 'lists_pagination_page', 1);

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

    private function prepareListSettings(&$data, $listId)
    {
        if (empty($listId)) {
            $listInformation = new \stdClass();
            $listInformation->id = '';
            $listInformation->name = '';
            $listInformation->description = '';
            $listInformation->active = 1;
            $listInformation->visible = 1;
            $randColor = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];
            $listInformation->color = '#'.$randColor[rand(0, 15)].$randColor[rand(0, 15)].$randColor[rand(0, 15)].$randColor[rand(0, 15)].$randColor[rand(0, 15)].$randColor[rand(
                    0,
                    15
                )];
            $listInformation->welcome_id = '';
            $listInformation->unsubscribe_id = '';
            $listInformation->access = [];
            $listInformation->tracking = 1;

            $this->breadcrumb[acym_translation('ACYM_NEW_LIST')] = acym_completeLink('lists&task=settings');
        } else {
            $listInformation = $this->currentClass->getOneById($listId);
            if (is_null($listInformation)) {
                acym_enqueueMessage(acym_translation('ACYM_LIST_DOESNT_EXIST'), 'error');
                $this->listing();

                return false;
            }

            $subscribersCount = $this->currentClass->getSubscribersCountPerStatusByListId([$listId]);

            $this->breadcrumb[acym_escape($listInformation->name)] = acym_completeLink('lists&task=settings&id='.$listId);

            $listInformation->access = empty($listInformation->access) ? [] : explode(',', $listInformation->access);

            $currentUser = acym_currentUserId();
            if (!acym_isAdmin() && ($listInformation->cms_user_id != $currentUser)) {
                $userGroups = acym_getGroupsByUser($currentUser);

                $canAccess = false;

                foreach ($userGroups as $group) {
                    if (in_array($group, $listInformation->access)) $canAccess = true;
                }

                if (!$canAccess) {
                    acym_enqueueMessage(acym_translation('ACYM_YOU_DONT_HAVE_ACCESS_TO_THIS_LIST'), 'error');

                    $this->listing();

                    return false;
                }
            }
        }

        $listInformation->subscribers = [
            'unsubscribed_users' => 0,
            'sendable_users' => 0,
            'unconfirmed_users' => 0,
            'inactive_users' => 0,
        ];
        if (!empty($subscribersCount)) {
            $listStats = array_shift($subscribersCount);
            $listInformation->subscribers = [
                'unsubscribed_users' => $listStats->unsubscribed_users,
                'sendable_users' => $listStats->sendable_users,
                'unconfirmed_users' => $listStats->unconfirmed_users,
                'inactive_users' => $listStats->inactive_users,
            ];
        }

        $data['listInformation'] = $listInformation;

        return true;
    }

    private function prepareTagsSettings(&$data, $listId)
    {
        $tagClass = new TagClass();
        $data['allTags'] = $tagClass->getAllTagsByType('list');
        $data['listTagsName'] = [];
        $listsTags = $tagClass->getAllTagsByElementId('list', $listId);
        foreach ($listsTags as $oneTag) {
            $data['listTagsName'][] = $oneTag;
        }
    }

    private function prepareSubscribersSettings(&$data, $listId)
    {
        $data['ordering'] = acym_getVar('string', 'users_ordering', 'id');
        $data['orderingSortOrder'] = acym_getVar('string', 'users_ordering_sort_order', 'desc');
        $data['classSortOrder'] = $data['orderingSortOrder'] == 'asc' ? 'acymicon-sort-amount-asc' : 'acymicon-sort-amount-desc';
        $data['subscribers'] = $this->currentClass->getSubscribersForList($listId, 0, 500, 1, $data['ordering'], $data['orderingSortOrder']);
        foreach ($data['subscribers'] as &$oneSub) {
            $oneSub->subscription_date = acym_getDate($oneSub->subscription_date);
        }
    }

    private function prepareSubscribersEntitySelect(&$data, $listId)
    {
        if (empty($listId)) {
            $data['subscribersEntitySelect'] = '';

            return;
        }

        $entityHelper = new EntitySelectHelper();

        $data['subscribersEntitySelect'] = acym_modal(
            acym_translation('ACYM_MANAGE_SUBSCRIBERS'),
            $entityHelper->entitySelect(
                'user',
                ['join' => 'join_list-'.$listId],
                $entityHelper->getColumnsForUser('userlist.user_id'),
                ['text' => acym_translation('ACYM_CONFIRM'), 'action' => 'saveSubscribers']
            ),
            null,
            '',
            'class="cell medium-6 large-shrink button button-secondary"'
        );
    }

    private function prepareListStat(&$data, $listId)
    {
        $data['listStats'] = ['deliveryRate' => 0, 'openRate' => 0, 'clickRate' => 0, 'failRate' => 0, 'bounceRate' => 0];
        if (empty($listId)) return;
        $mails = $this->currentClass->getMailsByListId($listId);
        if (empty($mails)) return;

        $mailStatClass = new MailStatClass();
        $mailsStat = $mailStatClass->getCumulatedStatsByMailIds($mails);

        if (intval($mailsStat->sent) + intval($mailsStat->fails) === 0) return;

        $totalSent = intval($mailsStat->sent) + intval($mailsStat->fails);
        if (empty($mailsStat->open)) $mailsStat->open = 0;
        if (empty($mailsStat->fails)) $mailsStat->fails = 0;
        if (empty($mailsStat->bounces)) $mailsStat->bounces = 0;

        $data['listStats']['openRate'] = number_format($mailsStat->open / $totalSent * 100, 2);
        $data['listStats']['deliveryRate'] = number_format(($mailsStat->sent - $mailsStat->bounces) / $totalSent * 100, 2);
        $data['listStats']['failRate'] = number_format($mailsStat->fails / $totalSent * 100, 2);
        $data['listStats']['bounceRate'] = number_format($mailsStat->bounces / $totalSent * 100, 2);

        $urlClickClass = new UrlClickClass();
        $nbClicks = $urlClickClass->getClickRateByMailIds($mails);
        $data['listStats']['clickRate'] = number_format($nbClicks / $totalSent * 100, 2);
    }

    private function prepareListStatEvolution(&$data, $listId)
    {
        $data['evol'] = [];
        $listClass = new ListClass();
        $subEvolStat = $listClass->getYearSubEvolutionPerList($listId);
        if (empty($subEvolStat['subscribers']) && empty($subEvolStat['unsubscribers'])) return;

        // Init tables ordered by month number starting on month from one year ago
        $firstMonth = date('n') + 1;
        $zeroReached = false;
        $evolSub = [];
        $evolUnsub = [];
        for ($i = 0 ; $i < 12 ; $i++) {
            $month = ($firstMonth + $i) % 13;
            if ($month == 0) $zeroReached = true;
            if ($zeroReached) $month += 1;
            $evolSub[$month] = $month.'_0';
            $evolUnsub[$month] = $month.'_0';
        }

        foreach ($subEvolStat['subscribers'] as $unit => $monthData) {
            $evolSub[$monthData->monthSub] = $monthData->monthSub.'_'.$monthData->nbUser;
        }

        foreach ($subEvolStat['unsubscribers'] as $unit => $monthData) {
            $evolUnsub[$monthData->monthUnsub] = $monthData->monthUnsub.'_'.$monthData->nbUser;
        }

        foreach ($evolSub as $month => $oneEvol) {
            $data['evol'][0][] = $oneEvol;
            $data['evol'][1][] = $evolUnsub[$month];
        }
    }

    protected function prepareWelcomeUnsubData(&$data)
    {
        $data['tmpls'] = [];
        if (empty($data['listInformation']->id)) return;

        $mailClass = new MailClass();

        foreach ([$mailClass::TYPE_WELCOME => 'welcome', $mailClass::TYPE_UNSUBSCRIBE => 'unsub'] as $full => $short) {
            $mailId = acym_getVar('int', $short.'mailid', 0);
            if (empty($data['listInformation']->{$full.'_id'}) && !empty($mailId)) {
                $data['listInformation']->{$full.'_id'} = $mailId;
                $listInfoSave = clone $data['listInformation'];
                unset($listInfoSave->subscribers);
                if (!$this->currentClass->save($listInfoSave)) acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVE_LIST'), 'error');
            }

            $returnLink = acym_completeLink('lists&task=settings&id='.$data['listInformation']->id.'&edition=1&'.$short.'mailid={mailid}');
            if (empty($data['listInformation']->{$full.'_id'})) {
                $data['tmpls'][$short.'TmplUrl'] = acym_completeLink(
                    'mails&task=edit&step=editEmail&type='.$full.'&type_editor=acyEditor&list_id='.$data['listInformation']->id.'&return='.urlencode(base64_encode($returnLink))
                );
            } else {
                $data['tmpls'][$short.'TmplUrl'] = acym_completeLink(
                    'mails&task=edit&id='.$data['listInformation']->{$full.'_id'}.'&type='.$full.'&list_id='.$data['listInformation']->id.'&return='.urlencode(
                        base64_encode($returnLink)
                    )
                );
            }

            $data['tmpls'][$full] = !empty($data['listInformation']->{$full.'_id'}) ? $mailClass->getOneById($data['listInformation']->{$full.'_id'}) : '';
        }
    }

    public function unsetMail($type)
    {
        $id = acym_getVar('int', 'id', 0);
        $list = $this->currentClass->getOneById($id);

        if (empty($list)) {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVE_LIST'), 'error');
            $this->listing();

            return;
        }

        $list->$type = null;

        if ($this->currentClass->save($list)) {
            acym_setVar('id', $id);
            $this->settings();
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVE_LIST'), 'error');
            $this->listing();
        }
    }

    public function unsetWelcome()
    {
        $this->unsetMail('welcome_id');
    }

    public function unsetUnsubscribe()
    {
        $this->unsetMail('unsubscribe_id');
    }

    public function apply()
    {
        $this->save(false);
    }

    public function save($goToListing = true)
    {
        acym_checkToken();

        $formData = (object)acym_getVar('array', 'list', []);

        $listId = acym_getVar('int', 'id', 0);
        if (!empty($listId)) {
            $formData->id = $listId;
        }

        $allowedFields = acym_getColumns('list');
        $listInformation = new \stdClass();
        if (empty($formData->welcome_id)) unset($formData->welcome_id);
        if (empty($formData->unsubscribe_id)) unset($formData->unsubscribe_id);
        foreach ($formData as $name => $data) {
            if (!in_array($name, $allowedFields)) {
                continue;
            }
            $listInformation->{$name} = $data;
        }

        $listInformation->tags = acym_getVar('array', 'list_tags', []);

        if (acym_isAdmin()) $listInformation->access = empty($listInformation->access) ? '' : ','.implode(',', $listInformation->access).',';

        $listId = $this->currentClass->save($listInformation);

        if (!empty($listId)) {
            acym_setVar('id', $listId);
            acym_enqueueMessage(acym_translationSprintf('ACYM_LIST_IS_SAVED', $listInformation->name), 'success');
            $this->_saveSubscribersTolist();
        } else {
            acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVING'), 'error');
            if (!empty($this->currentClass->errors)) {
                acym_enqueueMessage($this->currentClass->errors, 'error');
            }
        }
        if ($goToListing) {
            return $this->listing();
        } else {
            return $this->settings();
        }
    }

    private function _saveSubscribersTolist()
    {
        $usersIds = json_decode(acym_getVar('string', 'acym__entity_select__selected', '[]'));
        $usersIdsUnselected = json_decode(acym_getVar('string', 'acym__entity_select__unselected', '[]'));
        $listId = acym_getVar('int', 'id', 0);

        if (empty($listId)) return false;

        acym_arrayToInteger($usersIdsUnselected);
        if (!empty($usersIdsUnselected)) {
            acym_query(
                'UPDATE #__acym_user_has_list SET status = 0, unsubscribe_date = '.acym_escapeDB(acym_date(time(), 'Y-m-d H:i:s')).' WHERE list_id = '.intval(
                    $listId
                ).' AND user_id IN ('.implode(', ', $usersIdsUnselected).')'
            );
        }

        acym_arrayToInteger($usersIds);
        if (!empty($usersIds)) {
            acym_query(
                'INSERT IGNORE #__acym_user_has_list (`user_id`, `list_id`, `status`, `subscription_date`) (SELECT id, '.intval($listId).', 1, '.acym_escapeDB(
                    acym_date(time(), 'Y-m-d H:i:s')
                ).' FROM #__acym_user AS user WHERE user.id IN ('.implode(', ', $usersIds).')) ON DUPLICATE KEY UPDATE status = 1'
            );
        }

        return true;
    }

    /**
     * Save list subscribers
     */
    public function saveSubscribers()
    {
        $this->_saveSubscribersTolist();
        acym_checkToken();
        $listId = acym_getVar('int', 'id', 0);
        acym_setVar('id', $listId);

        $this->settings();
    }

    public function setVisible()
    {
        acym_checkToken();
        $ids = acym_getVar('array', 'elements_checked', []);

        if (!empty($ids)) {
            $this->currentClass->setVisible($ids, 1);
        }

        $this->listing();
    }

    public function setInvisible()
    {
        acym_checkToken();
        $ids = acym_getVar('array', 'elements_checked', []);

        if (!empty($ids)) {
            $this->currentClass->setVisible($ids, 0);
        }

        $this->listing();
    }

    public function loadMoreSubscribers()
    {
        acym_checkToken();
        $listId = acym_getVar('int', 'listid');
        $offset = acym_getVar('int', 'offset');
        $perCalls = acym_getVar('int', 'perCalls');
        $status = acym_getVar('int', 'status');
        $orderBy = acym_getVar('string', 'orderBy', 'id');
        $orderingSortOrder = acym_getVar('string', 'orderByOrdering', 'desc');
        $subscribers = $this->currentClass->getSubscribersForList($listId, $offset, $perCalls, $status, $orderBy, $orderingSortOrder);
        foreach ($subscribers as &$oneSub) {
            $oneSub->subscription_date = acym_getDate($oneSub->subscription_date);
        }
        echo json_encode(['data' => $subscribers]);
        exit;
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

        $lists = $this->currentClass->getListsWithIdNameCount($params);

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

    public function ajaxGetLists()
    {
        $subscribedListsIds = acym_getVar('string', 'ids');
        $echo = '';

        $subscribedListsIds = explode(',', $subscribedListsIds);

        $allLists = $this->currentClass->getListsByIds($subscribedListsIds);

        foreach ($allLists as $list) {
            $echo .= '<div class="grid-x cell acym__listing__row">
                        <div class="grid-x medium-5 cell acym__users__display__list__name">
                            <i class="cell shrink acymicon-circle" style="color:'.$list->color.'"></i>
                            <h6 class="cell auto">'.$list->name.'</h6>
                        </div>
                        <div class="medium-2 hide-for-small-only cell text-center acym__users__display__subscriptions__opening"></div>
                        <div class="medium-2 hide-for-small-only cell text-center acym__users__display__subscriptions__clicking"></div>
                        <div id="'.$list->id.'" class="medium-3 cell acym__users__display__list--action acym__user__action--remove">
                            <i class="acymicon-times-circle"></i>
                            <span>'.acym_translation('ACYM_REMOVE').'</span>
                        </div>
                    </div>';
        }
        $return = [];
        $return['html'] = $echo;
        $return['notif'] = acym_translationSprintf('ACYM_X_CONFIRMATION_SUBSCRIPTION_ADDED_AND_CLICK_TO_SAVE', count($allLists));
        $return = json_encode($return);
        echo $return;
        exit;
    }

    public function ajaxCreateNewList()
    {
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
}
