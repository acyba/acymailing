<?php

namespace AcyMailing\Helpers;

use AcyMailing\Classes\MailClass;
use AcyMailing\Core\AcymObject;

class AutomationHelper extends AcymObject
{
    const TYPE_PHONE = 'phone';
    const TYPE_DATETIME = 'datetime';
    const TYPE_TIMESTAMP = 'timestamp';

    public string $from = ' `#__acym_user` AS `user`';
    public array $leftjoin = [];
    public array $join = [];
    public array $where = [];
    public string $orderBy = '';
    public string $groupBy = '';
    public string $limit = '';
    public bool $excludeSelected = false;

    public function getQuery(array $select = []): string
    {
        $query = '';
        if (!empty($select)) {
            $query = ' SELECT ';
            if (strpos($select[0], 'COUNT') === false) {
                $query .= 'DISTINCT ';
            }
            $query .= implode(', ', $select);
        }
        if (!empty($this->from)) $query .= ' FROM '.$this->from;
        if (!empty($this->join)) $query .= ' JOIN '.implode(' JOIN ', $this->join);
        if (!empty($this->leftjoin)) $query .= ' LEFT JOIN '.implode(' LEFT JOIN ', $this->leftjoin);
        if (!empty($this->where)) $query .= ' WHERE ('.implode(') AND (', $this->where).')';
        if (!empty($this->groupBy)) $query .= ' GROUP BY '.$this->groupBy;
        if (!empty($this->orderBy)) $query .= ' ORDER BY '.$this->orderBy;
        if (!empty($this->limit)) $query .= ' LIMIT '.$this->limit;

        return $query;
    }

    public function count(): int
    {
        $result = intval(acym_loadResult($this->getQuery(['COUNT(DISTINCT user.id)'])));

        if (empty($this->limit)) {
            return $result;
        } else {
            return min($result, $this->limit);
        }
    }

    /**
     * The main point is to simplify the queries executed on automation actions.
     * It also avoids issues when an action modifies the matching users and they don't match anymore (so following actions won't be executed)
     */
    public function addFlag(int $id, bool $reset = false): void
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
        $this->where = $reset ? [] : ['user.automation '.($this->excludeSelected ? 'NOT ' : '').'LIKE "%a'.intval($id).'a%"'];
        $this->orderBy = '';
        $this->limit = '';
    }

    public function removeFlag(int $id): void
    {
        acym_query(
            'UPDATE #__acym_user 
            SET `automation` = REPLACE(`automation`, "a'.$id.'a", "") 
            WHERE `automation` LIKE "%a'.$id.'a%"'
        );
    }

    public function convertQuery(string $table, string $column, string $operator, $value, string $type = ''): string
    {
        //Fix operator issue...
        $operator = str_replace(['&lt;', '&gt;'], ['<', '>'], $operator);

        if ($operator === 'CONTAINS' || ($type === self::TYPE_PHONE && $operator === '=')) {
            $operator = 'LIKE';
            $value = '%'.$value.'%';
        } elseif ($operator === 'BEGINS') {
            $operator = 'LIKE';
            $value = $value.'%';
        } elseif ($operator === 'END') {
            $operator = 'LIKE';
            $value = '%'.$value;
        } elseif ($operator === 'NOTCONTAINS' || ($type === self::TYPE_PHONE && $operator === '!=')) {
            $operator = 'NOT LIKE';
            $value = '%'.$value.'%';
        } elseif ($operator === 'REGEXP') {
            if ($value === '') {
                return '1 = 1';
            }
        } elseif ($operator === 'NOT REGEXP') {
            if ($value === '') {
                return '0 = 1';
            }
        } elseif (!in_array($operator, ['IS NULL', 'IS NOT NULL', 'NOT LIKE', 'LIKE', '=', '!=', '>', '<', '>=', '<='])) {
            die(acym_translationSprintf('ACYM_UNKNOWN_OPERATOR', $operator));
        }

        //Is the value a time field?
        //If so, we replace it properly and we convert it into the right time field
        if (strpos($value, '[time]') !== false) {
            $value = acym_replaceDate($value);
            $value = date('Y-m-d H:i:s', $value);
        }

        $value = acym_replaceDateTags($value);

        //If it's a number, it does not bother us to db quote if we use = or !=, but if we use < or >=, it could make '20' > '100' as it begins by '2' and 100 begins by '1'...
        if (!is_numeric($value) || in_array($operator, ['REGEXP', 'NOT REGEXP', 'NOT LIKE', 'LIKE', '=', '!='])) {
            $value = acym_escapeDB($value);
        }

        if (in_array($operator, ['IS NULL', 'IS NOT NULL'])) {
            $value = '';
        }

        if (!empty($table)) {
            $table = acym_secureDBColumn($table).'.';
        }
        if ($type === self::TYPE_DATETIME && in_array($operator, ['=', '!='])) {
            return 'DATE_FORMAT('.$table.'`'.acym_secureDBColumn($column).'`, "%Y-%m-%d") '.$operator.' '.'DATE_FORMAT('.$value.', "%Y-%m-%d")';
        }
        if ($type === self::TYPE_TIMESTAMP && in_array($operator, ['=', '!='])) {
            return 'FROM_UNIXTIME('.$table.'`'.acym_secureDBColumn($column).'`, "%Y-%m-%d") '.$operator.' '.'FROM_UNIXTIME('.$value.', "%Y-%m-%d")';
        }

        return $table.'`'.acym_secureDBColumn($column).'` '.$operator.' '.$value;
    }

    public function deleteUnusedEmails(): void
    {
        $automationEmails = acym_loadResultArray('SELECT id FROM #__acym_mail WHERE type = "automation"');

        $emailsToDelete = [];
        foreach ($automationEmails as $email) {
            $search = '"acy_add_queue":{"mail_id":"'.$email.'"';
            $action = acym_loadResult('SELECT id FROM #__acym_action WHERE actions LIKE '.acym_escapeDB('%'.$search.'%'));

            if (empty($action)) $emailsToDelete[] = $email;
        }

        if (!empty($emailsToDelete)) {
            $mailClass = new MailClass();
            $mailClass->delete($emailsToDelete);
        }
    }
}
