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
            $automationClass->trigger('user_click', ['userId' => $urlClick['user_id']]);
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

    public function getNumberUsersClicked($mailid = '')
    {
        $query = 'SELECT COUNT(DISTINCT user_id) FROM #__acym_url_click AS url_click';
        $isMultilingual = acym_isMultilingual();
        if ($isMultilingual && !empty($mailid)) $query .= ' LEFT JOIN #__acym_mail AS mail ON `mail`.`id` = `url_click`.`mail_id` WHERE `mail`.`id` = '.intval($mailid).' OR  `mail`.`parent_id` = '.intval($mailid);
        if (!$isMultilingual && !empty($mailid)) $query .= ' WHERE `url_click`.`mail_id` = '.intval($mailid);
        $clickNb = acym_loadResult($query);

        return empty($clickNb) ? 0 : $clickNb;
    }

    public function getAllClickByMailMonth($mailid = '', $start = '', $end = '')
    {
        $query = 'SELECT COUNT(*) as click, DATE_FORMAT(date_click, \'%Y-%m\') as date_click FROM #__acym_url_click WHERE click > 0';
        $query .= empty($mailid) ? '' : ' AND `mail_id` = '.intval($mailid);
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

    public function getAllClickByMailDay($mailid = '', $start = '', $end = '')
    {
        $query = 'SELECT COUNT(*) as click, DATE_FORMAT(date_click, \'%Y-%m-%d\') as date_click FROM #__acym_url_click WHERE click > 0';
        $query .= empty($mailid) ? '' : ' AND `mail_id` = '.intval($mailid);
        $query .= empty($start) ? '' : ' AND date_click >= '.acym_escapeDB($start);
        $query .= empty($end) ? '' : ' AND date_click <= '.acym_escapeDB($end);
        $query .= ' GROUP BY DAYOFYEAR(date_click), YEAR(date_click) ORDER BY date_click';

        return acym_loadObjectList($query);
    }

    public function getAllClickByMailHour($mailid = '', $start = '', $end = '')
    {
        $query = 'SELECT COUNT(*) as click, DATE_FORMAT(date_click, \'%Y-%m-%d %H:00:00\') as date_click FROM #__acym_url_click WHERE click > 0';
        $query .= empty($mailid) ? '' : ' AND `mail_id` = '.intval($mailid);
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
}
