<?php

namespace AcyMailing\Controllers\Segments;

use AcyMailing\Classes\SegmentClass;
use AcyMailing\Helpers\AutomationHelper;

trait Campaign
{
    public function countSegmentById($segmentId, $listsIds, $ajax = true, $exclude = false): int
    {
        acym_arrayToInteger($listsIds);

        if (empty($listsIds)) {
            return 0;
        }

        if (empty($segmentId)) {
            if ($ajax) {
                acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_COUNT_USER'), [], false);
            } else {
                return 0;
            }
        }

        $segmentClass = new SegmentClass();
        $segment = $segmentClass->getOneById($segmentId);
        if (empty($segment)) {
            if ($ajax) {
                acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_COUNT_USER'), [], false);
            } else {
                return 0;
            }
        }

        $automationHelperBase = new AutomationHelper();
        $automationHelperBase->removeFlag(self::FLAG_COUNT);

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

            $automationHelpers[$or]->excludeSelected = $exclude;
            $automationHelpers[$or]->addFlag(self::FLAG_COUNT);
        }

        $join = ' #__acym_user_has_list AS user_list ON user_list.user_id = user.id ';
        $join .= 'AND user_list.list_id IN ('.implode(',', $listsIds).') ';
        $join .= 'AND user_list.status = 1 ';
        if ($this->config->get('require_confirmation', 1) == 1) {
            $join .= 'AND user.confirmed = 1 ';
        }

        if (empty($automationHelpers)) {
            $automationHelperBase->join['user_list'] = $join;

            return acym_loadResult($automationHelperBase->getQuery(['COUNT(DISTINCT user.id)']));
        } else {
            $automationHelperBase = array_pop($automationHelpers);
            $automationHelperBase->join['user_list'] = $join;
            $numberOfRecipients = acym_loadResult($automationHelperBase->getQuery(['COUNT(DISTINCT user.id)']));
            $automationHelperBase->removeFlag(self::FLAG_COUNT);

            return $numberOfRecipients;
        }
    }

    public function countGlobalBySegmentId()
    {
        $segmentId = acym_getVar('int', 'segmentId', 0);
        $isExclude = acym_getVar('boolean', 'exclude', false);
        $listsIds = json_decode(acym_getVar('string', 'lists', '[]'));

        if (empty($segmentId)) {
            acym_sendAjaxResponse('', ['count' => $this->countSegmentByParams([], $listsIds, $isExclude)]);
        }
        acym_sendAjaxResponse('', ['count' => $this->countSegmentById($segmentId, $listsIds, true, $isExclude)]);
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
        $segment->filters = json_encode($filters['filters']);

        $segmentId = $segmentClass->save($segment);
        if ($segmentId) {
            acym_sendAjaxResponse(acym_translation('ACYM_SEGMENT_WELL_SAVE'), ['segment_id' => $segmentId]);
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_SAVE_SEGMENT'), [], false);
        }
    }
}
