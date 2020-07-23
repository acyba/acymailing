<?php

class acymClass extends acymObject
{
    var $table = '';

    // Name of the Primary Key
    var $pkey = '';

    // Name of the namekey field (for non numeric values)
    var $namekey = '';

    // Handle errors
    var $errors = [];

    // Information messages, mainly for the cron report
    var $messages = [];

    /**
     * @param array $settings
     *
     * @return array containing "elements[]" which are all the requested element, "total" which is the number of elements.
     * Can also contain "status[]", containing the status of elements. Optional.
     */
    public function getMatchingElements($settings = [])
    {
        if (!empty($this->table) && !empty($this->pkey)) {
            $query = 'SELECT * FROM #__acym_'.acym_secureDBColumn($this->table);
            $queryCount = 'SELECT COUNT(*) FROM #__acym_'.acym_secureDBColumn($this->table);

            if (empty($settings['ordering'])) $settings['ordering'] = $this->pkey;
            $query .= ' ORDER BY `'.acym_secureDBColumn($settings['ordering']).'`';
            if (!empty($settings['ordering_sort_order'])) $query .= ' '.acym_secureDBColumn(strtoupper($settings['ordering_sort_order']));

            $elements = acym_loadObjectList($query);
            $total = acym_loadResult($queryCount);
        } else {
            $elements = [];
            $total = '0';
        }

        return [
            'elements' => $elements,
            'total' => $total,
        ];
    }

    /**
     * @param $id Id of element
     *
     * @return mixed
     */
    public function getOneById($id)
    {
        return acym_loadObject('SELECT * FROM #__acym_'.acym_secureDBColumn($this->table).' WHERE `'.acym_secureDBColumn($this->pkey).'` = '.intval($id));
    }

    public function getAll($key = null)
    {
        if (empty($key)) $key = $this->pkey;

        return acym_loadObjectList('SELECT * FROM #__acym_'.acym_secureDBColumn($this->table), $key);
    }

    public function save($element)
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

        if (empty($cloneElement->$pkey)) {
            $status = acym_insertObject('#__acym_'.$this->table, $cloneElement);
        } else {
            $status = acym_updateObject('#__acym_'.$this->table, $cloneElement, $pkey);
        }

        if (!$status) {
            $dbError = strip_tags(acym_getDBError());
            if (!empty($dbError)) {
                if (strlen($dbError) > 203) $dbError = substr($dbError, 0, 200).'...';
                $this->errors[] = $dbError;
            }

            return false;
        }

        //We return the element not modify if we want it later in the code
        return empty($cloneElement->$pkey) ? $status : $cloneElement->$pkey;
    }

    public function delete($elements)
    {
        if (!is_array($elements)) $elements = [$elements];
        if (empty($elements)) return 0;

        $column = is_numeric(reset($elements)) ? $this->pkey : $this->namekey;

        //Secure the query
        foreach ($elements as $key => $val) {
            $elements[$key] = acym_escapeDB($val);
        }

        if (empty($column) || empty($this->pkey) || empty($this->table) || empty($elements)) {
            return false;
        }

        $query = 'DELETE FROM #__acym_'.acym_secureDBColumn($this->table).' WHERE '.acym_secureDBColumn($column).' IN ('.implode(',', $elements).')';
        $result = acym_query($query);

        if (!$result) {
            return false;
        }

        acym_trigger('onAcymAfter'.$this->table.'Delete', [&$elements]);

        return $result;
    }

    public function setActive($elements)
    {
        if (!is_array($elements)) {
            $elements = [$elements];
        }

        if (empty($elements)) {
            return 0;
        }

        acym_arrayToInteger($elements);
        acym_query('UPDATE '.acym_secureDBColumn('#__acym_'.$this->table).' SET active = 1 WHERE `'.acym_secureDBColumn($this->pkey).'` IN ('.implode(',', $elements).')');
    }

    public function setInactive($elements)
    {
        if (!is_array($elements)) {
            $elements = [$elements];
        }

        if (empty($elements)) {
            return 0;
        }

        acym_arrayToInteger($elements);
        acym_query('UPDATE '.acym_secureDBColumn('#__acym_'.$this->table).' SET active = 0 WHERE `'.acym_secureDBColumn($this->pkey).'` IN ('.implode(',', $elements).')');
    }
}
