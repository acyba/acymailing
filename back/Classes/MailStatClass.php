<?php

namespace AcyMailing\Classes;

use AcyMailing\Core\AcymClass;

class MailStatClass extends AcymClass
{
    public function __construct()
    {
        parent::__construct();

        $this->table = 'mail_stat';
        $this->pkey = 'mail_id';
    }

    public function save($element): ?int
    {
        $mailStat = is_object($element) ? get_object_vars($element) : $element;

        $column = [];
        $valueColumn = [];
        $columnName = acym_getColumns('mail_stat');

        foreach ($mailStat as $key => $value) {
            if (in_array($key, $columnName)) {
                $column[] = '`'.acym_secureDBColumn($key).'`';

                if ($key === 'tracking_sale') {
                    $valueColumn[] = is_null($value) || strlen($value) === 0 ? 'NULL' : floatval($value);
                } else {
                    $valueColumn[] = acym_escapeDB($value);
                }
            }
        }

        $query = '#__acym_mail_stat ('.implode(',', $column).') VALUES ('.implode(', ', $valueColumn).')';

        $onDuplicate = [];

        if (isset($mailStat['sent'])) {
            $onDuplicate[] = ' sent = sent + '.intval($mailStat['sent']);
        }

        if (isset($mailStat['fail'])) {
            $onDuplicate[] = ' fail = fail + '.intval($mailStat['fail']);
        }

        if (isset($mailStat['open_unique'])) {
            $onDuplicate[] = 'open_unique = open_unique + '.intval($mailStat['open_unique']);
        }

        if (isset($mailStat['open_total'])) {
            $onDuplicate[] = 'open_total = open_total + '.intval($mailStat['open_total']);
        }

        if (isset($mailStat['total_subscribers'])) {
            $onDuplicate[] = 'total_subscribers = total_subscribers + '.intval($mailStat['total_subscribers']);
        }

        if (isset($mailStat['unsubscribe_total'])) {
            $onDuplicate[] = ' unsubscribe_total = unsubscribe_total + '.intval($mailStat['unsubscribe_total']);
        }

        if (isset($mailStat['tracking_sale'])) {
            // Cannot be changed as it is not guaranteed that the plugin is updated at the same time
            $onDuplicate[] = 'tracking_sale = '.floatval($mailStat['tracking_sale']);
        }

        if (isset($mailStat['currency'])) {
            $onDuplicate[] = 'currency = '.acym_escapeDB($mailStat['currency']);
        }

        if (!empty($onDuplicate)) {
            $query .= ' ON DUPLICATE KEY UPDATE ';
            $query .= implode(',', $onDuplicate);
            $query = 'INSERT INTO '.$query;
        } else {
            $query = 'INSERT IGNORE INTO '.$query;
        }

        return (int)acym_query($query);
    }

    public function getTotalSubscribersByMailId(int $mailId): int
    {
        $result = acym_loadResult('SELECT total_subscribers FROM #__acym_mail_stat WHERE mail_id = '.intval($mailId));

        return empty($result) ? 0 : $result;
    }

    public function getTotalSubscribersByMailIdWithChild(int $mailId): int
    {
        $result = acym_loadResult(
            'SELECT SUM(total_subscribers) FROM #__acym_mail_stat WHERE mail_id IN (SELECT id FROM #__acym_mail WHERE id = '.intval($mailId).' OR parent_id = '.intval($mailId).')'
        );

        return empty($result) ? 0 : $result;
    }

    public function getOneByMailId(int $id): ?object
    {
        $query = 'SELECT SUM(sent) AS sent, SUM(fail) AS fail FROM #__acym_mail_stat';
        $query .= empty($id) ? '' : ' WHERE `mail_id` = '.intval($id);

        $stats = acym_loadObject($query);

        return empty($stats) ? null : $stats;
    }

    public function getSentFailByMailIds(array $mailIds = []): object
    {
        acym_arrayToInteger($mailIds);

        $query = 'SELECT SUM(sent) AS sent, SUM(fail) AS fail FROM #__acym_mail_stat';
        $query .= empty($mailIds) ? '' : ' WHERE `mail_id` IN ('.implode(',', $mailIds).')';

        return acym_loadObject($query);
    }

    public function getOneRowByMailId(int $mailId): ?object
    {
        $stats = acym_loadObject('SELECT * FROM #__acym_mail_stat WHERE mail_id = '.intval($mailId));

        return empty($stats) ? null : $stats;
    }

