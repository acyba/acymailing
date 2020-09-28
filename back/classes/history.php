<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class HistoryClass extends acymClass
{
    var $table = 'history';

    /**
     * Function insert to insert a line into the history...
     * User modification, user update, bounces... etc.
     */
    public function insert($userId, $action, $data = [], $mailid = 0)
    {
        $currentUserid = acym_currentUserId();
        if (!empty($currentUserid)) {
            $data[] = acym_translation('EXECUTED_BY').'::'.$currentUserid.' ( '.acym_currentUserName().' )';
        }
        $history = new \stdClass();
        $history->user_id = intval($userId);
        $history->action = strip_tags($action);
        $history->data = implode("\n", $data);
        //Avoid a memory issue when the data is way too big.
        if (strlen($history->data) > 100000) {
            $history->data = substr($history->data, 0, 10000);
        }

        static $date = null;
        if (empty($date)) {
            $date = time();
        }

        $history->date = ++$date;
        while ($this->alreadyExists($history->user_id, $history->date)) {
            $history->date++;
        }

        $date = $history->date;

        $history->mail_id = $mailid;
        if ($this->config->get('anonymous_tracking', 0) == 0) {
            $history->ip = acym_getIP();
        }

        if (!empty($_SERVER)) {
            $source = [];
            if ($this->config->get('anonymous_tracking', 0) == 0) {
                $vars = ['HTTP_REFERER', 'HTTP_USER_AGENT', 'HTTP_HOST', 'SERVER_ADDR', 'REMOTE_ADDR', 'REQUEST_URI', 'QUERY_STRING'];
            } else {
                $vars = ['HTTP_REFERER', 'HTTP_HOST', 'SERVER_ADDR', 'REQUEST_URI', 'QUERY_STRING'];
            }

            foreach ($vars as $oneVar) {
                if (!empty($_SERVER[$oneVar])) {
                    $source[] = $oneVar.'::'.strip_tags($_SERVER[$oneVar]);
                }
            }
            $history->source = implode("\n", $source);
        }

        return acym_insertObject('#__acym_history', $history);
    }

    public function alreadyExists($userId, $date)
    {
        $result = acym_loadResult('SELECT user_id FROM #__acym_history WHERE user_id = '.intval($userId).' AND date = '.acym_escapeDB($date));

        return !empty($result);
    }

    /**
     * Get all history lines for one user order by date descending
     *
     * @param int $id
     *
     * @return array
     */
    public function getHistoryOfOneById($id)
    {
        $query = 'SELECT h.*, m.id, m.subject FROM #__acym_'.$this->table.' AS h ';
        $query .= 'LEFT JOIN #__acym_mail AS m ON h.mail_id = m.id ';
        $query .= 'WHERE h.user_id = '.intval($id);
        $query .= ' ORDER BY h.date DESC';

        return acym_loadObjectList($query);
    }
}
