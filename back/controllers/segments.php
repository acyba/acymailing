<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\SegmentClass;
use AcyMailing\Helpers\AutomationHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Libraries\acymController;

class SegmentsController extends acymController
{

    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_SEGMENTS')] = acym_completeLink('segments');
    }

    public function listing()
    {
        if (!acym_level(2)) {
            acym_redirect(acym_completeLink('dashboard&task=upgrade&version=enterprise', false, true));
        }

    }

    private function prepareToolbar(&$data)
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'segments_search', 'ACYM_SEARCH');
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'edit'], 'add', true);

        $data['toolbar'] = $toolbarHelper;
    }

    public function edit()
    {
        if (!acym_level(2)) {
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
            acym_setVar('id', $segmentId);
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
        $id = acym_getVar('int', 'id');
        $filters = acym_getVar('array', 'acym_action');
        $segmentRequest = acym_getVar('array', 'segment');

        $segmentClass = new SegmentClass();

        if (empty($id)) {
            $segment = new \stdClass();
            $segment->creation_date = acym_date('now', 'Y-m-d H:i:s');
        } else {
            $segment = $segmentClass->getOneById($id);
            if (empty($segment)) return false;
        }

        $segment->name = $segmentRequest['name'];
        $segment->active = $segmentRequest['active'];
        $segment->filters = json_encode($filters['filters'][0]);

        return $segmentClass->save($segment);
    }

    public function countResultsTotal()
    {
        $stepAutomation = acym_getVar('array', 'acym_action');

        //if we are in the campaign edition
        $listsIds = acym_getVar('string', 'list_selected', '');

        $automationHelper = new AutomationHelper();

        if (!empty($listsIds)) {
            $listsIds = json_decode($listsIds);
            acym_arrayToInteger($listsIds);
            $join = $this->config->get('require_confirmation', 1) == 1 ? ' AND user.confirmed = 1' : '';
            $automationHelper->join['user_list'] = ' #__acym_user_has_list AS user_list ON user_list.user_id = user.id AND user_list.list_id IN ('.implode(',', $listsIds).') and user_list.status = 1 '.$join;
        }

        if (!empty($stepAutomation) && !empty($stepAutomation['filters'][0])) {
            foreach ($stepAutomation['filters'][0] as $and => $andValues) {
                $and = intval($and);
                foreach ($andValues as $filterName => $options) {
                    acym_trigger('onAcymProcessFilter_'.$filterName, [&$automationHelper, &$options, &$and]);
                }
            }
        }

        echo json_encode(['count' => $automationHelper->count()]);
        exit;
    }

    public function countResults()
    {
        $and = acym_getVar('int', 'and');
        $stepAutomation = acym_getVar('array', 'acym_action');

        //if we are in the campaign edition
        $listsIds = acym_getVar('string', 'list_selected', '');

        if (empty($stepAutomation['filters'][0][$and])) die(acym_translation('ACYM_AUTOMATION_NOT_FOUND'));

        $automationHelper = new AutomationHelper();
        $messages = '';

        if (!empty($listsIds)) {
            $listsIds = json_decode($listsIds);
            acym_arrayToInteger($listsIds);
            $join = $this->config->get('require_confirmation', 1) == 1 ? ' AND user.confirmed = 1' : '';
            $automationHelper->join['user_list'] = ' #__acym_user_has_list AS user_list ON user_list.user_id = user.id AND user_list.list_id IN ('.implode(',', $listsIds).') and user_list.status = 1 '.$join;
        }

        foreach ($stepAutomation['filters'][0][$and] as $filterName => $options) {
            $messages = acym_trigger('onAcymProcessFilterCount_'.$filterName, [&$automationHelper, &$options, &$and]);
            break;
        }

        $messages = $messages[0];

        if (!empty($listsIds)) $messages .= acym_info('ACYM_CONDITION_WITH_LISTS_COUNT', '', '', 'wysid_tooltip');

        echo json_encode(['message' => $messages]);
        exit;
    }

    public function countGlobalBySegmentId()
    {
        $id = acym_getVar('int', 'id');
        $listsIds = json_decode(acym_getVar('string', 'lists', '[]'));
        acym_arrayToInteger($listsIds);
        $automationHelper = new AutomationHelper();

        if (empty($listsIds)) {
            echo json_encode(['count' => 0]);
            exit;
        }

        $join = $this->config->get('require_confirmation', 1) == 1 ? ' AND user.confirmed = 1' : '';
        $automationHelper->join['user_list'] = ' #__acym_user_has_list AS user_list ON user_list.user_id = user.id AND user_list.list_id IN ('.implode(',', $listsIds).') and user_list.status = 1 '.$join;

        if (!empty($id)) {
            $segmentClass = new SegmentClass();
            $segment = $segmentClass->getOneById($id);
            if (empty($segment)) {
                echo json_encode(['error' => acym_translation('ACYM_COULD_NOT_COUNT_USER')]);
                exit;
            }
            foreach ($segment->filters as $and => $andValues) {
                $and = intval($and);
                foreach ($andValues as $filterName => $options) {
                    acym_trigger('onAcymProcessFilter_'.$filterName, [&$automationHelper, &$options, &$and]);
                }
            }
        }

        echo json_encode(['count' => $automationHelper->count()]);
        exit;
    }

    public function saveFromCampaign()
    {
        $name = acym_getVar('string', 'segment_name', '');
        $filters = acym_getVar('array', 'acym_action', []);

        $segmentClass = new SegmentClass();

        $segment = new \stdClass();

        $segment->name = $name;
        $segment->creation_date = acym_date('now', 'Y-m-d H:i:s');
        $segment->active = 1;
        $segment->filters = json_encode($filters['filters'][0]);

        $return = [];
        $segmentId = $segmentClass->save($segment);
        if ($segmentId) {
            $return['message'] = acym_translation('ACYM_SEGMENT_WELL_SAVE');
            $return['segment_id'] = $segmentId;
        } else {
            $return['error'] = acym_translation('ACYM_COULD_NOT_SAVE_SEGMENT');
        }

        echo json_encode($return);
        exit;
    }
}
