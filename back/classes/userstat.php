<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class UserStatClass extends acymClass
{
    var $table = 'user_stat';

    public function save($userStat)
    {
        $column = [];
        $valueColumn = [];
        $columnName = acym_getColumns("user_stat");
        if (![$userStat] || !is_array($userStat)) {
            $userStat = (array)$userStat;
        }

        foreach ($userStat as $key => $value) {
            if (in_array($key, $columnName)) {
                $column[] = '`'.acym_secureDBColumn($key).'`';
                $valueColumn[] = acym_escapeDB($value);
            }
        }

        $query = "INSERT INTO #__acym_user_stat (".implode(',', $column).") VALUE (".implode(',', $valueColumn).")";
        $onDuplicate = [];

        if (!empty($userStat['statusSending'])) {
            $onDuplicate[] = $userStat['statusSending'] == 0 ? "fail = fail + 1" : "sent = sent + 1";
        }

        if (!empty($userStat['open'])) {
            $onDuplicate[] = "open = open + 1";
            $automationClass = new AutomationClass();
            $automationClass->trigger('user_open', ['userId' => $userStat['user_id']]);
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

        if (!empty($onDuplicate)) {
            $query .= " ON DUPLICATE KEY UPDATE ";
            $query .= implode(',', $onDuplicate);
        }

        acym_query($query);
    }

    public function getOneByMailAndUserId($mail_id, $user_id)
    {
        $query = 'SELECT * FROM #__acym_user_stat WHERE `mail_id` = '.intval($mail_id).' AND `user_id` = '.intval($user_id);

        return acym_loadObject($query);
    }

    public function getAllUserStatByUserId($idUser)
    {
        $query = 'SELECT * FROM #__acym_user_stat WHERE user_id = '.intval($idUser);

        return acym_loadObjectList($query);
    }


    public function getDetailedStats($settings)
    {
        $mailClass = new MailClass();

        $query = 'SELECT us.*, m.name, m.subject, u.email, u.name AS username, c.id as campaign_id, c.parent_id, 0 AS total_click 
                    FROM #__acym_user_stat AS us
                    LEFT JOIN #__acym_user AS u ON us.user_id = u.id
                    INNER JOIN #__acym_mail AS m ON us.mail_id = m.id
                    LEFT JOIN #__acym_campaign AS c ON m.id = c.mail_id';
        $queryCount = 'SELECT COUNT(*) FROM #__acym_user_stat as us
                        LEFT JOIN #__acym_user AS u ON us.user_id = u.id
                        INNER JOIN #__acym_mail AS m ON us.mail_id = m.id';
        $where = [];

        if (!empty($settings['mail_id'])) {
            $where[] = 'us.mail_id = '.intval($settings['mail_id']);
        }

        if (!empty($settings['search'])) {
            $searchTerms = acym_escapeDB('%'.$settings['search'].'%');
            $where[] = 'm.name LIKE '.$searchTerms.' OR u.email LIKE '.$searchTerms.' OR u.name LIKE '.$searchTerms;
        }

        if (!empty($where)) {
            $query .= ' WHERE ('.implode(') AND (', $where).')';
            $queryCount .= ' WHERE ('.implode(') AND (', $where).')';
        }

        $query .= ' GROUP BY us.mail_id, us.user_id';

        if (!empty($settings['ordering']) && !empty($settings['ordering_sort_order'])) {
            if (in_array($settings['ordering'], ['email', 'name'])) {
                $table = 'u';
            } elseif ($settings['ordering'] == 'subject') {
                $table = 'm';
            } else {
                $table = 'us';
            }
            $query .= ' ORDER BY '.$table.'.'.acym_secureDBColumn($settings['ordering']).' '.acym_secureDBColumn(strtoupper($settings['ordering_sort_order'])).', us.user_id ASC';
        }

        $mails = acym_loadObjectList($query, '', $settings['offset'], $settings['detailedStatsPerPage']);

        if (!empty($mails)) {
            $mailIds = [];
            foreach ($mails as $mail) {
                if (!in_array($mail->mail_id, $mailIds)) $mailIds[] = $mail->mail_id;
            }
            $userClick = acym_loadObjectList('SELECT user_id, mail_id, SUM(click) as total_click FROM #__acym_url_click WHERE mail_id IN ('.implode(',', $mailIds).') GROUP BY user_id, mail_id ');

            $trackingSales = [];
            if (acym_isTrackingSalesActive()) {
                $trackingSalesDB = acym_loadObjectList('SELECT SUM(tracking_sale) as sale, currency, mail_id, user_id FROM #__acym_user_stat WHERE mail_id IN ('.implode(',', $mailIds).')  AND currency IS NOT NULL GROUP BY user_id, mail_id');
                foreach ($trackingSalesDB as $oneMailTrackSales) {
                    $trackingSales[$oneMailTrackSales->mail_id.'-'.$oneMailTrackSales->user_id] = $oneMailTrackSales;
                }
            }

            $newUserClick = [];
            if (!empty($userClick)) {
                foreach ($userClick as $value) {
                    $newUserClick[$value->user_id.'-'.$value->mail_id] = $value;
                }
            }
            foreach ($mails as $key => $mail) {
                if (!empty($newUserClick[$mail->user_id.'-'.$mail->mail_id])) {
                    $mails[$key]->total_click = $newUserClick[$mail->user_id.'-'.$mail->mail_id]->total_click;
                }
                if (!empty($trackingSales[$mail->mail_id.'-'.$mail->user_id])) {
                    acym_trigger('getCurrency', [&$mails[$key]->currency]);
                    $mails[$key]->sales = $trackingSales[$mail->mail_id.'-'.$mail->user_id]->sale;
                }
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

        $query = "SELECT mail_id, SUM(fail) AS fail, SUM(sent) AS sent, SUM(open) AS open FROM #__acym_user_stat WHERE mail_id IN (".implode(',', $mailIds).") GROUP BY mail_id";

        return acym_loadObjectList($query, 'mail_id');
    }

    public function getUserWithNoMailOpen()
    {
        $query = 'SELECT user_id FROM #__acym_user_stat GROUP BY user_id HAVING MAX(open) = 0';

        return acym_loadResultArray($query);
    }
}
