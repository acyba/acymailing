<?php

namespace AcyMailing\Controllers\Segments;

use AcyMailing\Classes\SegmentClass;
use AcyMailing\Helpers\AutomationHelper;

trait Campaign
{
    public function countSegmentById($id, $listsIds, $ajax = true, $exclude = false)
    {
        acym_arrayToInteger($listsIds);

        if (empty($listsIds)) {
            return 0;
        }

        $automationHelpers = [];

        if (!empty($id)) {
            $segmentClass = new SegmentClass();
            $segment = $segmentClass->getOneById($id);
            if (empty($segment)) {
                if ($ajax) {
                    acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_COUNT_USER'), [], false);
                } else {
                    return 0;
                }
            }
            foreach ($segment->filters as $or => $orValues) {
                if (empty($orValues)) continue;
                $automationHelpers[$or] = new AutomationHelper();
                foreach ($orValues as $and => $andValues) {
                    $and = intval($and);
                    foreach ($andValues as $filterName => $options) {
                        acym_trigger('onAcymProcessFilter_'.$filterName, [&$automationHelpers[$or], &$options, $and.'_'.$or]);
                    }
                }

                $automationHelpers [$or]->invert = $exclude;
                $automationHelpers[$or]->addFlag(self::FLAG_COUNT);
            }
        }

        $automationHelperBase = new AutomationHelper();
        $join = $this->config->get('require_confirmation', 1) == 1 ? ' AND user.confirmed = 1' : '';

        if (!empty($segment->filters)) {
            if ($exclude) {
                foreach ($automationHelpers as $or => $orValues) {
                    $orValues->where = ['user.automation NOT LIKE "%a'.self::FLAG_COUNT.'a%"'];
                }
            }
        }

        $userIds = [];
        if (empty($automationHelpers)) {
            $automationHelperBase->join['user_list'] = ' #__acym_user_has_list AS user_list ON user_list.user_id = user.id AND user_list.list_id IN ('.implode(
                    ',',
                    $listsIds
                ).') and user_list.status = 1 '.$join;
            $userIds = acym_loadResultArray($automationHelperBase->getQuery(['user.id']));
        } else {
            foreach ($automationHelpers as $key => $automationHelper) {
                $automationHelper->join['user_list'] = ' #__acym_user_has_list AS user_list ON user_list.user_id = user.id AND user_list.list_id IN ('.implode(
                        ',',
                        $listsIds
                    ).') and user_list.status = 1 '.$join;
                $userIds = array_merge($userIds, acym_loadResultArray($automationHelper->getQuery(['user.id'])));
                $automationHelper->removeFlag(self::FLAG_COUNT);
            }
            $userIds = array_unique($userIds);
        }

        return count($userIds);
    }

    public function countGlobalBySegmentId()
    {
        $id = acym_getVar('int', 'id');
        $isExclude = acym_getVar('boolean', 'exclude', false);
        $listsIds = json_decode(acym_getVar('string', 'lists', '[]'));

        acym_sendAjaxResponse('', ['count' => $this->countSegmentById($id, $listsIds, true, $isExclude)]);
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
