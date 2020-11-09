<?php

namespace AcyMailing\Classes;

use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Libraries\acymClass;

class SegmentClass extends acymClass
{
    var $table = 'segment';
    var $pkey = 'id';

    public function getMatchingElements($settings = [])
    {
        $query = 'SELECT segment.* FROM #__acym_segment AS segment';
        $queryCount = 'SELECT COUNT(segment.id) AS total, SUM(active) AS totalActive FROM #__acym_segment AS segment';

        if (!empty($settings['search'])) {
            $filters[] = 'name LIKE '.acym_escapeDB('%'.$settings['search'].'%');
        }

        if (!empty($filters)) {
            $query .= ' WHERE ('.implode(') AND (', $filters).')';
            $queryCount .= ' WHERE ('.implode(') AND (', $filters).')';
        }

        if (!empty($settings['status'])) {
            $query .= empty($filters) ? ' WHERE ' : ' AND ';
            $query .= 'active = '.($settings['status'] == 'active' ? '1' : '0');
        }

        if (!empty($settings['ordering']) && !empty($settings['ordering_sort_order'])) {
            $query .= ' ORDER BY '.acym_secureDBColumn($settings['ordering']).' '.acym_secureDBColumn(strtoupper($settings['ordering_sort_order']));
        } else {
            $query .= ' ORDER BY id asc';
        }

        if (empty($settings['offset']) || $settings['offset'] < 0) {
            $settings['offset'] = 0;
        }

        if (empty($settings['elementsPerPage']) || $settings['elementsPerPage'] < 1) {
            $pagination = new PaginationHelper();
            $settings['elementsPerPage'] = $pagination->getListLimit();
        }

        $results['elements'] = acym_loadObjectList($query, '', $settings['offset'], $settings['elementsPerPage']);
        $results['total'] = acym_loadObject($queryCount);

        return $results;
    }

    public function getOneById($id)
    {
        $segment = parent::getOneById($id);

        $segment->filters = empty($segment->filters) ? [] : json_decode($segment->filters, true);

        return $segment;
    }

    public function getByIds($ids)
    {
        $segments = parent::getByIds($ids);

        if (empty($segments)) return [];

        foreach ($segments as $key => $segment) {
            $segments[$key]->filters = empty($segment->filters) ? [] : json_decode($segment->filters, true);
        }

        return $segments;
    }

    public function getAllForSelect($firstEmpty = true)
    {
        $segments = acym_loadObjectList('SELECT * FROM #__acym_segment WHERE active = 1');

        if ($firstEmpty) {
            $return = [
                '' => acym_translation('ACYM_SELECT_SEGMENT'),
            ];
        } else {
            $return = [];
        }

        foreach ($segments as $segment) {
            $return[$segment->id] = $segment->name;
        }

        return $return;
    }
}
