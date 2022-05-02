<?php

namespace AcyMailing\Controllers;

use AcyMailing\Libraries\acymController;
use AcyMailing\Classes\ZoneClass;

class ZonesController extends acymController
{
    public function save()
    {
        acym_checkToken();

        $zone = new \stdClass();
        $zone->name = acym_getVar('string', 'name');
        $zone->content = acym_getVar('string', 'content', '', 'default', ACYM_ALLOWRAW);

        if (empty($zone->name) || empty($zone->content)) {
            acym_sendAjaxResponse(acym_translation('ACYM_FAILED_CUSTOM_ZONE_SAVE'), [], false);
        }

        $zoneClass = new ZoneClass();

        $existingZone = $zoneClass->getOneByName($zone->name);
        if(!empty($existingZone)){
            acym_sendAjaxResponse(acym_translation('ACYM_FAILED_CUSTOM_ZONE_SAVE_EXISTING'), [], false);
        }

        $id = $zoneClass->save($zone);

        acym_sendAjaxResponse('', ['id' => $id]);
    }

    private function getZoneData()
    {
        acym_checkToken();
        $zoneId = acym_getVar('int', 'zoneId', 0);
        if (empty($zoneId)) {
            acym_sendAjaxResponse(acym_translation('ACYM_FAILED_CUSTOM_ZONE_FIND'), [], false);
        }

        $zoneClass = new ZoneClass();
        $zone = $zoneClass->getOneById($zoneId);
        if (empty($zone)) {
            acym_sendAjaxResponse(acym_translation('ACYM_FAILED_CUSTOM_ZONE_FIND'), [], false);
        }

        return [
            'zoneClass' => $zoneClass,
            'zoneId' => $zoneId,
            'zone' => $zone,
        ];
    }

    public function getForInsertion()
    {
        $zoneData = $this->getZoneData();

        acym_sendAjaxResponse('', ['content' => $zoneData['zone']->content]);
    }

    public function delete()
    {
        $zoneData = $this->getZoneData();
        $zoneData['zoneClass']->delete($zoneData['zoneId']);

        acym_sendAjaxResponse();
    }
}
