<?php

class acymqueryClass extends acymClass
{
    var $from = ' `#__acym_user` AS `user`';
    var $leftjoin = [];
    var $join = [];
    var $where = [];
    var $orderBy = '';
    var $limit = '';

    public function getQuery($select = [])
    {
        $query = '';
        if (!empty($select)) $query .= ' SELECT DISTINCT '.implode(',', $select);
        if (!empty($this->from)) $query .= ' FROM '.$this->from;
        if (!empty($this->join)) $query .= ' JOIN '.implode(' JOIN ', $this->join);
        if (!empty($this->leftjoin)) $query .= ' LEFT JOIN '.implode(' LEFT JOIN ', $this->leftjoin);
        if (!empty($this->where)) $query .= ' WHERE ('.implode(') AND (', $this->where).')';
        if (!empty($this->orderBy)) $query .= ' ORDER BY '.$this->orderBy;
        if (!empty($this->limit)) $query .= ' LIMIT '.$this->limit;

        return $query;
    }

    public function count()
    {
        return acym_loadResult($this->getQuery(['COUNT(DISTINCT user.id)']));
    }

    /**
     * The main point is to simplify the queries executed on automation actions.
     * It also avoids issues when an action modifies the matching users and they don't match anymore (so following actions won't be executed)
     *
     * @param $id automation id
     */
    public function addFlag($id)
    {
        // In MySQL, the ORDER BY and LIMIT are not supported with "IN" or in UPDATE queries... If you know how to optimize it, feel free to do so
        if (!empty($this->orderBy) || !empty($this->limit)) {
            $flagQuery = 'UPDATE #__acym_user';
            $flagQuery .= ' SET automation = CONCAT(automation, "a'.intval($id).'a")';
            $flagQuery .= ' WHERE id IN (
			SELECT id FROM (SELECT user.id FROM #__acym_user AS user';
            if (!empty($this->join)) $flagQuery .= ' JOIN '.implode(' JOIN ', $this->join);
            if (!empty($this->leftjoin)) $flagQuery .= ' LEFT JOIN '.implode(' LEFT JOIN ', $this->leftjoin);
            if (!empty($this->where)) $flagQuery .= ' WHERE ('.implode(') AND (', $this->where).')';
            if (!empty($this->orderBy)) $flagQuery .= ' ORDER BY '.$this->orderBy;
            if (!empty($this->limit)) $flagQuery .= ' LIMIT '.$this->limit;
            $flagQuery .= ') tmp);';
        } else {
            $flagQuery = 'UPDATE #__acym_user AS user ';
            if (!empty($this->join)) $flagQuery .= ' JOIN '.implode(' JOIN ', $this->join);
            if (!empty($this->leftjoin)) $flagQuery .= ' LEFT JOIN '.implode(' LEFT JOIN ', $this->leftjoin);
            $flagQuery .= ' SET user.automation = CONCAT(user.automation, "a'.intval($id).'a")';
            if (!empty($this->where)) $flagQuery .= ' WHERE ('.implode(') AND (', $this->where).')';
        }
        acym_query($flagQuery);

        $this->join = [];
        $this->leftjoin = [];
        $this->where = ['user.automation LIKE "%a'.intval($id).'a%"'];
        $this->orderBy = '';
        $this->limit = '';
    }

    public function removeFlag($id)
    {
        acym_query('UPDATE #__acym_user SET automation = REPLACE(automation, "a'.intval($id).'a", "") WHERE automation LIKE "%a'.intval($id).'a%"');
    }

    // $type can be '', 'timestamp', 'datetime' or 'phone'
    public function convertQuery($table, $column, $operator, $value, $type = '')
    {
        //Fix operator issue...
        $operator = str_replace(['&lt;', '&gt;'], ['<', '>'], $operator);

        if ($operator == 'CONTAINS' || ($type == 'phone' && $operator == '=')) {
            $operator = 'LIKE';
            $value = '%'.$value.'%';
        } elseif ($operator == 'BEGINS') {
            $operator = 'LIKE';
            $value = $value.'%';
        } elseif ($operator == 'END') {
            $operator = 'LIKE';
            $value = '%'.$value;
        } elseif ($operator == 'NOTCONTAINS' || ($type == 'phone' && $operator == '!=')) {
            $operator = 'NOT LIKE';
            $value = '%'.$value.'%';
        } elseif ($operator == 'REGEXP') {
            if ($value === '') return '1 = 1';
        } elseif ($operator == 'NOT REGEXP') {
            if ($value === '') return '0 = 1';
        } elseif (!in_array($operator, ['IS NULL', 'IS NOT NULL', 'NOT LIKE', 'LIKE', '=', '!=', '>', '<', '>=', '<='])) {
            die(acym_translation_sprintf('ACYM_UNKNOWN_OPERATOR', $operator));
        }

        //Is the value a time field?
        //If so, we replace it properly and we convert it into the right time field
        if (strpos($value, '[time]') !== false) {
            $value = acym_replaceDate($value);
            $value = strftime('%Y-%m-%d %H:%M:%S', $value);
        }

        $value = acym_replaceDateTags($value);

        //If it's a number, it does not bother us to db quote if we use = or !=, but if we use < or >=, it could make '20' > '100' as it begins by '2' and 100 begins by '1'...
        if (!is_numeric($value) || in_array($operator, ['REGEXP', 'NOT REGEXP', 'NOT LIKE', 'LIKE', '=', '!='])) {
            $value = acym_escapeDB($value);
        }

        if (in_array($operator, ['IS NULL', 'IS NOT NULL'])) {
            $value = '';
        }

        if ($type == 'datetime' && in_array($operator, ['=', '!='])) {
            return 'DATE_FORMAT('.acym_secureDBColumn($table).'.`'.acym_secureDBColumn($column).'`, "%Y-%m-%d") '.$operator.' '.'DATE_FORMAT('.$value.', "%Y-%m-%d")';
        }
        if ($type == 'timestamp' && in_array($operator, ['=', '!='])) {
            return 'FROM_UNIXTIME('.acym_secureDBColumn($table).'.`'.acym_secureDBColumn($column).'`, "%Y-%m-%d") '.$operator.' '.'FROM_UNIXTIME('.$value.', "%Y-%m-%d")';
        }

        return acym_secureDBColumn($table).'.`'.acym_secureDBColumn($column).'` '.$operator.' '.$value;
    }
}
