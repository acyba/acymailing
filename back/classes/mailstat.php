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

                if ($key === 'tracking_sale') {
                    $valueColumn[] = strlen($value) === 0 ? 'NULL' : floatval($value);
                } else {
                    $valueColumn[] = acym_escapeDB($value);
                }
            }
        }

        $query = '#__acym_mail_stat ('.implode(',', $column).') VALUES ('.implode(', ', $valueColumn).')';

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

        if (!empty($mailStat['tracking_sale'])) {
            $onDuplicate[] = 'tracking_sale = '.floatval($mailStat['tracking_sale']);
        }

        if (!empty($mailStat['currency'])) {
            $onDuplicate[] = 'currency = '.acym_escapeDB($mailStat['currency']);
        }

        if (!empty($onDuplicate)) {
            $query .= ' ON DUPLICATE KEY UPDATE ';
            $query .= implode(',', $onDuplicate);
            $query = 'INSERT INTO '.$query;
        } else {
            $query = 'INSERT IGNORE INTO '.$query;
        }

        return acym_query($query);
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

    public function getSentFailByMailIds($mailIds = [])
    {
        if (!is_array($mailIds)) $mailIds = [$mailIds];

        acym_arrayToInteger($mailIds);

        $query = 'SELECT SUM(sent) AS sent, SUM(fail) AS fail FROM #__acym_mail_stat';
        $query .= empty($mailIds) ? '' : ' WHERE `mail_id` IN ('.implode(',', $mailIds).')';

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
        $campaignClass = new CampaignClass();

        $query = 'SELECT mail.* 
                  FROM #__acym_mail AS mail 
                  JOIN #__acym_mail_stat AS mail_stat ON mail.id = mail_stat.mail_id';

        $queryAutoCampaign = 'SELECT mail.* FROM #__acym_mail AS mail 
                              JOIN #__acym_campaign as campaign ON campaign.mail_id = mail.id AND campaign.sending_type = '.acym_escapeDB($campaignClass::SENDING_TYPE_AUTO);

        $querySearch = '';

        if (!empty($search)) {
            $querySearch .= ' mail.name LIKE '.acym_escapeDB('%'.utf8_encode($search).'%').' ';
            $queryAutoCampaign .= ' WHERE '.$querySearch;

            $querySearch = ' AND '.$querySearch;
        }

        $query .= ' WHERE mail.parent_id IS NULL '.$querySearch;

        $query .= ' ORDER BY mail_stat.send_date DESC LIMIT 20';

        $mails = acym_loadObjectList($query);
        $mailsAuto = acym_loadObjectList($queryAutoCampaign);

        return $mailClass->decode(array_merge($mails, $mailsAuto));
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

    public function getByMailIds($mailIds)
    {
        if (!is_array($mailIds)) $mailIds = [$mailIds];

        acym_arrayToInteger($mailIds);

        return acym_loadObject('SELECT * FROM #__acym_mail_stat WHERE mail_id IN ('.implode(',', $mailIds).')');
    }

    public function migrateTrackingSale()
    {
        $query = 'SELECT tracking_sale, currency, mail_id FROM #__acym_user_stat WHERE currency IS NOT NULL';

        $trackingSales = acym_loadObjectList($query);

        if (empty($trackingSales)) return;

        $mailStats = [];

        foreach ($trackingSales as $sale) {
            if (empty($mailStats[$sale->mail_id])) {
                $mailStats[$sale->mail_id] = [
                    'mail_id' => $sale->mail_id,
                    'tracking_sale' => $sale->tracking_sale,
                    'currency' => $sale->currency,
                ];
            } else {
                $mailStats[$sale->mail_id]['tracking_sale'] += $sale->tracking_sale;
            }
        }

        foreach ($mailStats as $mailStat) {
            $this->save($mailStat);
        }
    }
}
