<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\HistoryClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailpoetClass;
use AcyMailing\Classes\SegmentClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\UserStatClass;
use AcyMailing\Helpers\EncodingHelper;
use AcyMailing\Helpers\EntitySelectHelper;
use AcyMailing\Helpers\ExportHelper;
use AcyMailing\Helpers\ImportHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\TabHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Libraries\acymController;

class UsersController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_SUBSCRIBERS')] = acym_completeLink('users');
        $this->loadScripts = [
            'edit' => ['datepicker'],
            'all' => ['vue-applications' => ['entity_select']],
        ];
    }

    public function listing()
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

    protected function prepareSegmentField(&$data)
    {
        $segmentClass = new SegmentClass();
        $segments = $segmentClass->getAllForSelect();
        $data['segments'] = [];
        foreach ($segments as $id => $name) {
            if (empty($id)) $id = 0;
            $data['segments'][$id] = $name;
        }
    }

    protected function prepareToolbar(&$data)
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
            '<i class="acymicon-bell1"></i>'.acym_translation('ACYM_SUBSCRIBE').' (<span id="acym__users__listing__number_to_add_to_list">0</span>)',
            $entityHelper->entitySelect('list', ['join' => ''], $entityHelper->getColumnsForList(), [
                'text' => acym_translation('ACYM_SUBSCRIBE_USERS_TO_THESE_LISTS'),
                'action' => 'addToList',
            ]),
            null,
            '',
            [
                'class' => 'button button-secondary disabled cell medium-6 large-shrink',
                'id' => 'acym__users__listing__button--add-to-list',
            ]
        );
        $toolbarHelper->addOtherContent($otherContent);
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'edit'], 'user-plus', true);

        $data['toolbar'] = $toolbarHelper;
    }

    protected function prepareListingFilters(&$data)
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
            if ($oneList->type === $listClass::LIST_TYPE_FRONT) continue;
            if ($oneList->type === $listClass::LIST_TYPE_FOLLOWUP) {
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

    protected function prepareUsersListing(&$data)
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
        $data['pagination']->setStatus($matchingUsers['total'], $page, $usersPerPage);

        $data['allUsers'] = $matchingUsers['elements'];
        $data['userNumberPerStatus'] = $matchingUsers['status'];
    }

    protected function prepareUsersSubscriptions(&$data)
    {
        $usersId = [];
        foreach ($data['allUsers'] as $oneUser) {
            $usersId[] = $oneUser->id;
        }

        $subscriptions = [];

        if (!empty($usersId)) {
            $subscriptionsArray = $this->currentClass->getUsersSubscriptionsByIds($usersId);

            foreach ($subscriptionsArray as $oneSubscription) {
                $subscriptions[$oneSubscription->user_id][$oneSubscription->id] = $oneSubscription;
            }
        }

        $data['usersSubscriptions'] = $subscriptions;
    }

    protected function prepareUsersFields(&$data)
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

    public function edit()
    {
        acym_setVar('layout', 'edit');

        $data = [];
        $data['tab'] = new TabHelper();

        $userId = acym_getVar('int', 'id', 0);

        if (!$this->prepareUserEdit($data, $userId)) return;
        $this->prepareEntitySelectEdit($data, $userId);
        $this->prepareUserFieldsEdit($data, $userId);
        $this->prepareSubscriptionsEdit($data, $userId);
        $this->prepareStatsEdit($data, $userId);
        $this->prepareHistoryEdit($data, $userId);
        $this->prepareMailHistory($data, $userId);
        $this->prepareFieldsEdit($data);

        parent::display($data);
    }

    private function prepareUserEdit(&$data, $userId)
    {
        if (empty($userId)) {
            $data['user-information'] = new \stdClass();
            $data['user-information']->name = '';
            $data['user-information']->email = '';
            $data['user-information']->active = '1';
            $data['user-information']->confirmed = '1';
            $data['user-information']->cms_id = null;
            $data['user-information']->tracking = 1;
            $data['user-information']->language = '';

            $this->breadcrumb[acym_escape(acym_translation('ACYM_NEW_SUBSCRIBER'))] = acym_completeLink('users&task=edit');
        } else {
            $data['user-information'] = $this->currentClass->getOneById($userId);

            if (empty($data['user-information'])) {
                acym_enqueueMessage(acym_translation('ACYM_USER_NOT_FOUND'), 'error');
                $this->listing();

                return false;
            }

            $this->breadcrumb[acym_escape($data['user-information']->email)] = acym_completeLink('users&task=edit&id='.$userId);
        }

        if (empty($data['user-information']->language)) {
            $data['user-information']->language = acym_getLanguageTag();
        }

        return true;
    }

    private function prepareEntitySelectEdit(&$data, $userId)
    {
        if (empty($userId)) return;

        $entityHelper = new EntitySelectHelper();

        $columnsToDisplay = $entityHelper->getColumnsForList('userlist.list_id');

        $data['entityselect'] = acym_modal(
            acym_translation('ACYM_MANAGE_SUBSCRIPTION'),
            $entityHelper->entitySelect('list', ['join' => 'join_user-'.$userId], $columnsToDisplay, ['text' => acym_translation('ACYM_CONFIRM'), 'action' => 'apply']),
            null,
            '',
            'class="cell medium-6 large-shrink button button-secondary"'
        );
    }

    private function prepareUserFieldsEdit(&$data, $userId)
    {
        $data['fieldsValues'] = [];

        if (empty($userId)) return;

        $fieldClass = new FieldClass();
        $fieldsValues = $fieldClass->getFieldsValueByUserId($userId);
        foreach ($fieldsValues as $one) {
            $data['fieldsValues'][$one->field_id] = $one->value;
        }
    }

    private function prepareSubscriptionsEdit(&$data, $userId)
    {
        $data['subscriptionsIds'] = [];
        $data['subscriptions'] = [];
        $data['unsubscribe'] = [];

        if (empty($userId)) return;

        $data['allSubscriptions'] = $this->currentClass->getUserSubscriptionById($userId);

        $data['subscriptions'] = [];
        $data['unsubscribe'] = [];

        foreach ($data['allSubscriptions'] as $sub) {
            if ($sub->status == 1) {
                $data['subscriptions'][] = $sub;
            } else {
                $data['unsubscribe'][] = $sub;
            }
        }

        $data['subscriptionsIds'] = [];

        if (!empty($data['subscriptions'])) {
            $data['subscriptionsIds'] = [];
            foreach ($data['subscriptions'] as $list) {
                $data['subscriptionsIds'][] = $list->id;
            }

            acym_arrayToInteger($data['subscriptionsIds']);
        }
    }

    private function prepareStatsEdit(&$data, $userId)
    {
        $data['pourcentageOpen'] = 0;
        $data['pourcentageClick'] = 0;

        if (empty($userId)) return;

        $userStatClass = new UserStatClass();
        $userStatFromDB = $userStatClass->getAllUserStatByUserId($userId);

        if (empty($userStatFromDB)) return;

        $userStat = new \stdClass();
        $userStat->totalSent = 0;
        $userStat->open = 0;

        foreach ($userStatFromDB as $oneStat) {
            if ($oneStat->sent > 0) $userStat->totalSent++;
            if ($oneStat->open > 0) $userStat->open++;
        }

        $userStat->pourcentageOpen = empty($userStat->open) ? 0 : intval(($userStat->open * 100) / $userStat->totalSent);

        $data['pourcentageOpen'] = $userStat->pourcentageOpen;
        $data['pourcentageClick'] = $userStat->pourcentageOpen;
    }

    private function prepareMailHistory(&$data, $userId)
    {
        if (empty($userId)) return;
        $data['userMailHistory'] = $this->currentClass->getMailHistory($userId);
    }

    private function prepareHistoryEdit(&$data, $userId)
    {
        if (empty($userId)) return;

        $historyClass = new HistoryClass();
        $data['userHistory'] = $historyClass->getHistoryOfOneById($userId);
        foreach ($data['userHistory'] as &$oneHistory) {
            if (!empty($oneHistory->data)) {
                $historyData = explode("\n", $oneHistory->data);
                $details = '<div><h5>'.acym_translation('ACYM_DETAILS').'</h5><br />';
                if (!empty($oneHistory->mail_id)) {
                    $details .= '<b>'.acym_translation('NEWSLETTER').' : </b>';
                    $details .= acym_escape($oneHistory->subject).' ( '.acym_translation('ACYM_ID').' : '.$oneHistory->mail_id.' )<br />';
                }

                foreach ($historyData as $value) {
                    if (!strpos($value, '::')) {
                        $details .= $value.'<br />';
                        continue;
                    }
                    list($part1, $part2) = explode('::', $value);
                    if (preg_match('#^[A-Z_]*$#', $part2)) $part2 = acym_translation($part2);
                    $details .= '<b>'.acym_escape(acym_translation($part1)).' : </b>'.acym_escape($part2).'<br />';
                }
                if ($oneHistory->action === 'unsubscribed') {
                    $details .= acym_translation('ACYM_UNSUBSCRIBE_REASON');
                    if (empty(acym_escape($oneHistory->unsubscribe_reason))) {
                        $details .= ' '.acym_translation('ACYM_NO_REASON_SET_BY_USER');
                    } else {
                        $details .= ' '.acym_escape($oneHistory->unsubscribe_reason);
                    }
                }

                $details .= '</div>';

                $oneHistory->data = acym_modal(
                    acym_translation('ACYM_VIEW_DETAILS'),
                    $details,
                    null,
                    'style="word-break: break-word;"',
                    'class="history_details"',
                    true,
                    false
                );
            }

            if (!empty($oneHistory->source)) {
                $source = explode("\n", $oneHistory->source);
                $details = '<div><h5>'.acym_translation('ACYM_SOURCE').'</h5><br />';
                foreach ($source as $value) {
                    if (!strpos($value, '::')) continue;
                    list($part1, $part2) = explode('::', $value);
                    $details .= '<b>'.acym_escape($part1).' : </b>'.acym_escape($part2).'<br />';
                }
                $details .= '</div>';

                $oneHistory->source = acym_modal(
                    acym_translation('ACYM_VIEW_SOURCE'),
                    $details,
                    null,
                    'style="word-break: break-word;"',
                    'class="history_details"'
                );
            }
        }
    }

    protected function prepareFieldsEdit(&$data, $fieldVisibility = 'backend_edition')
    {
        $data['allFields'] = [];

        $fieldClass = new FieldClass();
        $fieldsElements = $fieldClass->getMatchingElements();
        $allFields = $fieldsElements['elements'];
        $languageFieldId = $fieldClass->getLanguageFieldId();

        foreach ($allFields as $one) {
            $one->option = json_decode($one->option);
            $one->value = empty($one->value) ? '' : json_decode($one->value);
            $fieldDB = empty($one->option->fieldDB) ? '' : json_decode($one->option->fieldDB);

            // Keep this code, search for data-display-optional for more info
            //$displayIf = empty($one->option->display) ? '' : 'data-display-optional="'.acym_escape($one->option->display).'"';

            $valuesArray = [];
            if (!empty($one->value)) {
                foreach ($one->value as $value) {
                    $valueTmp = new \stdClass();
                    $valueTmp->text = $value->title;
                    $valueTmp->value = $value->value;
                    if ($value->disabled == 'y') $valueTmp->disable = true;
                    $valuesArray[$value->value] = $valueTmp;
                }
            }
            if (!empty($fieldDB) && !empty($fieldDB->value)) {
                $fromDB = $fieldClass->getValueFromDB($fieldDB);
                foreach ($fromDB as $value) {
                    $valuesArray[$value->value] = $value->title;
                }
            }

            $one->display = empty($one->option->display) ? '' : json_decode($one->option->display);
            $data['allFields'][$one->id] = $one;
            if ($one->id == 1) {
                $defaultValue = empty($data['user-information']->id) ? '' : $data['user-information']->name;
            } elseif ($one->id == 2) {
                $defaultValue = empty($data['user-information']->id) ? '' : $data['user-information']->email;
            } elseif ($one->id == $languageFieldId) {
                $defaultValue = empty($data['user-information']->id) ? acym_getLanguageTag() : $data['user-information']->language;
            } elseif (isset($data['fieldsValues'][$one->id]) && (((is_array($data['fieldsValues'][$one->id]) || $data['fieldsValues'][$one->id] instanceof Countable) && count(
                            $data['fieldsValues'][$one->id]
                        ) > 0) || (is_string($data['fieldsValues'][$one->id]) && strlen($data['fieldsValues'][$one->id]) > 0))) {
                $decoded = json_decode($data['fieldsValues'][$one->id]);
                $defaultValue = is_null($decoded) ? $data['fieldsValues'][$one->id] : $decoded;
            } else {
                $defaultValue = $one->default_value;
            }
            $size = empty($one->option->size) ? '' : 'width:'.$one->option->size.'px';

            $data['allFields'][$one->id]->html = $fieldClass->displayField($one, $defaultValue, $size, $valuesArray, true, !acym_isAdmin(), null, $one->$fieldVisibility);
        }
    }

    public function import()
    {
        acym_setVar('layout', 'import');

        $tab = new TabHelper();

        $nbUsersAcymailing = $this->currentClass->getCountTotalUsers();
        $nbUsersCMS = acym_loadResult('SELECT count('.$this->cmsUserVars->id.') FROM '.$this->cmsUserVars->table);

        // Get tables from database
        $tables = acym_getTables();
        $arrayTables = [];
        foreach ($tables as $key => $tableName) {
            $arrayTables[$tableName] = $tableName;
        }

        $data = [
            'tab' => $tab,
            'nbUsersAcymailing' => $nbUsersAcymailing,
            'nbUsersCMS' => $nbUsersCMS,
            'tables' => $arrayTables,
            'entitySelect' => new EntitySelectHelper(),
            'importHelper' => new ImportHelper(),
        ];

        //__START__wordpress_
        if (ACYM_CMS === 'wordpress' && acym_isExtensionActive('mailpoet/mailpoet.php')) {
            $this->prepareMailPoetList($data);
        }
        //__END__wordpress_

        $this->breadcrumb[acym_translation('ACYM_IMPORT')] = acym_completeLink('users&task=import');
        $data['menuClass'] = $this->menuClass;

        parent::display($data);
    }

    //__START__wordpress_
    private function prepareMailPoetList(&$data)
    {
        $mailpoetClass = new MailpoetClass();
        $data['mailpoet_list'] = $mailpoetClass->getAllLists();
    }

    //__END__wordpress_

    public function ajaxEncoding()
    {
        acym_setVar('layout', 'ajaxencoding');
        parent::display();
        exit;
    }

    public function doImport()
    {
        acym_checkToken();

        $function = acym_getVar('cmd', 'import_from');
        $importHelper = new ImportHelper();

        if (empty($function) || !$importHelper->$function()) {
            $this->import();

            return;
        }

        if ($function == 'textarea' || $function == 'file') {
            $importFile = ACYM_MEDIA.'import'.DS.acym_getVar('cmd', 'filename');
            if (file_exists($importFile)) {
                $importContent = file_get_contents($importFile);
            }
            if (empty($importContent)) {
                acym_enqueueMessage(acym_translation('ACYM_EMPTY_TEXTAREA'), 'error');
                $this->import();
            } else {
                acym_setVar('layout', 'genericimport');
                $this->breadcrumb[acym_translation('ACYM_IMPORT')] = acym_completeLink('users&task=import');
                parent::display();

                return;
            }
        } else {
            $this->listing();
        }
    }

    public function finalizeImport()
    {
        $importHelper = new ImportHelper();
        $importHelper->finalizeImport();

        $this->listing();
    }

    public function downloadImport()
    {
        $filename = acym_getVar('cmd', 'filename');
        if (!file_exists(ACYM_MEDIA.'import'.DS.$filename.'.csv')) {
            return;
        }
        $exportHelper = new ExportHelper();
        $exportHelper->setDownloadHeaders($filename);
        echo file_get_contents(ACYM_MEDIA.'import'.DS.$filename.'.csv');
        exit;
    }

    public function getAll()
    {
        return $this->currentClass->getAll();
    }

    /**
     * Export page where the user selects the export option
     */
    public function export()
    {
        acym_setVar('layout', 'export');
        $this->breadcrumb[acym_translation('ACYM_EXPORT_SUBSCRIBERS')] = acym_completeLink('users&task=export');

        $listClass = new ListClass();
        $lists = $listClass->getAll();

        $preselectList = acym_getVar('boolean', 'preselectList', false);
        $checkedElements = acym_getVar('array', 'elements_checked', []);

        $filtersListing = [];

        $filtersListing['list'] = $this->getVarFiltersListing('int', 'users_list', 0);
        $filtersListing['list_status'] = $this->getVarFiltersListing('string', 'list_status', 'all');

        $list = acym_getVar('int', 'users_list', 0);
        if (!empty($list)) {
            $preselectList = true;
            $checkedElements = [$list];
        }

        if (!empty($filtersListing['list'])) {
            $preselectList = true;
            $checkedElements = [$filtersListing['list']];
        }

        $fields = acym_getColumns('user');

        // Get custom fields and exclude name and email as we already have them
        $fieldClass = new FieldClass();
        $customFields = $fieldClass->getAll();

        $entityHelper = new EntitySelectHelper();
        $encodingHelper = new EncodingHelper();
        $userClass = new UserClass();

        if ($preselectList) {
            $entitySelect = $entityHelper->entitySelect('list', ['join' => 'join_lists-'.implode(',', $checkedElements)], $entityHelper->getColumnsForList('lists.list_id', true));
        } else {
            $entitySelect = $entityHelper->entitySelect('list', ['join' => ''], $entityHelper->getColumnsForList('', true));
        }

        $data = [
            'lists' => $lists,
            'checkedElements' => $checkedElements,
            'fields' => $fields,
            'customfields' => $customFields,
            'isPreselectedList' => $preselectList,
            'entitySelect' => $entitySelect,
            'exportListStatus' => $filtersListing['list_status'],
            'encodingHelper' => $encodingHelper,
            'userClass' => $userClass,
        ];

        parent::display($data);
    }

    /**
     * This method downloads the exported file directly
     *
     * @return bool
     */
    public function doexport()
    {
        acym_checkToken();
        acym_increasePerf();

        // Get passed data and check if we have everything we need
        $usersToExport = acym_getVar('string', 'export_users-to-export', 'all');
        $listsToExport = json_decode(acym_getVar('string', 'acym__entity_select__selected', '{}'));
        if ($usersToExport == 'list' && empty($listsToExport)) {
            acym_enqueueMessage(acym_translation('ACYM_EXPORT_SELECT_LIST'), 'error');

            return $this->exportError(acym_translation('ACYM_EXPORT_SELECT_LIST'));
        }
        acym_arrayToInteger($listsToExport);

        $exportUsersType = 'all';
        if ($usersToExport == 'list') {
            $exportUsersType = acym_getVar('string', 'export_list', 'all');
        }

        $selectedUsers = acym_getVar('string', 'selected_users', null);

        if (!empty($selectedUsers)) {
            $selectedUsersArray = explode(',', $selectedUsers);
            acym_arrayToInteger($selectedUsersArray);
        }

        // Make sure the user selected fields and didn't inject something
        $fieldsToExport = acym_getVar('array', 'export_fields', []);
        if (empty($fieldsToExport)) {
            if (!empty($selectedUsersArray)) {
                acym_setVar('elements_checked', $selectedUsersArray);
            } else {
                acym_setVar('elements_checked', []);
            }

            return $this->exportError(acym_translation('ACYM_EXPORT_SELECT_FIELD'));
        }

        $tableFields = acym_getColumns('user');
        $fieldClass = new FieldClass();
        $customFields = $fieldClass->getAll();

        $customFieldsToExport = [];

        foreach ($fieldsToExport as $i => $oneField) {
            if (empty($customFields[$oneField])) continue;
            $customFieldsToExport[$oneField] = $customFields[$oneField]->namekey;
            unset($fieldsToExport[$i]);
        }

        $notAllowedFields = array_diff($fieldsToExport, $tableFields);
        if (in_array('id', $fieldsToExport)) $notAllowedFields[] = 'id';
        if (!empty($notAllowedFields)) {
            return $this->exportError(acym_translationSprintf('ACYM_NOT_ALLOWED_FIELDS', implode(', ', $notAllowedFields), implode(', ', $tableFields)));
        }

        $charset = acym_getVar('string', 'export_charset', 'UTF-8');
        $excelsecurity = acym_getVar('string', 'export_excelsecurity', 0);
        $separator = acym_getVar('string', 'export_separator', 'comma');
        $realSeparators = ['comma' => ',', 'semicol' => ';'];
        if (!in_array($separator, ['comma', 'semicol'])) {
            $separator = 'comma';
        }


        // Save the selected options for the next time
        $newConfig = new \stdClass();
        $newConfig->export_separator = $separator;
        $newConfig->export_charset = $charset;
        $newConfig->export_excelsecurity = $excelsecurity;
        $newConfig->export_fields = implode(',', array_merge($fieldsToExport, array_keys($customFieldsToExport)));
        if (empty($selectedUsers)) {
            $newConfig->export_lists = implode(',', $listsToExport);
        }
        $this->config->save($newConfig);

        // Prepare the export query
        foreach ($fieldsToExport as $oneField) {
            acym_secureDBColumn($oneField);
        }
        $query = 'SELECT DISTINCT user.`id`, user.`'.implode('`, user.`', $fieldsToExport).'` FROM #__acym_user AS user';

        $where = [];

        if (!empty($selectedUsersArray)) {
            acym_arrayToInteger($selectedUsersArray);
            $where[] = 'user.id IN ('.implode(',', $selectedUsersArray).')';
        } elseif ($usersToExport == 'list' && !empty($listsToExport)) {
            acym_arrayToInteger($listsToExport);

            $listJoin = '#__acym_user_has_list AS userlist ON userlist.user_id = user.id AND userlist.list_id IN ('.implode(',', $listsToExport).')';

            if ($exportUsersType == 'none') {
                $query .= ' LEFT JOIN '.$listJoin;
                $where[] = 'userlist.status IS NULL';
            } else {
                $query .= ' JOIN '.$listJoin;
            }
            if ($exportUsersType == 'sub') $where[] = 'userlist.status = 1';
            if ($exportUsersType == 'unsub') $where[] = 'userlist.status = 0';
        }

        $filtersListingSearch = $this->getVarFiltersListing('string', 'users_search', '');
        $filtersListingStatus = $this->getVarFiltersListing('string', 'users_status', '');

        if (!empty($filtersListingSearch)) {
            $search = acym_escapeDB('%'.$filtersListingSearch.'%');
            $where[] = 'user.name LIKE '.$search.' OR user.email LIKE '.$search.' OR user.id LIKE '.$search;
        }

        if (!empty($filtersListingStatus)) {
            if ($filtersListingStatus == 'active') {
                $where[] = 'user.active = 1';
            } elseif ($filtersListingStatus == 'inactive') {
                $where[] = 'user.active = 0';
            } elseif ($filtersListingStatus == 'confirmed') {
                $where[] = 'user.confirmed = 1';
            } else {
                $where[] = 'user.confirmed = 0';
            }
        }

        if (!empty($where)) $query .= ' WHERE ('.implode(') AND (', $where).')';

        // We have all we need for the export, prepare the headers for the download
        $exportHelper = new ExportHelper();
        $exportHelper->exportCSV($query, $fieldsToExport, $customFieldsToExport, $realSeparators[$separator], $charset);

        exit;
    }

    /**
     * Aborts export and displays an error message
     *
     * @param string|array $message
     *
     * @return bool
     */
    private function exportError($message)
    {
        acym_enqueueMessage($message, 'error', 0);
        acym_setNoTemplate(false);

        return acym_redirect(acym_completeLink('users&task=export', false, true));
    }

    public function resetSubscription()
    {
        $userId = acym_getVar('int', 'id');

        if (empty($userId)) {
            $this->listing();

            return;
        }

        $list = acym_getVar('int', 'acym__entity_select__selected');
        $this->currentClass->resetSubscription($userId, [$list]);

        $this->edit();
    }

    public function unsubscribeUser()
    {
        $userId = acym_getVar('int', 'id');

        if (empty($userId)) {
            $this->listing();

            return;
        }

        $lists = json_decode(acym_getVar('string', 'acym__entity_select__selected', '{}'));
        if (!is_array($lists)) {
            $lists = (array)$lists;
        }

        $this->currentClass->unsubscribe($userId, $lists);

        $this->edit();
    }

    public function unsubscribeUserFromAll()
    {
        $userId = acym_getVar('int', 'id');

        if (empty($userId)) {
            $this->listing();

            return;
        }

        $lists = [];
        $subscriptions = $this->currentClass->getSubscriptionStatus($userId);
        foreach ($subscriptions as $i => $oneList) {
            if ($oneList->status == 1) {
                $lists[] = $oneList->list_id;
            }
        }

        $this->currentClass->unsubscribe($userId, $lists);

        $this->edit();
    }

    public function resubscribeUserToAll()
    {
        $userId = acym_getVar('int', 'id');

        if (empty($userId)) {
            $this->listing();

            return;
        }

        $lists = [];
        $subscriptions = $this->currentClass->getSubscriptionStatus($userId);
        foreach ($subscriptions as $i => $oneList) {
            if ($oneList->status == 0) {
                $lists[] = $oneList->list_id;
            }
        }

        $this->currentClass->subscribe($userId, $lists);

        $this->edit();
    }

    public function subscribeUser($returnOnEdit = true)
    {
        $userId = acym_getVar('int', 'id');
        $lists = json_decode(acym_getVar('string', 'acym__entity_select__selected', '{}'));

        if (empty($userId)) {
            $this->listing();

            return;
        }

        if (!is_array($lists)) {
            $lists = (array)$lists;
        }

        $this->currentClass->subscribe($userId, $lists);

        if ($returnOnEdit) $this->edit();
    }

    public function save()
    {
        $this->apply(true);
    }

    public function apply($listing = false)
    {
        $userInformation = acym_getVar('array', 'user');
        $userId = acym_getVar('int', 'id');
        $listsToAdd = json_decode(acym_getVar('string', 'acym__entity_select__selected', '{}'));
        $listsToUnsub = json_decode(acym_getVar('string', 'acym__entity_select__unselected', '{}'));

        $user = new \stdClass();
        $user->name = $userInformation['name'];
        $user->email = $userInformation['email'];
        if (!empty($userInformation['language'])) $user->language = $userInformation['language'];
        $user->active = $userInformation['active'];
        $user->confirmed = $userInformation['confirmed'];
        $user->tracking = $userInformation['tracking'];
        $customFields = acym_getVar('array', 'customField');

        preg_match('/'.acym_getEmailRegex().'/i', $user->email, $matches);

        if (empty($matches)) {
            $this->edit();
            acym_enqueueMessage(acym_translationSprintf('ACYM_VALID_EMAIL', $user->email), 'error');

            return;
        }

        $existingUser = $this->currentClass->getOneByEmail($user->email);
        if (empty($userId)) {
            if (!empty($existingUser) && acym_isAdmin()) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_X_ALREADY_EXIST', $user->email), 'error');

                $this->edit();

                return;
            } elseif (!empty($existingUser)) {
                $userId = $existingUser->id;
            } else {
                $user->creation_date = acym_date('now', 'Y-m-d H:i:s', false);
                $userId = $this->currentClass->save($user, $customFields);
            }
            acym_setVar('id', $userId);
        } else {
            if (!empty($existingUser) && $existingUser->id != $userId) {
                acym_enqueueMessage(acym_translationSprintf('ACYM_X_ALREADY_EXIST', $user->email), 'error');
                $this->edit();

                return;
            }
            $user->id = $userId;
            $this->currentClass->save($user, $customFields);
        }

        if (!empty($listsToAdd)) {
            $this->subscribeUser(false);
        }
        if (!empty($listsToUnsub)) {
            $this->currentClass->unsubscribeOnSubscriptions($userId, $listsToUnsub);
        }

        if ($listing) {
            $this->listing();
        } else {
            $this->edit();
        }
    }

    public function getColumnsFromTable()
    {
        $tableName = acym_secureDBColumn(acym_getVar('string', 'tablename', ''));
        if (empty($tableName)) {
            exit;
        }
        $columns = acym_getColumns($tableName, false, false);
        $allColumnsSelect = '<option value=""></option>';
        foreach ($columns as $oneColumn) {
            $allColumnsSelect .= '<option value="'.acym_escape($oneColumn).'">'.$oneColumn.'</option>';
        }

        echo $allColumnsSelect;
        exit;
    }

    public function addToList()
    {
        $listsSelected = json_decode(acym_getVar('string', 'acym__entity_select__selected', '{}'));
        $userSelected = acym_getVar('array', 'elements_checked');
        foreach ($userSelected as $user) {
            $this->currentClass->subscribe($user, $listsSelected);
        }
        $this->listing();
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

    public function getUserInfo()
    {
        $id = acym_getVar('int', 'id', 0);

        if (empty($id)) acym_sendAjaxResponse(acym_translation('ACYM_USER_NOT_FOUND'), [], false);

        $userClass = new UserClass();
        $user = $userClass->getCustomFieldValueById($id);

        if (empty($user)) acym_sendAjaxResponse(acym_translation('ACYM_SUBSCRIBER_NOT_CUSTOM_FIELD'), [], false);

        acym_sendAjaxResponse('', $user, true);
    }
}
