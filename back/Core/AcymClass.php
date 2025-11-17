<?php

namespace AcyMailing\Core;

abstract class AcymClass extends AcymObject
{
    // Handle errors
    public array $errors = [];
    // Information messages, mainly for the cron report
    public array $messages = [];

    protected string $table;
    protected string $pkey = '';
    protected bool $forceInsert = false;
    protected array $intColumns = [];
    protected array $jsonColumns = [];

    /**
     * Returns an array containing "elements[]" which are all the requested elements and "total" which is the number of elements.
     * Can also contain "status[]", containing the status of elements. Optional.
     */
    public function getMatchingElements(array $settings = []): array
    {
        if (!empty($this->table) && !empty($this->pkey)) {
            $query = 'SELECT * FROM #__acym_'.acym_secureDBColumn($this->table);
            $queryCount = 'SELECT COUNT(*) AS total FROM #__acym_'.acym_secureDBColumn($this->table);

            if (empty($settings['ordering'])) $settings['ordering'] = $this->pkey;
            $query .= ' ORDER BY `'.acym_secureDBColumn($settings['ordering']).'`';
            if (!empty($settings['ordering_sort_order'])) $query .= ' '.acym_secureDBColumn(strtoupper($settings['ordering_sort_order']));

            $elements = acym_loadObjectList($query);
            $total = acym_loadObject($queryCount);
        } else {
            $elements = [];
            $total = new \stdClass();
            $total->total = 0;
        }

        array_map([$this, 'fixTypes'], $elements);

        return [
            'elements' => $elements,
            'total' => $total,
        ];
    }

    public function getOneById(int $id): ?object
    {
        $element = acym_loadObject('SELECT * FROM #__acym_'.acym_secureDBColumn($this->table).' WHERE `'.acym_secureDBColumn($this->pkey).'` = '.intval($id));

        if (empty($element)) {
            return null;
        }

        $this->fixTypes($element);

        return $element;
    }

    public function getByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        acym_arrayToInteger($ids);

        $elements = acym_loadObjectList('SELECT * FROM #__acym_'.acym_secureDBColumn($this->table).' WHERE `'.acym_secureDBColumn($this->pkey).'` IN ("'.implode('","', $ids).'")');
        array_map([$this, 'fixTypes'], $elements);

        return $elements;
    }

    public function getAll(?string $key = null): array
    {
        if (empty($key)) {
            $key = $this->pkey;
        }

        $elements = acym_loadObjectList('SELECT * FROM #__acym_'.acym_secureDBColumn($this->table), $key);
        array_map([$this, 'fixTypes'], $elements);

        return $elements;
    }

    public function save(object $element): ?int
    {
        $tableColumns = acym_getColumns($this->table);
        // We clone the element because we don't want to modify it for later in the code
        $cloneElement = clone $element;
        foreach ($cloneElement as $column => $value) {
            if (!in_array($column, $tableColumns)) {
                // Unset variables that don't exist in the table
                unset($cloneElement->$column);
                continue;
            }
            acym_secureDBColumn($column);
        }

        $pkey = $this->pkey;

        try {
            if (empty($cloneElement->$pkey) || $this->forceInsert) {
                $status = acym_insertObject('#__acym_'.$this->table, $cloneElement);
            } else {
                $status = acym_updateObject('#__acym_'.$this->table, $cloneElement, [$pkey]);
            }
        } catch (\Exception $e) {
            $status = false;
        }

        if (empty($status)) {
            $dbError = strip_tags(isset($e) ? $e->getMessage() : acym_getDBError());
            if (!empty($dbError)) {
                if (strlen($dbError) > 203) $dbError = substr($dbError, 0, 200).'...';
                $this->errors[] = $dbError;
            }

            return false;
        }

        return (int)(empty($cloneElement->$pkey) ? $status : $cloneElement->$pkey);
    }

    public function delete(array $elements): int
    {
        if (empty($elements)) {
            return 0;
        }

        $escapedElements = array_map('acym_escapeDB', $elements);

        if (empty($this->pkey) || empty($this->table) || empty($escapedElements)) {
            return 0;
        }

        acym_trigger('onAcymBefore'.ucfirst($this->table).'Delete', [&$elements]);

        $query = 'DELETE FROM #__acym_'.acym_secureDBColumn($this->table).' WHERE '.acym_secureDBColumn($this->pkey).' IN ('.implode(',', $escapedElements).')';
        $result = acym_query($query);

        if (!$result) {
            return 0;
        }

        acym_trigger('onAcymAfter'.ucfirst($this->table).'Delete', [&$elements]);

        return (int)$result;
    }

    public function setActive(array $elements): void
    {
        if (empty($elements)) {
            return;
        }

        acym_arrayToInteger($elements);
        acym_query('UPDATE '.acym_secureDBColumn('#__acym_'.$this->table).' SET active = 1 WHERE `'.acym_secureDBColumn($this->pkey).'` IN ('.implode(',', $elements).')');
    }

    public function setInactive(array $elements): void
    {
        if (empty($elements)) {
            return;
        }

        acym_arrayToInteger($elements);
        acym_query('UPDATE '.acym_secureDBColumn('#__acym_'.$this->table).' SET active = 0 WHERE `'.acym_secureDBColumn($this->pkey).'` IN ('.implode(',', $elements).')');
    }

    /**
     * Joomla/WordPress can return ints as strings or ints depending on their versions
     */
    protected function fixTypes(object $element): void
    {
        foreach ($this->intColumns as $intColumn) {
            if (isset($element->$intColumn)) {
                $element->$intColumn = (int)$element->$intColumn;
            }
        }

        foreach ($this->jsonColumns as $oneColumn) {
            if (!isset($element->$oneColumn)) {
                continue;
            }

            if (empty($element->$oneColumn)) {
                $element->$oneColumn = [];
            } elseif (is_string($element->$oneColumn)) {
                $element->$oneColumn = [];
            }
        }
    }
}
