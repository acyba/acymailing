<?php

namespace AcyMailing\Classes;

use AcyMailing\Libraries\acymClass;

class UrlClickClass extends acymClass
{
    var $table = 'url_click';

    public function save($urlClick)
    {
        $column = [];
        $valueColumn = [];
        $columnName = acym_getColumns("url_click");

        if (!is_array($urlClick)) {
            $urlClick = (array)$urlClick;
        }

        foreach ($urlClick as $key => $value) {
            if (in_array($key, $columnName)) {
                $column[] = '`'.acym_secureDBColumn($key).'`';
                $valueColumn[] = acym_escapeDB($value);
            }
        }

        $query = "#__acym_url_click (".implode(',', $column).") VALUES (".implode(',', $valueColumn).")";

        $onDuplicate = [];

        if (!empty($urlClick['click'])) {
            $onDuplicate[] = "click = click + 1";
            $automationClass = new AutomationClass();
            $automationClass->trigger('user_click', ['userId' => $urlClick['user_id'], 'mailId' => $urlClick['mail_id']]);
        }

        if (!empty($onDuplicate)) {
            $query .= " ON DUPLICATE KEY UPDATE ";
            $query .= implode(',', $onDuplicate);
            $query = "INSERT INTO ".$query;
        } else {
            $query = "INSERT IGNORE INTO ".$query;
        }

        acym_query($query);
    }

    public function getNumberUsersClicked($mailIds = [])
    {
        if (!is_array($mailIds)) $mailIds = [$mailIds];

        acym_arrayToInteger($mailIds);

        $query = 'SELECT COUNT(DISTINCT user_id) FROM #__acym_url_click AS url_click';
        $isMultilingual = acym_isMultilingual();
        if ($isMultilingual && !empty($mailIds)) {
            $query .= ' LEFT JOIN #__acym_mail AS mail ON `mail`.`id` = `url_click`.`mail_id` WHERE `mail`.`id` IN ('.implode(
                    ',',
                    $mailIds
                ).') OR  `mail`.`parent_id` IN ('.implode(',', $mailIds).')';
        }
        if (!$isMultilingual && !empty($mailIds)) $query .= ' WHERE `url_click`.`mail_id` IN ('.implode(',', $mailIds).')';
        $clickNb = acym_loadResult($query);

        return empty($clickNb) ? 0 : $clickNb;
    }

    public function getAllClickByMailMonth($mailIds = [], $start = '', $end = '')
    {
        if (!is_array($mailIds)) $mailIds = [$mailIds];
        acym_arrayToInteger($mailIds);

        $query = 'SELECT COUNT(*) as click, DATE_FORMAT(date_click, \'%Y-%m\') as date_click FROM #__acym_url_click WHERE click > 0';
        $query .= empty($mailIds) ? '' : ' AND `mail_id` IN ('.implode(',', $mailIds).')';
        $query .= empty($start) ? '' : ' AND date_click >= '.acym_escapeDB($start);
        $query .= empty($end) ? '' : ' AND date_click <= '.acym_escapeDB($end);
        $query .= ' GROUP BY MONTH(date_click), YEAR(date_click) ORDER BY date_click';

        return acym_loadObjectList($query);
    }

    public function getAllClickByMailWeek($mailid = '', $start = '', $end = '')
    {
        $query = 'SELECT COUNT(*) as click, DATE_FORMAT(date_click, \'%Y-%m-%d\') as date_click FROM #__acym_url_click WHERE click > 0';
        $query .= empty($mailid) ? '' : ' AND `mail_id` = '.intval($mailid);
        $query .= empty($start) ? '' : ' AND date_click >= '.acym_escapeDB($start);
        $query .= empty($end) ? '' : ' AND date_click <= '.acym_escapeDB($end);
        $query .= ' GROUP BY WEEK(date_click), YEAR(date_click) ORDER BY date_click';

        return acym_loadObjectList($query);
    }

    public function getAllClickByMailDay($mailIds = [], $start = '', $end = '')
    {
        if (!is_array($mailIds)) $mailIds = [$mailIds];
        acym_arrayToInteger($mailIds);

        $query = 'SELECT COUNT(*) as click, DATE_FORMAT(date_click, \'%Y-%m-%d\') as date_click FROM #__acym_url_click WHERE click > 0';
        $query .= empty($mailIds) ? '' : ' AND `mail_id` IN ('.implode(',', $mailIds).')';
        $query .= empty($start) ? '' : ' AND date_click >= '.acym_escapeDB($start);
        $query .= empty($end) ? '' : ' AND date_click <= '.acym_escapeDB($end);
        $query .= ' GROUP BY DAYOFYEAR(date_click), YEAR(date_click) ORDER BY date_click';

        return acym_loadObjectList($query);
    }

    public function getAllClickByMailHour($mailIds = [], $start = '', $end = '')
    {
        if (!is_array($mailIds)) $mailIds = [$mailIds];
        acym_arrayToInteger($mailIds);

        $query = 'SELECT COUNT(*) as click, DATE_FORMAT(date_click, \'%Y-%m-%d %H:00:00\') as date_click FROM #__acym_url_click WHERE click > 0';
        $query .= empty($mailIds) ? '' : ' AND `mail_id` IN ('.implode(',', $mailIds).')';
        $query .= empty($start) ? '' : ' AND date_click >= '.acym_escapeDB($start);
        $query .= empty($end) ? '' : ' AND date_click <= '.acym_escapeDB($end);
        $query .= ' GROUP BY HOUR(date_click), DAYOFYEAR(date_click), YEAR(date_click) ORDER BY date_click';

        return acym_loadObjectList($query);
    }