    public function getAllMailsForStats(string $search = ''): array
    {
        $mailClass = new MailClass();

        $query = 'SELECT mail.* 
                  FROM #__acym_mail AS mail 
                  JOIN #__acym_mail_stat AS mail_stat ON mail.id = mail_stat.mail_id';

        $queryAutoCampaign = 'SELECT mail.* FROM #__acym_mail AS mail 
                              JOIN #__acym_campaign as campaign ON campaign.mail_id = mail.id AND campaign.sending_type = '.acym_escapeDB(CampaignClass::SENDING_TYPE_AUTO);

        $querySearch = '';

        if (!empty($search)) {
            $querySearch .= ' mail.name LIKE '.acym_escapeDB('%'.acym_utf8Encode($search).'%').' ';
            $queryAutoCampaign .= ' WHERE '.$querySearch;

            $querySearch = ' AND '.$querySearch;
        }

        $query .= ' WHERE mail.parent_id IS NULL '.$querySearch;
        $query .= ' ORDER BY mail_stat.send_date DESC LIMIT 20';

        $mails = acym_loadObjectList($query);
        $mailsAuto = acym_loadObjectList($queryAutoCampaign);

        return $mailClass->decode(array_merge($mails, $mailsAuto));
    }

    public function getCumulatedStatsByMailIds(array $mailsIds = []): object
    {
        acym_arrayToInteger($mailsIds);
        $condMailIds = '';
        if (!empty($mailsIds)) {
            $condMailIds = 'WHERE mail_id IN ('.implode(',', $mailsIds).')';
        }

        $query = 'SELECT SUM(sent) AS sent, SUM(open_unique) AS open, SUM(open_total) AS open_total, SUM(click_unique) AS click_unique, SUM(click_total) AS click_total, SUM(fail) AS fails, SUM(bounce_unique) AS bounces FROM #__acym_mail_stat '.$condMailIds;

        return acym_loadObject($query);
    }

    public function getByMailIds(array $mailIds): ?object
    {
        acym_arrayToInteger($mailIds);

        if (empty($mailIds)) {
            return null;
        }

        return acym_loadObject('SELECT * FROM #__acym_mail_stat WHERE mail_id IN ('.implode(',', $mailIds).')');
    }

    public function migrateTrackingSale(): void
    {
        $query = 'SELECT tracking_sale, currency, mail_id FROM #__acym_user_stat WHERE currency IS NOT NULL';

        $trackingSales = acym_loadObjectList($query);

        if (empty($trackingSales)) return;

        $mailStats = [];

        foreach ($trackingSales as $sale) {
            if (empty($mailStats[$sale->mail_id])) {
                $mailStats[$sale->mail_id] = new \stdClass();
                $mailStats[$sale->mail_id]->mail_id = $sale->mail_id;
                $mailStats[$sale->mail_id]->tracking_sale = $sale->tracking_sale;
                $mailStats[$sale->mail_id]->currency = $sale->currency;
            } else {
                $mailStats[$sale->mail_id]->tracking_sale += $sale->tracking_sale;
            }
        }

        foreach ($mailStats as $mailStat) {
            $this->save($mailStat);
        }
    }

    public function getBestEmailByStats(array $mailIds, string $statsType): array
    {
        acym_arrayToInteger($mailIds);

        $mailStats = acym_loadObjectList(
            'SELECT ms.total_subscribers, ms.mail_id, ms.open_total, ms.click_total 
            FROM #__acym_mail_stat AS ms 
            WHERE ms.mail_id IN ('.implode(',', $mailIds).')',
            'mail_id'
        );

        foreach ($mailStats as $mailStat) {
            $mailStat->click_rate = $mailStat->open_total > 0 ? $mailStat->click_total / $mailStat->open_total : 0;
            $mailStat->open_rate = $mailStat->total_subscribers > 0 ? $mailStat->open_total / $mailStat->total_subscribers : 0;
        }

        if ($statsType === 'click_rate') {
            return ['click_rate' => $this->getBestMailByRate($mailStats, 'click_rate')];
        } elseif ($statsType === 'open_rate') {
            return ['open_rate' => $this->getBestMailByRate($mailStats, 'open_rate')];
        } else {
            return [
                'click_rate' => $this->getBestMailByRate($mailStats, 'click_rate'),
                'open_rate' => $this->getBestMailByRate($mailStats, 'open_rate'),
            ];
        }
    }

    private function getBestMailByRate(array $mailStats, string $type): int
    {
        uasort(
            $mailStats,
            function ($a, $b) use ($type) {
                if ($a->$type == $b->$type) {
                    return 0;
                }

                return ($a->$type < $b->$type) ? 1 : -1;
            }
        );

        return array_keys($mailStats)[0];
    }

