<?php

namespace AcyMailing\Controllers\Segments;

use AcyMailing\Classes\SegmentClass;
use AcyMailing\Controllers\AutomationController;
use AcyMailing\Helpers\AutomationHelper;

trait Edition
{
    public function edit(): void
    {
        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }

        //__START__enterprise_
        if (acym_level(ACYM_ENTERPRISE)) {
            acym_setVar('layout', 'edit');
            $segmentId = acym_getVar('int', 'segmentId', 0);

            $segmentClass = new SegmentClass();

            if (empty($segmentId)) {
                $segment = new \stdClass();
                $segment->active = 1;
            } else {
                $segment = $segmentClass->getOneById($segmentId);

                if (empty($segment)) {
                    acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_FIND_SEGMENT'), 'error');
                    $this->listing();

                    return;
                }
            }

            $filters = [];
            acym_trigger('onAcymDeclareFilters', [&$filters]);

            uasort(
                $filters,
                function ($a, $b) {
                    return strcmp(strtolower($a->name), strtolower($b->name));
                }
            );

            $selectFilter = new \stdClass();
            $selectFilter->name = acym_translation('ACYM_SELECT_FILTER');
            $selectFilter->option = '';
            array_unshift($filters, $selectFilter);

            $filtersClassic = ['name' => [], 'option'];

            foreach ($filters as $key => $filter) {
                $filtersClassic['name'][$key] = $filter->name;
                $filtersClassic['option'][$key] = $filter->option;
            }


            $this->breadcrumb[empty($segment->id) ? acym_translation('ACYM_NEW_SEGMENT') : $segment->name] = acym_completeLink(
                'segments&task=edit'.(empty($segment->id) ? '' : '&segmentId='.$segment->id)
            );

            $data = [
                'segment' => $segment,
                'filter_name' => $filtersClassic['name'],
                'filter_option' => json_encode(preg_replace_callback(ACYM_REGEX_SWITCHES, [new AutomationController(), 'switches'], $filtersClassic['option'])),
            ];

            parent::display($data);
        }
        //__END__enterprise_
    }

    public function apply(): void
    {
        $segmentId = $this->store();
        if (empty($segmentId)) {
            acym_enqueueMessage(acym_translation('ACYM_COULD_NOT_SAVE_SEGMENT'), 'error');
            $this->listing();
        } else {
            acym_enqueueMessage(acym_translation('ACYM_SEGMENT_WELL_SAVE'));
            acym_setVar('segmentId', $segmentId);
            $this->edit();
        }
    }

    public function save(): void
    {
        $segmentId = $this->store();
        acym_enqueueMessage(acym_translation(empty($segmentId) ? 'ACYM_COULD_NOT_SAVE_SEGMENT' : 'ACYM_SEGMENT_WELL_SAVE'), empty($segmentId) ? 'error' : 'success');
        $this->listing();
    }

    private function store(): int
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
            if (empty($segment)) {
                return 0;
            }
        }

        $segment->name = $segmentRequest['name'];
        $segment->active = $segmentRequest['active'];
        $segment->filters = json_encode($filters['filters'], JSON_FORCE_OBJECT);

        $segmentId = $segmentClass->save($segment);

        return empty($segmentId) ? 0 : $segmentId;
    }

    public function countResultsTotal(): void
    {
        $segmentSelected = acym_getVar('int', 'segment_selected');
        if (empty($segmentSelected)) {
            $stepAutomation = acym_getVar('array', 'acym_action', []);
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
        $listsIds = acym_getVar('string', 'list_selected', '[]');
        $listsIds = empty($listsIds) ? [] : json_decode($listsIds, true);

        $isExclude = acym_getVar('boolean', 'exclude', false);
        acym_sendAjaxResponse($this->countSegmentByParams($stepAutomation, $listsIds, $isExclude));
    }

    public function countSegmentByParams(array $segment, array $listsIds, bool $exclude = false): int
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

                $automationHelpers[$or]->excludeSelected = $exclude;
                $automationHelpers[$or]->addFlag(self::FLAG_COUNT);
            }
        }

        if (!empty($listsIds)) {
            acym_arrayToInteger($listsIds);

            $join = ' #__acym_user_has_list AS user_list ON user_list.user_id = user.id ';
            $join .= 'AND user_list.list_id IN ('.implode(',', $listsIds).') ';
            $join .= 'AND user_list.status = 1 ';
            $join .= 'AND user.active = 1 ';
            if ($this->config->get('require_confirmation', 1) == 1) {
                $join .= 'AND user.confirmed = 1 ';
            }
        }

        if (empty($automationHelpers)) {
            $automationHelperBase = new AutomationHelper();
            if (!empty($listsIds)) {
                $automationHelperBase->join['user_list'] = $join;
            }

            return acym_loadResult($automationHelperBase->getQuery(['COUNT(DISTINCT user.id)']));
        } else {
            $automationHelperBase = array_pop($automationHelpers);
            if (!empty($listsIds)) {
                $automationHelperBase->join['user_list'] = $join;
            }
            $numberOfRecipients = acym_loadResult($automationHelperBase->getQuery(['COUNT(DISTINCT user.id)']));
            $automationHelperBase->removeFlag(self::FLAG_COUNT);

            return $numberOfRecipients;
        }
    }

    public function countResults(): void
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

        if (!empty($listsIds)) $messages .= acym_info(['textShownInTooltip' => 'ACYM_CONDITION_WITH_LISTS_COUNT', 'classText' => 'wysid_tooltip']);

        acym_sendAjaxResponse($messages);
    }

    public function usersSummary(): void
    {
        $offset = acym_getVar('int', 'offset', 0);
        $limit = acym_getVar('int', 'limit', 50);
        $search = acym_getVar('string', 'modal_search', '');

        $and = acym_getVar('int', 'and');
        $or = acym_getVar('int', 'or');
        $stepAutomation = acym_getVar('array', 'acym_action');

        if (empty($stepAutomation['filters'][$or][$and])) {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_RETRIEVE_DATA'), [], false);
        }

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
