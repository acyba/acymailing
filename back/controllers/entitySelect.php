<?php

namespace AcyMailing\Controllers;

use AcyMailing\Helpers\EntitySelectHelper;
use AcyMailing\Libraries\acymController;

class EntitySelectController extends acymController
{
    var $entitySelectHelper;

    public function __construct()
    {
        parent::__construct();
        $this->entitySelectHelper = new EntitySelectHelper();
        $this->loadScripts = [
            'all' => ['vue-applications' => ['entity_select']],
        ];
    }

    public function loadEntityFront()
    {
        $entity = acym_getVar('string', 'entity');
        $offset = acym_getVar('int', 'offset');
        $perCalls = acym_getVar('int', 'perCalls');
        $join = acym_getVar('string', 'join');
        $joinColumnGet = acym_getVar('string', 'join_table', '');
        $columnsToDisplay = explode(',', acym_getVar('string', 'columns', ''));
        if (!empty($joinColumnGet)) $columnsToDisplay['join'] = $joinColumnGet;

        $entityBack = $this->loadEntityBack($entity, $offset, $perCalls, $join, $columnsToDisplay);
        if (!empty($entityBack['error'])) {
            acym_sendAjaxResponse($entityBack['error'], [], false);
        } else {
            acym_sendAjaxResponse('', ['results' => $entityBack]);
        }
    }

    public function loadEntityBack($entity, $offset, $perCalls, $join, $columnsToDisplay)
    {
        if (empty($entity) || (empty($offset) && 0 !== $offset) || empty($perCalls)) {
            return ['error' => acym_translation('ACYM_MISSING_PARAMETERS')];
        }

        $entityParams = [
            'offset' => $offset,
            'elementsPerPage' => $perCalls,
            'entitySelect' => true,
        ];
        if (!empty($join)) $entityParams['join'] = $join;
        if (!empty($columnsToDisplay)) $entityParams['columns'] = $columnsToDisplay;

        if ('list' == $entity) $entityParams['columns'][] = 'description';

        if (!acym_isAdmin()) $entityParams['creator_id'] = acym_currentUserId();

        $namespaceClass = 'AcyMailing\\Classes\\'.ucfirst($entity).'Class';
        $entityClass = new $namespaceClass;

        $availableEntity = $entityClass->getMatchingElements($entityParams);

        $this->formatEntites($availableEntity, $entity);

        return ['data' => empty($availableEntity) ? 'end' : $availableEntity];
    }

    private function formatEntites(&$availableEntity, $entity)
    {
        if ($entity == 'list') {
            foreach ($availableEntity['elements'] as $key => $element) {
                $availableEntity['elements'][$key]->color = '<i style="color: '.$element->color.'" class="acym_subscription acymicon-circle">';
                if (!empty($element->description)) $availableEntity['elements'][$key]->name = $element->name.acym_info($element->description);
            }
        } elseif ($entity == 'user') {
            foreach ($availableEntity['elements'] as $key => $element) {
                $availableEntity['elements'][$key]->email = $element->email.'<span class="acym__hover__user_info" data-id="'.$availableEntity['elements'][$key]->id.'">
                '.acym_info('<i class="acymicon-circle-o-notch acymicon-spin"></i>').'
                </span>';
            }
        }
    }
}
