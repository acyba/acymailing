<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class UserStatClass extends acymClass
{
    var $table = 'user_stat';

    const DESKTOP_DEVICES = [
        'windows' => 'Windows',
        'macintosh' => 'Mac',
        'linux' => 'Linux',
    ];

    const MOBILE_DEVICES = [
        'bada' => 'Bada',
        'ubuntu; mobile' => 'Ubuntu Mobile',
        'ubuntu; tablet' => 'Ubuntu Tablet',
        'tizen' => 'Tizen',
        'palm os' => 'Palm',
        'meego' => 'meeGo',
        'symbian' => 'Symbian',
        'symbos' => 'Symbian',
        'blackberry' => 'BlackBerry',
        'windows ce' => 'Windows Phone',
        'windows mobile' => 'Windows Phone',
        'windows phone' => 'Windows Phone',
        'iphone' => 'iPhone',
        'ipad' => 'iPad',
        'ipod' => 'iPod',
        'android' => 'Android',
    ];

    public function save($userStat, $overrideSendDate = false)
    {
        $column = [];
        $valueColumn = [];
        $columnName = acym_getColumns('user_stat');
        if (![$userStat] || !is_array($userStat)) {
            $userStat = (array)$userStat;
        }

        foreach ($userStat as $key => $value) {
            if (in_array($key, $columnName)) {
                $column[] = '`'.acym_secureDBColumn($key).'`';

                if ($key === 'tracking_sale') {
                    $valueColumn[] = strlen($value) === 0 ? 'NULL' : floatval($value);
                } else {
                    $valueColumn[] = acym_escapeDB($value);
                }
            }
        }

        $query = 'INSERT INTO #__acym_user_stat ('.implode(',', $column).') VALUE ('.implode(', ', $valueColumn).')';
        $onDuplicate = [];

        if (!empty($userStat['statusSending'])) {
            $onDuplicate[] = $userStat['statusSending'] == 0 ? 'fail = fail + 1' : 'sent = sent + 1';
        }

        if ($overrideSendDate && !empty($userStat['send_date'])) {
            $onDuplicate[] = 'send_date = '.acym_escapeDB($userStat['send_date']);
        }

        if (!empty($userStat['open'])) {
            $onDuplicate[] = 'open = open + 1';
            $automationClass = new AutomationClass();
            $automationClass->trigger('user_open', ['userId' => $userStat['user_id'], 'mailId' => $userStat['mail_id']]);
        }

        if (!empty($userStat['open_date'])) {
            $onDuplicate[] = 'open_date = '.acym_escapeDB($userStat['open_date']);
        }

        if (!empty($userStat['tracking_sale'])) {
            $onDuplicate[] = 'tracking_sale = '.floatval($userStat['tracking_sale']);
        }

        if (!empty($userStat['currency'])) {
            $onDuplicate[] = 'currency = '.acym_escapeDB($userStat['currency']);
        }

        if (!empty($userStat['unsubscribe'])) {
            $onDuplicate[] = 'unsubscribe = '.intval($userStat['unsubscribe']);
        }

        if (!empty($userStat['device'])) {
            $onDuplicate[] = 'device = '.acym_escapeDB($userStat['device']);
        }

        if (!empty($userStat['opened_with'])) {
            $onDuplicate[] = 'opened_with = '.acym_escapeDB($userStat['opened_with']);
        }

        if (!empty($onDuplicate)) {
            $query .= ' ON DUPLICATE KEY UPDATE ';
            $query .= implode(',', $onDuplicate);
        }

        acym_query($query);
    }

    public function getOneByMailAndUserId($mail_id, $user_id)
    {
        $query = 'SELECT * FROM #__acym_user_stat WHERE `mail_id` = '.intval($mail_id).' AND `user_id` = '.intval($user_id);

        return acym_loadObject($query);
    }

    public function getDetailedStatistics(array $options)
    {
        $limit = $options['limit'] ?? 10;
        $offset = $options['offset'] ?? 0;
        $mailId = $options['mail_id'] ?? 0;

        return acym_loadObjectList(
            'SELECT user_stat.*, `user`.email
            FROM #__acym_user_stat AS user_stat
            JOIN #__acym_user AS `user` ON user_stat.user_id = `user`.id 
            WHERE `mail_id` = '.intval($mailId),
            '',
            $offset,
            $limit
        );
    }

    public function getAllUserStatByUserId($idUser)
    {
        $query = 'SELECT * FROM #__acym_user_stat WHERE user_id = '.intval($idUser);

        return acym_loadObjectList($query);
    }

    public function getDetailedStats($settings)
    {
        $mailClass = new MailClass();

        $query = 'SELECT us.*, m.name, m.subject, m.parent_id, u.email, u.name AS username 
                    FROM #__acym_user_stat AS us
                    LEFT JOIN #__acym_user AS u ON us.user_id = u.id
                    INNER JOIN #__acym_mail AS m ON us.mail_id = m.id';
        $queryCount = 'SELECT COUNT(*) FROM #__acym_user_stat as us
                        LEFT JOIN #__acym_user AS u ON us.user_id = u.id
                        INNER JOIN #__acym_mail AS m ON us.mail_id = m.id';
        $where = [];

        if (!empty($settings['mail_ids'])) {
            if (!is_array($settings['mail_ids'])) $settings['mail_ids'] = [$settings['mail_ids']];
            acym_arrayToInteger($settings['mail_ids']);
            $where[] = 'us.mail_id IN ('.implode(',', $settings['mail_ids']).')';
        }

        if (!empty($settings['search'])) {
            $searchTerms = acym_escapeDB('%'.$settings['search'].'%');
            $where[] = 'm.name LIKE '.$searchTerms.' OR u.email LIKE '.$searchTerms.' OR u.name LIKE '.$searchTerms;
        }

        if (!empty($where)) {
            $query .= ' WHERE ('.implode(') AND (', $where).')';
            $queryCount .= ' WHERE ('.implode(') AND (', $where).')';
        }

        if (!empty($settings['ordering']) && !empty($settings['ordering_sort_order'])) {
            if (in_array($settings['ordering'], ['email', 'name'])) {
                $table = 'u';
            } elseif ($settings['ordering'] === 'subject') {
                $table = 'm';
            } else {
                $table = 'us';
            }
            $query .= ' ORDER BY '.$table.'.'.acym_secureDBColumn($settings['ordering']).' '.acym_secureDBColumn(strtoupper($settings['ordering_sort_order'])).', us.user_id ASC';
        }

        $mails = acym_loadObjectList($query, '', $settings['offset'], $settings['detailedStatsPerPage']);

        if (!empty($mails)) {
            if (acym_isTrackingSalesActive()) {
                $mailIds = [];
                foreach ($mails as $mail) {
                    if (!in_array($mail->mail_id, $mailIds)) $mailIds[] = $mail->mail_id;
                }

                $trackingSales = [];
                $trackingSalesDB = acym_loadObjectList(
                    'SELECT SUM(tracking_sale) as sale, currency, mail_id, user_id FROM #__acym_user_stat WHERE mail_id IN ('.implode(
                        ',',
                        $mailIds
                    ).')  AND currency IS NOT NULL GROUP BY user_id, mail_id'
                );
                foreach ($trackingSalesDB as $oneMailTrackSales) {
                    $trackingSales[$oneMailTrackSales->mail_id.'-'.$oneMailTrackSales->user_id] = $oneMailTrackSales;
                }
            }

            $totalClicksForMails = [];
            $campaignsForMails = [];
            foreach ($mails as $key => $mail) {
                // Add tracked income amount
                if (!empty($trackingSales[$mail->mail_id.'-'.$mail->user_id])) {
                    acym_trigger('getCurrency', [&$mails[$key]->currency]);
                    $mails[$key]->sales = $trackingSales[$mail->mail_id.'-'.$mail->user_id]->sale;
                }

                // Add total clicks
                if (!isset($totalClicksForMails[$mail->mail_id])) {
                    $totalClicksForMails[$mail->mail_id] = acym_loadObjectList(
                        'SELECT SUM(click) AS nbClicks, user_id
                        FROM #__acym_url_click 
                        WHERE mail_id = '.intval($mail->mail_id).' 
                        GROUP BY user_id',
                        'user_id'
                    );
                }
                $mails[$key]->total_click = empty($totalClicksForMails[$mail->mail_id][$mail->user_id]) ? 0 : $totalClicksForMails[$mail->mail_id][$mail->user_id]->nbClicks;

                // Handle multilingual
                $mainMailId = empty($mail->parent_id) ? $mail->mail_id : $mail->parent_id;
                if (!isset($campaignsForMails[$mainMailId])) {
                    $campaignsForMails[$mainMailId] = acym_loadObject('SELECT id AS campaign_id, parent_id FROM #__acym_campaign WHERE mail_id = '.intval($mainMailId));
                }

                $mails[$key]->campaign_id = empty($campaignsForMails[$mainMailId]) ? 0 : $campaignsForMails[$mainMailId]->campaign_id;
                $mails[$key]->parent_id = empty($campaignsForMails[$mainMailId]) ? 0 : $campaignsForMails[$mainMailId]->parent_id;
            }
        }

        $results['detailed_stats'] = $mailClass->decode($mails);
        $results['total'] = acym_loadResult($queryCount);

        return $results;
    }

    public function getTotalFailClickOpenByMailIds($mailIds)
    {
        acym_arrayToInteger($mailIds);
        if (empty($mailIds)) return [];

        $query = 'SELECT mail_id, SUM(fail) AS fail, SUM(sent) AS sent, SUM(open) AS open FROM #__acym_user_stat WHERE mail_id IN ('.implode(',', $mailIds).') GROUP BY mail_id';

        return acym_loadObjectList($query, 'mail_id');
    }

    public function getUserWithNoMailOpen()
    {
        $query = 'SELECT user_id FROM #__acym_user_stat GROUP BY user_id HAVING MAX(open) = 0';

        return acym_loadResultArray($query);
    }

    public function getOpenTimeStats($mailIds)
    {

        if (!is_array($mailIds)) $mailIds = [$mailIds];
        acym_arrayToInteger($mailIds);
        $where = empty($mailIds) ? '' : ' AND mail_id in ('.implode(',', $mailIds).')';

        $query = 'SELECT SUM(`open`) AS open_total, DATE_FORMAT(open_date, "%w") AS day, FORMAT(CONVERT(DATE_FORMAT(open_date, "%H"), SIGNED INTEGER) / 3, 0) AS hour, CONCAT(DATE_FORMAT(open_date, "%w"), "_", FORMAT(CONVERT(DATE_FORMAT(open_date, "%H"), SIGNED INTEGER) / 3, 0)) AS date_id FROM `#__acym_user_stat` WHERE open_date IS NOT NULL '.$where.' GROUP BY date_id';

        $return['total_open'] = acym_loadResult('SELECT SUM(`open`) FROM #__acym_user_stat WHERE open_date IS NOT NULL '.$where);
        $return['stats'] = acym_loadObjectList($query, 'date_id');

        return $return;
    }

    public function getDefaultStat()
    {
        $percentageRemaining = 100;
        $stats = [];


        for ($day = 0 ; $day < 7 ; $day++) {
            $stats[$day] = [];
            for ($hour = 0 ; $hour < 8 ; $hour++) {
                $hourPercentage = $this->getRandomStatOpenTime($percentageRemaining, $hour);
                $stats[$day][$hour] = $hourPercentage;
            }
        }

        return $stats;
    }

    private function getRandomStatOpenTime(&$percentageRemaining, $hour)
    {
        if (empty($percentageRemaining)) return 0;
        $randoms = [
            0 => [0, 1],
            1 => [0, 2],
            2 => [1, 2],
            3 => [1, 3],
            4 => [3, 5],
            5 => [1, 3],
            6 => [1, 2],
            7 => [0, 2],
        ];

        $percentage = rand($randoms[$hour][0], $randoms[$hour][1]);

        if ($percentageRemaining - $percentage < 0) return 0;


        $percentageRemaining -= $percentage;

        return $percentage;
    }

    public function getOpenSourcesStats($mailIds = [])
    {
        if (!is_array($mailIds)) {
            $mailIds = [$mailIds];
        }
        acym_arrayToInteger($mailIds);

        $query = 'SELECT opened_with, COUNT(*) AS number FROM #__acym_user_stat WHERE `open` > 0';
        if (!empty($mailIds)) {
            $query .= ' AND mail_id IN ('.implode(',', $mailIds).')';
        }
        $query .= ' GROUP BY opened_with';

        return acym_loadObjectList($query);
    }

    public function deleteDetailedStatsPeriod(): array
    {
        $shouldClearExpiredStats = $this->config->get('delete_stats_enabled', 0);
        if (empty($shouldClearExpiredStats)) {
            return [];
        }

        $deleteAfterXSeconds = $this->config->get('delete_stats', 86400 * 360);
        if (empty($deleteAfterXSeconds)) {
            return [];
        }

        $date = acym_date(time() - $deleteAfterXSeconds, 'Y-m-d H:i');

        $queries = [
            '#__acym_user_stat' => 'DELETE FROM #__acym_user_stat WHERE send_date < '.acym_escapeDB($date),
            '#__acym_url_click' => 'DELETE FROM #__acym_url_click WHERE date_click < '.acym_escapeDB($date),
        ];

        $messages = [];
        foreach ($queries as $table => $query) {
            try {
                $status = acym_query($query);
                if (!empty($status)) {
                    $messages[] = acym_translationSprintf('ACYM_DELETE_X_ROWS_TABLE_X', $status, acym_translation('ACYM_USER_DETAILED_STATS').' ('.$table.')');
                }
            } catch (\Exception $e) {
                $messages[] = $e->getMessage();
            }
        }

        if (empty($messages)) {
            return [];
        }

        return ['message' => implode("\r\n", $messages)];
    }
}
