<?php

namespace AcyMailing\Classes;

use AcyMailing\Core\AcymClass;

class HistoryClass extends AcymClass
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'history';
    }

    /**
     * Function insert to insert a line into the history...
     * User modification, user update, bounces... etc.
     */
    public function insert(int $userId, string $action, array $data = [], int $mailid = 0, $unsubscribe_reason = null): void
    {
        $currentUserid = acym_currentUserId();
        if (!empty($currentUserid)) {
            $data[] = acym_translation('EXECUTED_BY').'::'.$currentUserid.' ( '.acym_currentUserName().' )';
        }
        $history = new \stdClass();
        $history->user_id = intval($userId);
        $history->action = strip_tags($action);
        $history->data = implode("\n", $data);
        $history->unsubscribe_reason = $unsubscribe_reason;
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
        $history->ip = acym_getIP();

        if (!empty($_SERVER)) {
            $source = [];
            $vars = ['HTTP_REFERER', 'HTTP_USER_AGENT', 'HTTP_HOST', 'SERVER_ADDR', 'REMOTE_ADDR', 'REQUEST_URI', 'QUERY_STRING'];

            foreach ($vars as $oneVar) {
                if (!empty($_SERVER[$oneVar])) {
                    $source[] = $oneVar.'::'.strip_tags($_SERVER[$oneVar]);
                }
            }
            $history->source = implode("\n", $source);
        }

        try {
            acym_insertObject('#__acym_history', $history);
        } catch (\Exception $e) {
        }
    }

    private function alreadyExists(int $userId, int $time): bool
    {
        $result = acym_loadResult('SELECT user_id FROM #__acym_history WHERE user_id = '.intval($userId).' AND date = '.intval($time));

        return !empty($result);
    }

    /**
     * Get all history lines for one user order by date descending
     */
    public function getHistoryOfOneById(int $id): array
    {
        $query = 'SELECT h.*, m.id, m.subject FROM #__acym_'.$this->table.' AS h ';
        $query .= 'LEFT JOIN #__acym_mail AS m ON h.mail_id = m.id ';
        $query .= 'WHERE h.user_id = '.intval($id);
        $query .= ' ORDER BY h.date DESC';

        return acym_loadObjectList($query);
    }

    public function getAllUnsubReasons(): array
    {
        $query = 'SELECT `action`, `data`, `unsubscribe_reason` FROM #__acym_history WHERE `action` = "unsubscribed" AND `unsubscribe_reason` != ""';

        return acym_loadObjectList($query);
    }

    public function getAllMainLanguageUnsubReasons(): array
    {
        $allUnsubReasons = $this->config->get('unsub_survey', '{}');

        return json_decode($allUnsubReasons, true);
    }

    public function getUnsubscribeReasonText($index)
    {
        $surveyAnswers = json_decode($this->config->get('unsub_survey'), true);
        if (is_numeric($index)) {
            $index = $index - 1;
            $reason = $surveyAnswers[$index] ?? $index;
        } else {
            $reason = $index;
        }

        return $reason;
    }
}
