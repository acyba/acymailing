<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class MailStatClass extends acymClass
{
    var $table = 'mail_stat';
    var $pkey = 'mail_id';

    public function save($mailStat)
    {
        $column = [];
        $valueColumn = [];
        $columnName = acym_getColumns('mail_stat');

        if (!is_array($mailStat)) {
            $mailStat = (array)$mailStat;
        }

        foreach ($mailStat as $key => $value) {
            if (in_array($key, $columnName)) {
                $column[] = '`'.acym_secureDBColumn($key).'`';
                $valueColumn[] = acym_escapeDB($value);
            }
        }

        $query = '#__acym_mail_stat ('.implode(',', $column).') VALUES ('.implode(',', $valueColumn).')';

        $onDuplicate = [];

        if (!empty($mailStat['sent'])) {
            $onDuplicate[] = ' sent = sent + '.intval($mailStat['sent']);
        }

        if (!empty($mailStat['fail'])) {
            $onDuplicate[] = ' fail = fail + '.intval($mailStat['fail']);
        }

        if (!empty($mailStat['open_unique'])) {
            $onDuplicate[] = 'open_unique = open_unique + 1';
        }

        if (!empty($mailStat['open_total'])) {
            $onDuplicate[] = 'open_total = open_total + 1';
        }

        if (!empty($mailStat['total_subscribers'])) {
            $onDuplicate[] = 'total_subscribers = '.intval($mailStat['total_subscribers']);
        }

        if (!empty($mailStat['unsubscribe_total'])) {
            $onDuplicate[] = ' unsubscribe_total = unsubscribe_total + '.intval($mailStat['unsubscribe_total']);
        }

        if (!empty($onDuplicate)) {
            $query .= ' ON DUPLICATE KEY UPDATE ';
            $query .= implode(',', $onDuplicate);
            $query = 'INSERT INTO '.$query;
        } else {
            $query = 'INSERT IGNORE INTO '.$query;
        }

        acym_query($query);
    }

    public function getTotalSubscribersByMailId($mailId)
    {
        $result = acym_loadResult('SELECT total_subscribers FROM #__acym_mail_stat WHERE mail_id = '.intval($mailId));

        return $result === null ? 0 : $result;
    }

    public function getOneByMailId($id = '')
    {
        $query = 'SELECT SUM(sent) AS sent, SUM(fail) AS fail FROM #__acym_mail_stat';
        $query .= empty($id) ? '' : ' WHERE `mail_id` = '.intval($id);

        return acym_loadObject($query);
    }

    public function getAllFromMailIds($mailsIds = [])
    {
        acym_arrayToInteger($mailsIds);
        if (empty($mailsIds)) {
            $mailsIds[] = 0;
        }

        $result = acym_loadObjectList('SELECT * FROM #__acym_mail_stat WHERE mail_id IN ('.implode(',', $mailsIds).')', 'mail_id');

        return $result === null ? 0 : $result;
    }

    public function getOneRowByMailId($mailId)
    {
        $query = 'SELECT * FROM #__acym_mail_stat WHERE mail_id = '.intval($mailId);

        return acym_loadObject($query);
    }

    public function getAllMailsForStats($search = '')
    {
        $mailClass = new MailClass();

        $query = 'SELECT mail.* 
                  FROM #__acym_mail AS mail 
                  JOIN #__acym_mail_stat AS mail_stat ON mail.id = mail_stat.mail_id';

        $querySearch = '';

        if (!empty($search)) {
            $querySearch .= ' AND mail.name LIKE '.acym_escapeDB('%'.$search.'%').' ';
        }

        $query .= ' WHERE mail.parent_id IS NULL '.$querySearch;

        $query .= ' ORDER BY mail_stat.send_date DESC LIMIT 20';

        return $mailClass->decode(acym_loadObjectList($query));
    }

    public function getCumulatedStatsByMailIds($mailsIds = [])
    {
        acym_arrayToInteger($mailsIds);
        $condMailIds = '';
        if (!empty($mailsIds)) {
            $condMailIds = 'WHERE mail_id IN ('.implode(',', $mailsIds).')';
        }

        $query = 'SELECT SUM(sent) AS sent, SUM(open_unique) AS open, SUM(fail) AS fails, SUM(bounce_unique) AS bounces FROM #__acym_mail_stat '.$condMailIds;

        return acym_loadObject($query);
    }
}