    public function getAllLinkFromEmail($id)
    {
        $queryClickUrl = 'SELECT url.name, SUM(urlclick.click) as click FROM #__acym_url_click AS urlclick 
                          LEFT JOIN #__acym_url AS url ON urlclick.url_id = url.id 
                          WHERE `mail_id` = '.intval($id).' GROUP BY `url_id`';

        $queryCountAllClicks = 'SELECT SUM(click) FROM #__acym_url_click WHERE `mail_id` = '.intval($id);

        return [
            'urls_click' => acym_loadObjectList($queryClickUrl),
            'allClick' => acym_loadResult($queryCountAllClicks),
        ];
    }

    public function getClickRateByMailIds($mailsIds = [])
    {
        $conditionMailId = '';
        if (!empty($mailsIds)) {
            acym_arrayToInteger($mailsIds);
            $conditionMailId = 'WHERE mail_id IN ('.implode(',', $mailsIds).')';
        }
        $query = 'SELECT COUNT(groupStat.user_id) AS nbClick FROM (SELECT user_id FROM #__acym_url_click '.$conditionMailId.' GROUP BY mail_id, user_id) AS groupStat';

        return acym_loadResult($query);
    }

    public function getUrlsFromMailsWithDetails($params)
    {
        if (empty($params['mail_ids'])) return [];

        if (!is_array($params['mail_ids'])) $params['mail_ids'] = [$params['mail_ids']];
        acym_arrayToInteger($params['mail_ids']);

        $query = 'SELECT url.id, url.name, SUM(click) AS total_click, COUNT(DISTINCT user_id) AS unique_click, mail.subject AS mail_subject, mail.name AS mail_name
                  FROM #__acym_url AS url
                  JOIN #__acym_url_click AS url_click ON url.id = url_click.url_id AND url_click.mail_id IN ('.implode(',', $params['mail_ids']).')
                  JOIN #__acym_mail AS mail ON mail.id = url_click.mail_id';

        $queryCount = 'SELECT COUNT(DISTINCT url.id) FROM #__acym_url AS url
                  JOIN #__acym_url_click AS url_click ON url.id = url_click.url_id AND url_click.mail_id IN ('.implode(',', $params['mail_ids']).')
                  JOIN #__acym_mail AS mail ON mail.id = url_click.mail_id';

        if (!empty($params['search'])) {
            $searchTerms = acym_escapeDB('%'.$params['search'].'%');
            $where[] = 'url.name LIKE '.$searchTerms;
        }

        if (!empty($where)) {
            $query .= ' WHERE ('.implode(') AND (', $where).')';
            $queryCount .= ' WHERE ('.implode(') AND (', $where).')';
        }

        $query .= ' GROUP BY name';

        if (!empty($params['ordering']) && !empty($params['ordering_sort_order'])) {
            $query .= ' ORDER BY '.acym_secureDBColumn($params['ordering']).' '.acym_secureDBColumn(strtoupper($params['ordering_sort_order']));
        } else {
            $query .= ' ORDER BY id DESC';
        }

        $return = [];
        $return['links_details'] = acym_loadObjectList($query, '', $params['offset'], $params['detailedStatsPerPage']);
        $return['total'] = acym_loadResult($queryCount);
        $return['query'] = $query;

        return $return;
    }

    public function getUserUrlClicksStats($params)
    {
        if (empty($params['mail_ids'])) return [];

        if (!is_array($params['mail_ids'])) $params['mail_ids'] = [$params['mail_ids']];
        acym_arrayToInteger($params['mail_ids']);

        $query = 'SELECT url.id AS url_id,
                        user.id AS user_id,
                        user.email,
                        user.name AS user_name,
                        url.name AS url_name,
                        url_click.date_click,
                        url_click.click,
                        mail.subject AS mail_subject,
                        mail.name AS mail_name
                  FROM #__acym_user AS user 
                  JOIN #__acym_url_click AS url_click ON url_click.user_id = user.id AND url_click.mail_id IN ('.implode(',', $params['mail_ids']).')
                  JOIN #__acym_url AS url ON url.id = url_click.url_id
                  JOIN #__acym_mail AS mail ON mail.id = url_click.mail_id';

        $queryCount = 'SELECT COUNT(DISTINCT user.id) FROM #__acym_user AS user 
                  JOIN #__acym_url_click AS url_click ON url_click.user_id = user.id AND url_click.mail_id IN ('.implode(',', $params['mail_ids']).')
                  JOIN #__acym_url AS url ON url.id = url_click.url_id
                  JOIN #__acym_mail AS mail ON mail.id = url_click.mail_id';

        if (!empty($params['search'])) {
            $searchTerms = acym_escapeDB('%'.$params['search'].'%');
            $where[] = 'url.name LIKE '.$searchTerms.' or user.name LIKE '.$searchTerms.' or user.email LIKE '.$searchTerms;
        }

        if (!empty($where)) {
            $query .= ' WHERE('.implode(') and (', $where).')';
            $queryCount .= ' WHERE('.implode(') and (', $where).')';
        }

        if (!empty($params['ordering']) && !empty($params['ordering_sort_order'])) {
            $query .= ' ORDER BY '.acym_secureDBColumn($params['ordering']).' '.acym_secureDBColumn(strtoupper($params['ordering_sort_order']));
        } else {
            $query .= ' ORDER BY user_id DESC';
        }

        $return = [];
        $return['user_links_details'] = acym_loadObjectList($query, '', $params['offset'], $params['detailedStatsPerPage']);
        $return['total'] = acym_loadResult($queryCount);
        $return['query'] = $query;

        return $return;
    }
}
