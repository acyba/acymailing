<?php

namespace AcyMailing\Controllers;

use AcyMailing\Libraries\acymController;
use AcyMailing\Classes\ZoneClass;

class ZonesController extends acymController
{
    const ZONE_IMAGE_FOLDER = ACYM_UPLOAD_FOLDER.'zones'.DS;

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

        if (!empty($existingZone)) {
            acym_sendAjaxResponse(acym_translation('ACYM_FAILED_CUSTOM_ZONE_SAVE_EXISTING'), [], false);
        }

        $zone->id = $zoneClass->save($zone);

        $dataResponse = ['id' => $zone->id];

        if (!empty($_FILES['image']['name'])) {
            $extension = pathinfo($_FILES['image']['name']);
            $newPath = self::ZONE_IMAGE_FOLDER.$zone->id.'.'.$extension['extension'];

            if (in_array($extension['extension'], acym_getImageFileExtensions()) && acym_uploadFile($_FILES['image']['tmp_name'], ACYM_ROOT.$newPath)) {
                $zone->image = $newPath;
                if ($zoneClass->save($zone)) {
                    $dataResponse['image'] = acym_rootURI().$newPath;
                }
            }
        }

        acym_sendAjaxResponse('', $dataResponse);
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
