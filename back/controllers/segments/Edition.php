<?php

namespace AcyMailing\Controllers\Segments;

use AcyMailing\Classes\SegmentClass;
use AcyMailing\Controllers\AutomationController;
use AcyMailing\Helpers\AutomationHelper;

trait Edition
{
    public function edit()
    {
        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }

    }

    public function apply()
    {
        $segmentId = $this->store();
        if (!$segmentId) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_SAVE_SEGMENT'), 'error');
            $this->listing();
        } else {
            acym_enqueueMessage(acym_translation('ACYM_SEGMENT_WELL_SAVE'), 'success');
            acym_setVar('segmentId', $segmentId);
            $this->edit();
        }
    }

    public function save()
    {
        $segmentId = $this->store();
        acym_enqueueMessage(acym_translation($segmentId ? 'ACYM_SEGMENT_WELL_SAVE' : 'ACYM_COULD_NOT_SAVE_SEGMENT'), $segmentId ? 'success' : 'error');
        $this->listing();
    }

    private function store()
    {
        $segmentId = acym_getVar('int', 'segmentId');
        $filters = acym_getVar('array', 'acym_action');
        $segmentRequest = acym_getVar('array', 'segment');

        $segmentClass = new SegmentClass();

        if (empty($segmentId)) {
            $segment = new \stdClass();
            $segment->creation_date = acym_date('now', 'Y-m-d H:i:s');
        } else {
            $segment = $segmentClass->getOneById($segmentId);
            if (empty($segment)) return false;
        }

        $segment->name = $segmentRequest['name'];
        $segment->active = $segmentRequest['active'];
        $segment->filters = json_encode($filters['filters'], JSON_FORCE_OBJECT);

        return $segmentClass->save($segment);
    }

    public function countResultsTotal()
    {
        $segmentSelected = acym_getVar('int', 'segment_selected');
        if (empty($segmentSelected)) {
            $stepAutomation = acym_getVar('array', 'acym_action');
        } else {
            $segmentClass = new SegmentClass();
            $segment = $segmentClass->getOneById($segmentSelected);
            if (empty($segment)) {
                $stepAutomation = [];
            } else {
                $stepAutomation = ['filters' => $segment->filters];
            }
        }

        //if we are in the campaign edition
        $listsIds = acym_getVar('string', 'list_selected', '');
        if (!empty($listsIds)) {
            $listsIds = json_decode($listsIds);
        }

        $isExclude = acym_getVar('boolean', 'exclude', false);
        acym_sendAjaxResponse($this->countSegmentByParams($stepAutomation, $listsIds, $isExclude));
    }

    public function countSegmentByParams($segment, $listsIds, $exclude = false)
    {
        $automationHelpers = [];

        if (!empty($segment) && !empty($segment['filters'])) {
            foreach ($segment['filters'] as $or => $orValues) {
                if (empty($orValues)) continue;
                $automationHelpers[$or] = new AutomationHelper();
                foreach ($orValues as $and => $andValues) {
                    $and = intval($and);
                    foreach ($andValues as $filterName => $options) {
                        acym_trigger('onAcymProcessFilter_'.$filterName, [&$automationHelpers[$or], &$options, $and.'_'.$or]);
                    }
                }

                $automationHelpers[$or]->invert = $exclude;
                $automationHelpers[$or]->addFlag(self::FLAG_COUNT);
            }
        }

        $join = '';

        if (!empty($listsIds)) {
            acym_arrayToInteger($listsIds);
            $join = $this->config->get('require_confirmation', 1) == 1 ? ' AND user.confirmed = 1' : '';
        }

        if (!empty($segment['filters'])) {
            if ($exclude) {
                foreach ($automationHelpers as $or => $orValues) {
                    $orValues->where = ['user.automation NOT LIKE "%a'.self::FLAG_COUNT.'a%"'];
                }
            }
        }

        $userIds = [];

        if (empty($automationHelpers)) {
            $automationHelperBase = new AutomationHelper();
            if (!empty($listsIds)) {
                $automationHelperBase->join['user_list'] = ' #__acym_user_has_list AS user_list ON user_list.user_id = user.id AND user_list.list_id IN ('.implode(
                        ',',
                        $listsIds
                    ).') and user_list.status = 1 '.$join;
                $automationHelperBase->where[] = 'user.active = 1';
            }
            $userIds = acym_loadResultArray($automationHelperBase->getQuery(['user.id']));
        } else {
            foreach ($automationHelpers as $automationHelper) {
                if (!empty($listsIds)) {
                    $automationHelper->join['user_list'] = ' #__acym_user_has_list AS user_list ON user_list.user_id = user.id AND user_list.list_id IN ('.implode(
                            ',',
                            $listsIds
                        ).') and user_list.status = 1 '.$join;
                    $automationHelper->where[] = 'user.active = 1';
                }
                $userIds = array_merge($userIds, acym_loadResultArray($automationHelper->getQuery(['user.id'])));
            }
            $automationHelper->removeFlag(self::FLAG_COUNT);
        }

        return count(array_unique($userIds));
    }

    public function countResults()
    {
        $and = acym_getVar('int', 'and');
        $or = acym_getVar('int', 'or');
        $stepAutomation = acym_getVar('array', 'acym_action');

        //if we are in the campaign edition
        $listsIds = acym_getVar('string', 'list_selected', '');

        if (empty($stepAutomation['filters'][$or][$and])) {
            acym_sendAjaxResponse(acym_translation('ACYM_AUTOMATION_NOT_FOUND'), [], false);
        }

        $automationHelper = new AutomationHelper();

        if (!empty($listsIds)) {
            $listsIds = json_decode($listsIds);
            acym_arrayToInteger($listsIds);
            $join = $this->config->get('require_confirmation', 1) == 1 ? ' AND user.confirmed = 1' : '';
            $automationHelper->join['user_list'] = ' #__acym_user_has_list AS user_list ON user_list.user_id = user.id AND user_list.list_id IN ('.implode(
                    ',',
                    $listsIds
                ).') and user_list.status = 1 '.$join;
        }

        $filterName = key($stepAutomation['filters'][$or][$and]);
        $options = current($stepAutomation['filters'][$or][$and]);
        $messages = acym_trigger('onAcymProcessFilterCount_'.$filterName, [&$automationHelper, &$options, &$and]);
        $messages = $messages[0];

        if (!empty($listsIds)) $messages .= acym_info('ACYM_CONDITION_WITH_LISTS_COUNT', '', '', 'wysid_tooltip');

        acym_sendAjaxResponse($messages);
    }

    public function usersSummary()
    {
        $offset = acym_getVar('int', 'offset', 0);
        $limit = acym_getVar('int', 'limit', 50);
        $search = acym_getVar('string', 'modal_search', '');

        $and = acym_getVar('int', 'and');
        $or = acym_getVar('int', 'or');
        $stepAutomation = acym_getVar('array', 'acym_action');

        if (empty($stepAutomation['filters'][$or][$and])) acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_RETRIEVE_DATA'), [], false);

        $automationHelper = new AutomationHelper();

        //if we are in the campaign edition
        $listsIds = acym_getVar('string', 'list_selected', '');

        if (!empty($listsIds)) {
            $listsIds = json_decode($listsIds);
            acym_arrayToInteger($listsIds);
            $join = $this->config->get('require_confirmation', 1) == 1 ? ' AND user.confirmed = 1' : '';
            $automationHelper->join['user_list'] = ' #__acym_user_has_list AS user_list ON user_list.user_id = user.id AND user_list.list_id IN ('.implode(
                    ',',
                    $listsIds
                ).') and user_list.status = 1 '.$join;
        }

        $filterName = key($stepAutomation['filters'][$or][$and]);
        $options = current($stepAutomation['filters'][$or][$and]);
        acym_trigger('onAcymProcessFilter_'.$filterName, [&$automationHelper, &$options, &$and]);

        if (!empty($search)) {
            $search = acym_escapeDB('%'.$search.'%');
            $automationHelper->where[] = ' (user.email LIKE '.$search.' OR user.name LIKE '.$search.' OR user.id LIKE '.$search.') ';
        }

        $automationHelper->limit = intval($offset).', '.intval($limit);

        $users = acym_loadObjectList($automationHelper->getQuery(['user.email', 'user.name', 'user.id']), 'id');

        acym_sendAjaxResponse('', ['users' => $users]);
    }
}
