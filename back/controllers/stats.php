<?php

class StatsController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_STATISTICS')] = acym_completeLink('stats');
        $this->loadScripts = [
            'all' => ['datepicker', 'thumbnail'],
        ];
    }

    public function saveSendingStatUser($userId, $mailId, $sendDate = null)
    {
        $userStatClass = acym_get('class.userstat');

        if ($sendDate == null) {
            $sendDate = acym_date();
        }

        $userStat = new stdClass();
        $userStat->mail_id = $mailId;
        $userStat->user_id = $userId;
        $userStat->send_date = $sendDate;

        $userStatClass->save($userStat);
    }

    public function listing()
    {
        acym_setVar('layout', 'listing');

        $data = [];
        $data['tab'] = acym_get('helper.tab');
        $data['selectedMailid'] = acym_getVar('int', 'mail_id', '');
        $mailStatClass = acym_get('class.mailstat');
        $data['sentMails'] = $mailStatClass->getAllMailsForStats();
        $data['show_date_filters'] = true;
        $data['page_title'] = false;

        $this->prepareDetailedListing($data);
        $this->prepareMailFilter($data);
        $this->prepareClickStats($data);
        $this->preparecharts($data);
        $this->prepareDefaultRoundCharts($data);
        $this->prepareDefaultLineChart($data);

        $data['url_foundation_email'] = ACYM_CSS.'libraries/foundation_email.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'libraries'.DS.'foundation_email.min.css');
        $data['url_click_map_email'] = ACYM_CSS.'click_map.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'click_map.min.css');

        parent::display($data);
    }

    private function prepareDetailedListing(&$data)
    {
        $userStatClass = acym_get('class.userstat');
        $pagination = acym_get('helper.pagination');

        $search = acym_getVar('string', 'detailed_stats_search', '');
        $ordering = acym_getVar('string', 'detailed_stats_ordering', 'send_date');
        $orderingSortOrder = acym_getVar('string', 'detailed_stats_ordering_sort_order', 'desc');

        $detailedStatsPerPage = $pagination->getListLimit();
        $page = acym_getVar('int', 'detailed_stats_pagination_page', 1);

        $matchingDetailedStats = $userStatClass->getDetailedStats(
            [
                'ordering' => $ordering,
                'search' => $search,
                'detailedStatsPerPage' => $detailedStatsPerPage,
                'offset' => ($page - 1) * $detailedStatsPerPage,
                'ordering_sort_order' => $orderingSortOrder,
                'mail_id' => $data['selectedMailid'],
            ]
        );

        // Prepare the pagination
        $pagination->setStatus($matchingDetailedStats['total'], $page, $detailedStatsPerPage);

        $data['search'] = $search;
        $data['ordering'] = $ordering;
        $data['orderingSortOrder'] = $orderingSortOrder;
        $data['pagination'] = $pagination;
        $data['detailed_stats'] = $matchingDetailedStats['detailed_stats'];
    }

    private function prepareMailFilter(&$data)
    {
        $data['mail_filter'] = acym_select(
            [],
            'mail_id',
            $data['selectedMailid'],
            'class="acym__select acym_select2_ajax" acym-data-default="'.acym_translation('ACYM_ALL_EMAILS', true).'" data-placeholder="'.acym_translation('ACYM_ALL_EMAILS', true).'" data-ctrl="stats" data-task="searchSentMail" data-min="0" data-selected="'.$data['selectedMailid'].'"'
        );
    }

    private function prepareClickStats(&$data)
    {
        if (empty($data['selectedMailid'])) return;

        $urlClickClass = acym_get('class.urlclick');
        $allClickInfo = $urlClickClass->getAllLinkFromEmail($data['selectedMailid']);

        $data['url_click'] = [];
        $data['url_click']['allClick'] = $allClickInfo['allClick'];

        $allPercentage = [];
        foreach ($allClickInfo['urls_click'] as $url) {
            $percentage = 0;
            if (empty($url->click)) {
                $data['url_click'][$url->name] = ['percentage' => $percentage, 'numberClick' => '0'];
            } else {
                $percentage = intval(($url->click * 100) / $allClickInfo['allClick']);
                $data['url_click'][$url->name] = ['percentage' => $percentage, 'numberClick' => $url->click];
            }
            $allPercentage[] = $percentage;
        }

        $mailClass = acym_get('class.mail');
        $data['mailInformation'] = $mailClass->getOneById($data['selectedMailid']);

        $helperMailer = acym_get('helper.mailer');
        $helperMailer->body = $data['mailInformation']->body;
        $helperMailer->statClick($data['mailInformation']->id, 0, true);
        $data['mailInformation']->body = $helperMailer->body;


        if (!empty($allPercentage)) {
            $maxPercentage = max($allPercentage);

            foreach ($data['url_click'] as $name => $val) {
                if ($name === 'allClick') continue;
                $percentageRecalc = intval(($val['percentage'] * 100) / $maxPercentage);
                if ($percentageRecalc <= 33) {
                    $data['url_click'][$name]['color'] = '0, 164, 255';
                } elseif ($percentageRecalc <= 66) {
                    $data['url_click'][$name]['color'] = '248, 31, 255';
                } else {
                    $data['url_click'][$name]['color'] = '255, 82, 89';
                }
            }
        }

        $data['url_click'] = json_encode($data['url_click']);
    }

    public function preparecharts(&$data)
    {
        $mailStatClass = acym_get('class.mailstat');

        $data['mail'] = $mailStatClass->getOneByMailId($data['selectedMailid']);
        if (empty($data['mail'])) return;

        $campaignClass = acym_get('class.campaign');
        $urlClickClass = acym_get('class.urlclick');

        //For the total opening, the doughnut chart
        $data['mail']->totalMail = $data['mail']->sent + $data['mail']->fail;
        $data['mail']->pourcentageSent = empty($data['mail']->totalMail) ? 0 : number_format(($data['mail']->sent * 100) / $data['mail']->totalMail, 2);
        $data['mail']->allSent = empty($data['mail']->totalMail) ? acym_translation_sprintf('ACYM_X_MAIL_SUCCESSFULLY_SENT_OF_X', 0, 0) : acym_translation_sprintf('ACYM_X_MAIL_SUCCESSFULLY_SENT_OF_X', $data['mail']->sent, $data['mail']->totalMail);

        //open rate
        $openRateCampaign = empty($data['selectedMailid']) ? $campaignClass->getOpenRateAllCampaign() : $campaignClass->getOpenRateOneCampaign($data['selectedMailid']);
        $data['mail']->pourcentageOpen = empty($openRateCampaign->sent) ? 0 : number_format(($openRateCampaign->open_unique * 100) / $openRateCampaign->sent, 2);
        $data['mail']->allOpen = empty($openRateCampaign->sent) ? acym_translation_sprintf('ACYM_X_MAIL_OPENED_OF_X', 0, 0) : acym_translation_sprintf('ACYM_X_MAIL_OPENED_OF_X', $openRateCampaign->open_unique, $openRateCampaign->sent);

        //click rate
        $clickRateCampaign = $urlClickClass->getNumberUsersClicked($data['selectedMailid']);
        $data['mail']->pourcentageClick = empty($data['mail']->sent) ? 0 : number_format(($clickRateCampaign * 100) / $data['mail']->sent, 2);
        $data['mail']->allClick = empty($data['mail']->sent) ? acym_translation_sprintf('ACYM_X_MAIL_CLICKED_OF_X', 0, 0) : acym_translation_sprintf('ACYM_X_MAIL_CLICKED_OF_X', $clickRateCampaign, $data['mail']->sent);

        //bounce rate
        $bounceRateCampaign = empty($data['selectedMailid']) ? $campaignClass->getBounceRateAllCampaign() : $campaignClass->getBounceRateOneCampaign($data['selectedMailid']);
        $data['mail']->pourcentageBounce = empty($data['mail']->sent) ? 0 : number_format(($bounceRateCampaign->bounce_unique * 100) / $data['mail']->sent, 2);
        $data['mail']->allBounce = empty($data['mail']->sent) ? acym_translation_sprintf('ACYM_X_BOUNCE_OF_X', 0, 0) : acym_translation_sprintf('ACYM_X_BOUNCE_OF_X', $bounceRateCampaign->bounce_unique, $data['mail']->sent);

        $this->prepareLineChart($data['mail'], $data['selectedMailid']);
    }

    public function prepareDefaultRoundCharts(&$data)
    {
        $charts = [
            'delivery' => [
                'percentage' => 95,
                'text' => 'ACYM_SUCCESSFULLY_SENT',
            ],
            'open' => [
                'percentage' => 25,
                'text' => 'ACYM_OPEN_RATE',
            ],
            'click' => [
                'percentage' => 10,
                'text' => 'ACYM_CLICK_RATE',
            ],
            'fail' => [
                'percentage' => 5,
                'text' => 'ACYM_FAIL',
            ],
        ];

        $data['example_round_chart'] = '';
        foreach ($charts as $type => $oneChart) {
            $data['example_round_chart'] .= '<div class="acym__stats__donut__one-chart medium-3 small-12">';
            $data['example_round_chart'] .= acym_round_chart(
                '',
                $oneChart['percentage'],
                $type,
                '',
                acym_translation($oneChart['text'])
            );
            $data['example_round_chart'] .= '</div>';
        }
    }

    public function prepareDefaultLineChart(&$data)
    {
        $dataMonth = [];
        $dataMonth['Jan 18'] = ['open' => '150', 'click' => '40'];
        $dataDay = [];
        $dataDay['23 Jan'] = ['open' => '150', 'click' => '40'];
        $dataHour = [];
        $dataHour['23 Jan 08:00'] = ['open' => '25', 'click' => '10'];
        $dataHour['23 Jan 09:00'] = ['open' => '50', 'click' => '10'];
        $dataHour['23 Jan 10:00'] = ['open' => '16', 'click' => '10'];
        $dataHour['23 Jan 11:00'] = ['open' => '59', 'click' => '10'];
        $data['example_line_chart'] = acym_line_chart('', $dataMonth, $dataDay, $dataHour);
    }

    public function setDataForChartLine()
    {
        $newStart = acym_date(acym_getVar('string', 'start'), 'Y-m-d H:i:s');
        $newEnd = acym_date(acym_getVar('string', 'end'), 'Y-m-d H:i:s');
        $mailIdOfCampaign = acym_getVar('int', 'id');

        if ($newStart >= $newEnd) {
            echo 'error';
            exit;
        }

        $statsCampaignSelected = new stdClass();

        $this->prepareLineChart($statsCampaignSelected, $mailIdOfCampaign, $newStart, $newEnd);

        echo @acym_line_chart('', $statsCampaignSelected->month, $statsCampaignSelected->day, $statsCampaignSelected->hour);
        exit;
    }

    private function getValues($modifier, $intervalCode, $campaignOpens, $campaignClicks, $dateCode, $hour = false)
    {
        $opens = [];
        foreach ($campaignOpens as $one) {
            $opens[acym_date(acym_getTime($one->open_date), $dateCode)] = $one->open;
        }

        $clicks = [];
        foreach ($campaignClicks as $one) {
            $clicks[acym_date(acym_getTime($one->date_click), $dateCode)] = $one->click;
        }

        $begin = new DateTime(empty($campaignClicks) ? $campaignOpens[0]->open_date : min([$campaignOpens[0]->open_date, $campaignClicks[0]->date_click]));
        $end = new DateTime(empty($campaignClicks) ? end($campaignOpens)->open_date : max([end($campaignOpens)->open_date, end($campaignClicks)->date_click]));

        $end->modify('+1 '.$modifier);

        $interval = new DateInterval($intervalCode);
        $daterange = new DatePeriod($begin, $interval, $end);

        $result = [];
        foreach ($daterange as $date) {
            $one = acym_date(acym_getTime($date->format('Y-m-d H:i:s')), $dateCode);

            $current = [];
            $current['open'] = empty($opens[$one]) ? 0 : $opens[$one];
            $current['click'] = empty($clicks[$one]) ? 0 : $clicks[$one];

            $key = $hour ? $one.':00' : $one;
            $result[$key] = $current;
        }

        return $result;
    }

    public function prepareLineChart(&$statsCampaignSelected, $mailIdOfCampaign, $newStart = '', $newEnd = '')
    {
        $campaignClass = acym_get('class.campaign');
        $statsCampaignSelected->hasStats = true;

        //We get the opening by month, day, hour
        $campaignOpenByMonth = $campaignClass->getOpenByMonth($mailIdOfCampaign, $newStart, $newEnd);
        $campaignOpenByDay = $campaignClass->getOpenByDay($mailIdOfCampaign, $newStart, $newEnd);
        $campaignOpenByHour = $campaignClass->getOpenByHour($mailIdOfCampaign, $newStart, $newEnd);

        if (empty($campaignOpenByMonth) || empty($campaignOpenByDay) || empty($campaignOpenByHour)) {
            $statsCampaignSelected->hasStats = false;

            return;
        }

        $urlClickClass = acym_get('class.urlclick');
        $campaignClickByMonth = $urlClickClass->getAllClickByMailMonth($mailIdOfCampaign, $newStart, $newEnd);
        $campaignClickByDay = $urlClickClass->getAllClickByMailDay($mailIdOfCampaign, $newStart, $newEnd);
        $campaignClickByHour = $urlClickClass->getAllClickByMailHour($mailIdOfCampaign, $newStart, $newEnd);

        $statsCampaignSelected->month = $this->getValues('day', 'P1M', $campaignOpenByMonth, $campaignClickByMonth, 'Y-m');
        $statsCampaignSelected->day = $this->getValues('hour', 'P1D', $campaignOpenByDay, $campaignClickByDay, 'Y-m-d');
        $statsCampaignSelected->hour = $this->getValues('min', 'PT1H', $campaignOpenByHour, $campaignClickByHour, 'Y-m-d H:i', true);

        $allHour = array_keys($statsCampaignSelected->hour);

        //those are the dates when the first was open and the last was open
        $statsCampaignSelected->startEndDateHour = [];
        $statsCampaignSelected->startEndDateHour['start'] = $allHour[0];
        $statsCampaignSelected->startEndDateHour['end'] = end($allHour);
    }

    public function searchSentMail()
    {
        $idSelected = acym_getVar('int', 'id', 0);
        if (!empty($idSelected)) {
            $mailClass = acym_get('class.mail');
            $mail = $mailClass->getOneById($idSelected);
            $name = empty($mail->name) ? '' : $mail->name;

            echo json_encode(
                [
                    'value' => $idSelected,
                    'text' => $name,
                ]
            );
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');

        $mailstatClass = acym_get('class.mailstat');
        $mails = $mailstatClass->getAllMailsForStats($search);

        foreach ($mails as $oneMail) {
            $return[] = [$oneMail->id, $oneMail->name];
        }

        echo json_encode($return);
        exit;
    }

    private function exportGlobalFormatted()
    {
        $exportHelper = acym_get('helper.export');
        $data['selectedMailid'] = acym_getVar('int', 'mail_id', '');
        $data['show_date_filters'] = true;
        $data['page_title'] = false;
        $timeLinechart = acym_getVar('string', 'time_linechart', 'month');

        //$this->prepareDetailedListing($data);
        $this->prepareMailFilter($data);
        $this->prepareClickStats($data);
        $this->preparecharts($data);
        $this->prepareDefaultRoundCharts($data);
        $this->prepareDefaultLineChart($data);

        $globalDonut = [$data['mail']->pourcentageSent, $data['mail']->pourcentageOpen, $data['mail']->pourcentageClick, $data['mail']->pourcentageBounce];
        $mailName = empty($data['selectedMailid']) ? acym_translation('ACYM_ALL_MAILS') : $data['mailInformation']->name;
        $globalLine = $data['mail']->$timeLinechart;

        $exportHelper->exportStatsFormattedCSV($mailName, $globalDonut, $globalLine, $timeLinechart);
        exit;
    }

    private function exportGlobalFull()
    {
        $exportHelper = acym_get('helper.export');
        $selectedMailid = acym_getVar('int', 'mail_id', '');

        $where = '';
        if (!empty($selectedMailid)) $where = 'WHERE mail_id = '.intval($selectedMailid);

        $columnsMailStat = acym_getColumns('mail_stat');
        $columnsToExport = [];

        $columnsToExport['mail.subject'] = acym_translation('ACYM_EMAIL_SUBJECT');
        foreach ($columnsMailStat as $column) {
            if (in_array($column, ['mail_id'])) continue;
            $trad = acym_translation('ACYM_'.strtoupper($column).'_COLUMN_STAT');
            if ($column == 'send_date') $trad = acym_translation('ACYM_SEND_DATE');
            $columnsToExport['mailstat.'.$column] = $trad;
        }

        $query = 'SELECT '.implode(', ', array_keys($columnsToExport)).' FROM #__acym_mail_stat AS mailstat LEFT JOIN #__acym_mail AS mail ON mail.id = mailstat.mail_id '.$where;
        $exportHelper->exportStatsFullCSV($query, $columnsToExport);
        exit;
    }

    public function exportDetailed()
    {
        $exportHelper = acym_get('helper.export');
        $selectedMailid = acym_getVar('int', 'mail_id', '');

        $where = '';
        if (!empty($selectedMailid)) $where = 'WHERE userstat.`mail_id` = '.intval($selectedMailid);

        $groupBy = ' GROUP BY userstat.mail_id, userstat.user_id ';

        $columnsMailStat = acym_getColumns('user_stat');
        $columnsToExport = [];

        $columnsToExport['mail.subject'] = acym_translation('ACYM_EMAIL_SUBJECT');
        $columnsToExport['user.email'] = acym_translation('ACYM_USER_EMAIL');
        foreach ($columnsMailStat as $column) {
            if (in_array($column, ['user_id', 'mail_id'])) continue;
            $trad = acym_translation('ACYM_'.strtoupper($column).'_COLUMN_STAT');
            if ($column == 'send_date') $trad = acym_translation('ACYM_SEND_DATE');
            if ($column == 'open') $trad = acym_translation('ACYM_OPEN_TOTAL_COLUMN_STAT');
            if ($column == 'bounce') $trad = acym_translation('ACYM_BOUNCE_UNIQUE_COLUMN_STAT');
            $columnsToExport['userstat.'.$column] = $trad;
        }

        $query = 'SELECT '.implode(', ', array_keys($columnsToExport)).', SUM(urlclick.click) as click FROM #__acym_user_stat AS userstat 
                  LEFT JOIN #__acym_user AS user ON user.id = userstat.user_id 
                  LEFT JOIN #__acym_mail AS mail ON mail.id = userstat.mail_id 
                  LEFT JOIN #__acym_url_click AS urlclick ON urlclick.user_id = userstat.user_id AND userstat.mail_id = urlclick.mail_id  '.$where.$groupBy;
        $columnsToExport['urlclick.click'] = acym_translation('ACYM_TOTAL_CLICK');
        $exportHelper->exportStatsFullCSV($query, $columnsToExport, 'detailed');
        exit;
    }

    public function exportGlobal()
    {
        $exportType = acym_getVar('string', 'export_type', 'charts');

        $functionName = 'exportGlobal'.ucfirst($exportType);

        if (!method_exists($this, $functionName)) {
            acym_enqueueMessage(acym_translation('ACYM_EXPORT_METHOD_NOT_FOUND'), 'error');
            $this->listing();

            return;
        }

        $this->$functionName();
    }
}