    public function incrementClicks(int $mailId, bool $isFirst): void
    {
        acym_query(
            'UPDATE #__acym_mail_stat 
            SET click_total = click_total + 1'.($isFirst ? ', click_unique = click_unique + 1' : '').'
            WHERE mail_id = '.intval($mailId)
        );
    }

    public function getOneSentEmail(): bool
    {
        $firstSentEmail = acym_loadObject('SELECT * FROM #__acym_mail_stat WHERE sent > 0');

        return !empty($firstSentEmail);
    }

    public function incrementDetail(int $mailId, string $type, string $key): void
    {
        if (empty($mailId) || $type === '') {
            return;
        }

        acym_query(
            'INSERT INTO #__acym_mail_stat_detail (`mail_id`, `detail_type`, `detail_key`, `number`)
            VALUES ('.intval($mailId).', '.acym_escapeDB($type).', '.acym_escapeDB($key).', 1)
            ON DUPLICATE KEY UPDATE `number` = `number` + 1'
        );
    }

    public function getAnonDevices(array $mailIds = []): array
    {
        return $this->getAnonGroupedDetail($mailIds, 'device', 'device');
    }

    public function getAnonOpenSources(array $mailIds = []): array
    {
        return $this->getAnonGroupedDetail($mailIds, 'client', 'opened_with');
    }

    private function getAnonGroupedDetail(array $mailIds, string $type, string $alias): array
    {
        acym_arrayToInteger($mailIds);
        $condMailIds = empty($mailIds) ? '' : ' AND mail_id IN ('.implode(',', $mailIds).')';

        return acym_loadObjectList(
            'SELECT detail_key AS `'.acym_secureDBColumn($alias).'`, SUM(number) AS number
            FROM #__acym_mail_stat_detail
            WHERE detail_type = '.acym_escapeDB($type).$condMailIds.'
            GROUP BY detail_key'
        );
    }

    public function getAnonOpensByFormat(array $mailIds, string $format, string $start = '', string $end = ''): array
    {
        return $this->getAnonTimeSeries($mailIds, 'open_time', $format, 'open', 'open_date', $start, $end);
    }

    private function getAnonTimeSeries(array $mailIds, string $type, string $format, string $countAlias, string $dateAlias, string $start, string $end): array
    {
        acym_arrayToInteger($mailIds);
        $cond = empty($mailIds) ? '' : ' AND mail_id IN ('.implode(',', $mailIds).')';
        if (!empty($start)) {
            $cond .= ' AND detail_key >= '.acym_escapeDB($start);
        }
        if (!empty($end)) {
            $cond .= ' AND detail_key <= '.acym_escapeDB($end);
        }

        return acym_loadObjectList(
            'SELECT SUM(number) AS `'.acym_secureDBColumn($countAlias).'`, DATE_FORMAT(detail_key, '.acym_escapeDB($format).') AS `'.acym_secureDBColumn($dateAlias).'`
            FROM #__acym_mail_stat_detail
            WHERE detail_type = '.acym_escapeDB($type).$cond.'
            GROUP BY `'.acym_secureDBColumn($dateAlias).'`'
        );
    }

    public function getAnonOpenTimeStats(array $mailIds): array
    {
        acym_arrayToInteger($mailIds);
        $cond = empty($mailIds) ? '' : ' AND mail_id IN ('.implode(',', $mailIds).')';
        $offsetSeconds = (int)acym_getTimeOffsetCMS();

        return [
            'total_open' => acym_loadResult(
                'SELECT SUM(number) FROM #__acym_mail_stat_detail WHERE detail_type = '.acym_escapeDB('open_time').$cond
            ),
            'stats' => acym_loadObjectList(
                'SELECT
                    SUM(number) AS `open_total`,
                    DATE_FORMAT(DATE_ADD(detail_key, INTERVAL '.$offsetSeconds.' SECOND), "%w") AS `day`,
                    FORMAT(CONVERT(DATE_FORMAT(DATE_ADD(detail_key, INTERVAL '.$offsetSeconds.' SECOND), "%H"), SIGNED INTEGER) / 3, 0) AS `hour`,
                    CONCAT(
                        DATE_FORMAT(DATE_ADD(detail_key, INTERVAL '.$offsetSeconds.' SECOND), "%w"),
                        "_",
                        FORMAT(CONVERT(DATE_FORMAT(DATE_ADD(detail_key, INTERVAL '.$offsetSeconds.' SECOND), "%H"), SIGNED INTEGER) / 3, 0)
                    ) AS `date_id`
                FROM #__acym_mail_stat_detail
                WHERE detail_type = '.acym_escapeDB('open_time').$cond.'
                GROUP BY date_id',
                'date_id'
            ),
        ];
    }
}
