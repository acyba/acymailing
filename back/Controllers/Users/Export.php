<?php

namespace AcyMailing\Controllers\Users;

use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\SegmentClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Controllers\SegmentsController;
use AcyMailing\Helpers\AutomationHelper;
use AcyMailing\Helpers\EncodingHelper;
use AcyMailing\Helpers\EntitySelectHelper;
use AcyMailing\Helpers\ExportHelper;

trait Export
{
    /**
     * Export page where the user selects the export option
     */
    public function export(): void
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

        $filtersSegment = $this->getVarFiltersListing('int', 'segment', 0);
        $segmentClass = new SegmentClass();
        $availableSegments = $segmentClass->getAllForSelect();

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
            'coreFields' => [1, 2, $fieldClass->getLanguageFieldId()],
            'isPreselectedList' => $preselectList,
            'entitySelect' => $entitySelect,
            'exportListStatus' => $filtersListing['list_status'],
            'encodingHelper' => $encodingHelper,
            'userClass' => $userClass,
            'segments' => $availableSegments,
            'preselectedSegment' => $filtersSegment,
        ];

        parent::display($data);
    }

    /**
     * This method downloads the exported file directly
     */
    public function doexport(): void
    {
        acym_checkToken();
        acym_increasePerf();

        // Get passed data and check if we have everything we need
        $usersToExport = acym_getVar('string', 'export_users-to-export', 'all');
        $selectedLists = acym_getVar('string', 'acym__entity_select__selected', '[]');
        $listsToExport = json_decode(empty($selectedLists) ? '[]' : $selectedLists, true);
        if ($usersToExport === 'list' && empty($listsToExport)) {
            acym_enqueueMessage(acym_translation('ACYM_EXPORT_SELECT_LIST'), 'error');

            $this->exportError(acym_translation('ACYM_EXPORT_SELECT_LIST'));
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
            acym_setVar('elements_checked', empty($selectedUsersArray) ? [] : $selectedUsersArray);

            $this->exportError(acym_translation('ACYM_EXPORT_SELECT_FIELD'));
        }

        $tableFields = acym_getColumns('user');
        $fieldClass = new FieldClass();
        $customFields = $fieldClass->getAll();

        $customFieldsToExport = [];
        $specialFieldsToExport = [];
        foreach ($fieldsToExport as $i => $oneField) {
            if (in_array($oneField, ['subscribe_date', 'unsubscribe_date'])) {
                $specialFieldsToExport[] = $oneField;
                unset($fieldsToExport[$i]);
            } elseif (!empty($customFields[$oneField])) {
                $customFieldsToExport[$oneField] = $customFields[$oneField]->namekey;
                unset($fieldsToExport[$i]);
            }
        }

        $notAllowedFields = array_diff($fieldsToExport, $tableFields);
        if (in_array('id', $fieldsToExport)) {
            $notAllowedFields[] = 'id';
        }
        if (!empty($notAllowedFields)) {
            $this->exportError(acym_translationSprintf('ACYM_NOT_ALLOWED_FIELDS', implode(', ', $notAllowedFields), implode(', ', $tableFields)));
        }

        $charset = acym_getVar('string', 'export_charset', 'UTF-8');
        $excelSecurity = acym_getVar('string', 'export_excelsecurity', 0);
        $separator = acym_getVar('string', 'export_separator', 'comma');
        $realSeparators = ['comma' => ',', 'semicol' => ';'];
        if (!in_array($separator, array_keys($realSeparators))) {
            $separator = 'comma';
        }

        // Save the selected options for the next time
        $newConfig = [
            'export_separator' => $separator,
            'export_charset' => $charset,
            'export_excelsecurity' => $excelSecurity,
            'export_fields' => implode(',', array_merge($fieldsToExport, array_keys($customFieldsToExport))),
        ];
        if (empty($selectedUsers)) {
            $newConfig['export_lists'] = implode(',', $listsToExport);
        }
        $this->config->saveConfig($newConfig);

        // Prepare the export query
        foreach ($fieldsToExport as $oneField) {
            acym_secureDBColumn($oneField);
        }
        $query = 'SELECT DISTINCT user.`id`, user.`'.implode('`, user.`', $fieldsToExport).'` FROM #__acym_user AS user';

        $where = [];
        $flagSegment = 0;

        if (!empty($selectedUsersArray)) {
            acym_arrayToInteger($selectedUsersArray);
            $where[] = 'user.id IN ('.implode(',', $selectedUsersArray).')';
        } else {
            if ($usersToExport == 'list' && !empty($listsToExport)) {
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

            $segmentChosen = acym_getVar('string', 'export_segment', '');
            if ('' !== $segmentChosen) {
                $segmentClass = new SegmentClass();
                $segment = $segmentClass->getOneById($segmentChosen);
                if (!empty($segment)) {
                    $automationHelpers = [];
                    foreach ($segment->filters as $or => $orValues) {
                        if (empty($orValues)) continue;
                        $automationHelpers[$or] = new AutomationHelper();
                        foreach ($orValues as $and => $andValues) {
                            $and = intval($and);
                            foreach ($andValues as $filterName => $options) {
                                acym_trigger('onAcymProcessFilter_'.$filterName, [&$automationHelpers[$or], &$options, $and.'_'.$or]);
                            }
                        }
                    }
                    $flagSegment = SegmentsController::FLAG_EXPORT_USERS;
                    foreach ($automationHelpers as $automationHelper) {
                        $automationHelper->addFlag($flagSegment);
                    }

                    $where[] = 'user.automation LIKE "%a'.intval($flagSegment).'a%"';
                }
            }
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

        if (!empty($where)) {
            $query .= ' WHERE ('.implode(') AND (', $where).')';
        }

        // We have all we need for the export, prepare the headers for the download
        $exportHelper = new ExportHelper();
        $exportHelper->exportCSV($query, $fieldsToExport, $customFieldsToExport, $specialFieldsToExport, $realSeparators[$separator], $charset, '', $flagSegment);

        exit;
    }

    /**
     * Aborts export and displays an error message
     */
    private function exportError(string $message): void
    {
        acym_enqueueMessage($message, 'error', 0);
        acym_setNoTemplate(false);

        acym_redirect(acym_completeLink('users&task=export', false, true));
    }
}
