<?php

namespace AcyMailing\Controllers\Stats;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\UrlClickClass;
use AcyMailing\Classes\UserStatClass;
use AcyMailing\Helpers\ExportHelper;
use AcyMailing\Helpers\MailerHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\WorkflowHelper;

trait GlobalStats
{
    public function globalStats()
    {
        acym_setVar('layout', 'global_stats');

        $data = [];

        $this->prepareDefaultPageInfo($data);

        $this->prepareOpenTimeChart($data);
        $this->preparecharts($data);
        $this->prepareListReceivers($data);
        $this->prepareDefaultRoundCharts($data);
        $this->prepareDefaultLineChart($data);
        $this->prepareDefaultDevicesChart($data);
        $this->prepareDefaultBrowsersChart($data);
        if (acym_isMultilingual() && count($this->selectedMailIds) == 1) $this->prepareMultilingualMails($data);
        $this->prepareMailFilter($data);

        parent::display($data);
    }

    private function prepareMailFilter(&$data)
    {
        $data['input_mail_ids'] = '';

        if (!empty($this->multiLanguageMailAdded) || !empty($this->generatedMailAdded)) {
            $this->selectedMailIds = array_filter(
                $this->selectedMailIds,
                function ($mailId) {
                    return !in_array($mailId, $this->multiLanguageMailAdded) && !in_array($mailId, $this->generatedMailAdded);
                }
            );
        }

        if (count($this->selectedMailIds) > 1) {
            $data['input_mail_ids'] = '<input type="hidden" value="'.implode(',', $this->selectedMailIds).'" name="mail_ids">';
        }

        $attributes = [
            'class' => 'acym__select acym_select2_ajax acym__stats__select__mails',
            'data-placeholder' => acym_translation('ACYM_ALL_EMAILS'),
            'data-ctrl' => 'stats',
            'data-task' => 'searchSentMail',
            'data-min' => '0',
            'id' => 'mail_ids',
        ];

        if (!empty($this->selectedMailIds)) $attributes['data-selected'] = implode(',', $this->selectedMailIds);

        $data['mail_filter'] = acym_selectMultiple(
            [],
            'mail_ids',
            [],
            $attributes
        );

        $data['emailTranslationsFilters'] = '';

        if (!empty($data['emailTranslations'])) {
            $data['emailTranslationsFilters'] = acym_select(
                $data['emailTranslations'],
                'mail_id_language',
                $this->selectedMailIds[0],
                [
                    'class' => 'acym__select acym__stats__select__language',
                ]
            );
        }
    }

    private function prepareClickStats(&$data)
    {
        if (empty($data['selectedMailid'])) return;

        $urlClickClass = new UrlClickClass();
        $allClickInfo = $urlClickClass->getAllLinkFromEmail($this->selectedMailIds[0]);

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

        $helperMailer = new MailerHelper();
        if (!empty($data['mailInformation'])) {
            $helperMailer->body = $data['mailInformation']->body;
            $helperMailer->statClick($data['mailInformation']->id, 0, true);
            $data['mailInformation']->body = $helperMailer->body;
        }


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

        $data['url_foundation_email'] = ACYM_CSS.'libraries/foundation_email.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'libraries'.DS.'foundation_email.min.css');
        $data['url_click_map_email'] = ACYM_CSS.'click_map.min.css?v='.filemtime(ACYM_MEDIA.'css'.DS.'click_map.min.css');
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
                'percentage' => 2,
                'text' => 'ACYM_BOUNCE_RATE',
            ],
            'unsub' => [
                'percentage' => 3,
                'text' => 'ACYM_UNSUBSCRIBE',
            ],
        ];

        $data['example_round_chart'] = '';
        foreach ($charts as $type => $oneChart) {
            if ($type == 'unsub' && empty($this->selectedMailIds)) continue;
            $data['example_round_chart'] .= '<div class="cell acym__stats__donut__one-chart">';
            $data['example_round_chart'] .= acym_roundChart(
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
        $data['example_line_chart'] = acym_lineChart('', $dataMonth, $dataDay, $dataHour);
    }

    public function prepareDefaultDevicesChart(&$data)
    {
        $allDevices = array_merge(UserStatClass::DESKTOP_DEVICES, UserStatClass::MOBILE_DEVICES);
        $defaultData = [];

        for ($i = 0 ; $i < 10 ; $i++) {
            $oneDevice = array_rand($allDevices);
            $defaultData[$allDevices[$oneDevice]] = rand(20, 10000);
        }

        $data['example_devices_chart'] = acym_pieChart('', $defaultData, '', acym_translation('ACYM_DEVICES'));
    }

    public function prepareDefaultBrowsersChart(&$data)
    {
        $exampleData = [
            'Google Chrome' => rand(20, 10000),
            'Firefox' => rand(20, 10000),
            'Safari' => rand(20, 10000),
            'Microsoft Edge' => rand(20, 10000),
            'Outlook' => rand(20, 10000),
            'Apple Mail' => rand(20, 10000),
            'Thunderbird' => rand(20, 10000),
        ];

        $data['example_source_chart'] = acym_pieChart('', $exampleData, '', acym_translation('ACYM_OPENED_WITH'));
    }

    public function setDataForChartLine()
    {
        $newStart = acym_date(acym_getVar('string', 'start'), 'Y-m-d H:i:s');
        $newEnd = acym_date(acym_getVar('string', 'end'), 'Y-m-d H:i:s');
        $mailIds = acym_getVar('int', 'id');

        if (empty($mailIds)) {
            $mailIds = [];
        }

        $mailClass = new MailClass();
        if (!empty($mailIds)) {
            $mailIds = $mailClass->getAutomaticMailIds($mailIds);
        }

        if (!empty($mailIds) && !is_array($mailIds)) {
            $mailIds = [$mailIds];
        }

        if ($newStart >= $newEnd) {
            echo 'error';
            exit;
        }

        $statsCampaignSelected = new \stdClass();
        $this->prepareLineChart($statsCampaignSelected, $mailIds, $newStart, $newEnd);

        echo @acym_lineChart('', $statsCampaignSelected->month, $statsCampaignSelected->day, $statsCampaignSelected->hour, true);
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

        $begin = new \DateTime(empty($campaignClicks) ? $campaignOpens[0]->open_date : min([$campaignOpens[0]->open_date, $campaignClicks[0]->date_click]));
        $end = new \DateTime(empty($campaignClicks) ? end($campaignOpens)->open_date : max([end($campaignOpens)->open_date, end($campaignClicks)->date_click]));

        $end->modify('+1 '.$modifier);

        $interval = new \DateInterval($intervalCode);
        $daterange = new \DatePeriod($begin, $interval, $end);

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

    public function prepareLineChart(&$statsCampaignSelected, $mailIdsOfCampaign, $newStart = '', $newEnd = '')
    {
        $campaignClass = new CampaignClass();
        $statsCampaignSelected->hasStats = true;

        //We get the opening by month, day, hour
        $campaignOpenByMonth = $campaignClass->getOpenByMonth($mailIdsOfCampaign, $newStart, $newEnd);
        $campaignOpenByDay = $campaignClass->getOpenByDay($mailIdsOfCampaign, $newStart, $newEnd);
        $campaignOpenByHour = $campaignClass->getOpenByHour($mailIdsOfCampaign, $newStart, $newEnd);

        if (empty($campaignOpenByMonth) || empty($campaignOpenByDay) || empty($campaignOpenByHour)) {
            $statsCampaignSelected->hasStats = false;

            return;
        }

        $urlClickClass = new UrlClickClass();
        $campaignClickByMonth = $urlClickClass->getAllClickByMailMonth($mailIdsOfCampaign, $newStart, $newEnd);
        $campaignClickByDay = $urlClickClass->getAllClickByMailDay($mailIdsOfCampaign, $newStart, $newEnd);
        $campaignClickByHour = $urlClickClass->getAllClickByMailHour($mailIdsOfCampaign, $newStart, $newEnd);

        $statsCampaignSelected->month = $this->getValues('day', 'P1M', $campaignOpenByMonth, $campaignClickByMonth, 'Y-m');
        $statsCampaignSelected->day = $this->getValues('hour', 'P1D', $campaignOpenByDay, $campaignClickByDay, 'Y-m-d');
        $statsCampaignSelected->hour = $this->getValues('min', 'PT1H', $campaignOpenByHour, $campaignClickByHour, 'Y-m-d H:i', true);

        $allHour = array_keys($statsCampaignSelected->hour);

        //those are the dates when the first was open and the last was open
        $statsCampaignSelected->startEndDateHour = [];
        $statsCampaignSelected->startEndDateHour['start'] = $allHour[0];
        $statsCampaignSelected->startEndDateHour['end'] = end($allHour);
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

    public function prepareOpenTimeChart(&$data)
    {
        $userStatClass = new UserStatClass();
        $statsDB = $userStatClass->getOpenTimeStats($this->selectedMailIds);

        if (empty($statsDB['total_open'])) {
            $data['openTime'] = $userStatClass->getDefaultStat();
            $data['empty_open'] = true;

            return true;
        }
        $data['empty_open'] = false;

        $stats = [];

        for ($day = 0 ; $day < 7 ; $day++) {
            $stats[$day] = [];
            for ($hour = 0 ; $hour < 8 ; $hour++) {
                if (empty($statsDB['stats'][$day.'_'.$hour]) || empty($statsDB['total_open'])) {
                    $percentage = 0;
                } else {
                    $percentage = ($statsDB['stats'][$day.'_'.$hour]->open_total * 100) / $statsDB['total_open'];
                }
                $stats[$day][$hour] = round($percentage);
            }
        }

        $data['openTime'] = $stats;

        return true;
    }

    public function prepareStatByList(&$data)
    {
        $mailid = (count(array_unique($data['selectedMailid'])) != 1) ? 0 : $data['selectedMailid'][0];

        if (empty($mailid)) {
            return;
        }

        $mailSelected = $this->getVarFiltersListing('int', 'mail_id_language', $mailid);

        $mailClass = new MailClass();
        $mail = $mailClass->getOneById($mailSelected);
        $mainMailId = empty($mail->parent_id) ? $mail->id : $mail->parent_id;

        $data['lists'] = [];
        $data['emailsSent'] = [];
        $data['emailsOpen'] = [];
        $data['bounces'] = [];
        $data['unsubscribed'] = [];
        $data['click'] = [];
        $data['listsStats'] = [];
        $data['userPerList'] = [];

        // get lists from the selected campaign
        $query = 'SELECT l.id, l.name, l.color FROM #__acym_list l';
        $query .= ' JOIN #__acym_mail_has_list ml on l.id = ml.list_id';
        $query .= ' WHERE ml.mail_id = '.$mainMailId;

        $data['lists'] = acym_loadObjectList($query, 'id');
        $listIds = implode(',', array_keys($data['lists']));

        if (!empty($listIds)) {
            //get nbSent, nbOpen, nbBounce, nbUnsubscribe
            $query = 'SELECT ul.list_id, SUM(us.sent) as nbSent, SUM(IF(us.open >= 1, 1, 0)) as nbOpen, SUM(us.bounce) as nbBounce, SUM(us.unsubscribe) as nbUnsub from #__acym_user_has_list ul';
            $query .= ' JOIN #__acym_user_stat us on ul.user_id = us.user_id';
            $query .= ' WHERE ul.list_id in ('.$listIds.') AND us.mail_id = '.$mailSelected;
            $query .= ' GROUP BY ul.list_id;';
            $data['listsStats'] = acym_loadObjectList($query, 'list_id');

            //get nbClick
            $query = 'SELECT ul.list_id, COUNT(uc.click) as nbClick';
            $query .= ' FROM #__acym_user_has_list ul ';
            $query .= ' JOIN #__acym_url_click uc on ul.user_id =  uc.user_id';
            $query .= ' WHERE ul.list_id in ('.$listIds.') AND uc.mail_id = '.$mailSelected;
            $query .= ' GROUP BY ul.list_id;';

            $data['nbClick'] = acym_loadObjectList($query, 'list_id');

            //get nbUser per list
            $query = 'SELECT ul.list_id, COUNT(ul.user_id) as nbUser';
            $query .= ' FROM #__acym_user_has_list ul';
            $query .= ' JOIN #__acym_user_stat us ON ul.user_id = us.user_id';
            $query .= ' WHERE ul.list_id in ('.$listIds.') AND us.mail_id = '.$mailSelected;
            $query .= ' GROUP BY ul.list_id;';

            $data['userPerList'] = acym_loadObjectList($query, 'list_id');
        }

        //format data for barChart
        foreach ($data['listsStats'] as $listId => $item) {
            $data['emailsSent'][$listId] = [
                'label' => $data['lists'][$listId]->name,
                'value' => $item->nbSent,
                'color' => $data['lists'][$listId]->color,
            ];
            $data['emailsOpen'][$listId] = [
                'label' => $data['lists'][$listId]->name,
                'value' => round(($item->nbOpen * 100) / $item->nbSent),
                'color' => $data['lists'][$listId]->color,
            ];
            $data['bounces'][$listId] = [
                'label' => $data['lists'][$listId]->name,
                'value' => round(($item->nbBounce * 100) / $item->nbSent),
                'color' => $data['lists'][$listId]->color,
            ];
            $data['unsubscribed'][$listId] = [
                'label' => $data['lists'][$listId]->name,
                'value' => round(($item->nbUnsub * 100) / $item->nbSent),
                'color' => $data['lists'][$listId]->color,
            ];
        }

        foreach ($data['userPerList'] as $listId => $item) {
            $nbUser = $item->nbUser;
            // we can't provide lists and their colors if there is no click,
            // so we initialize the values with $data['lists'] to display lists with no click rather than nothing
            $data['click'][$listId] = ['label' => $data['lists'][$listId]->name, 'value' => 0, 'color' => $data['lists'][$listId]->color];

            if ($nbUser > 0 && !empty($data['nbClick'][$listId]->nbClick)) {
                $data['click'][$listId]['value'] = round(($data['nbClick'][$listId]->nbClick * 100) / $nbUser);
            }
        }
    }

    private function prepareMultilingualMails(&$data)
    {
        if (empty($this->selectedMailIds)) return;

        $mailClass = new MailClass();

        $translatedEmails = [];

        if (empty($data['mailInformation']->parent_id)) {
            $translatedEmails = $mailClass->getTranslationsById($data['mailInformation']->id, true, true);
        } elseif (!empty($data['mailInformation']->parent_id)) {
            $parentEmail = $mailClass->getOneById($data['mailInformation']->parent_id);
            if (empty($parentEmail)) return;
            $translatedEmails = $mailClass->getTranslationsById($parentEmail->id, true, true);
        }

        $data['emailTranslations'] = [];
        $allLanguages = acym_getLanguages();

        foreach ($translatedEmails as $email) {
            if (!empty($email->language)) {
                $data['emailTranslations'][$email->id] = empty($allLanguages[$email->language]) ? $email->language
                    : $allLanguages[$email->language]->name;
            }
        }
    }

    private function prepareOpenSourcesStats(&$data)
    {
        $userStatClass = new UserStatClass();
        $openedFromStats = $userStatClass->getOpenSourcesStats($this->selectedMailIds);

        $formattedSources = [];
        foreach ($openedFromStats as $oneSource) {
            if (empty($oneSource->number)) continue;

            if (empty($oneSource->opened_with)) $oneSource->opened_with = acym_translation('ACYM_UNKNOWN');
            $formattedSources[$oneSource->opened_with] = $oneSource->number;
        }

        $data['openedWith'] = $formattedSources;
    }

    private function prepareDevicesStats(&$data)
    {
        $campaignClass = new CampaignClass();

        $devicesCampaign = $campaignClass->getDevicesWithCountByMailId($this->selectedMailIds);

        $formattedDevices = [];
        foreach ($devicesCampaign as $oneDevice) {
            if (empty($oneDevice->number)) continue;

            if (in_array($oneDevice->device, array_keys(UserStatClass::MOBILE_DEVICES))) {
                $deviceName = UserStatClass::MOBILE_DEVICES[$oneDevice->device];
            } elseif (in_array($oneDevice->device, array_keys(UserStatClass::DESKTOP_DEVICES))) {
                $deviceName = UserStatClass::DESKTOP_DEVICES[$oneDevice->device];
            } else {
                $deviceName = acym_translation('ACYM_UNKNOWN');
            }

            $formattedDevices[$deviceName] = $oneDevice->number;
        }

        $data['devices'] = $formattedDevices;
    }

    public function preparecharts(&$data)
    {
        $mailStatClass = new MailStatClass();

        $data['mail'] = $mailStatClass->getSentFailByMailIds($this->selectedMailIds);
        if (empty($data['mail'])) return;

        $campaignClass = new CampaignClass();
        $urlClickClass = new UrlClickClass();

        //For the total opening, the doughnut chart
        $data['mail']->totalMail = $data['mail']->sent + $data['mail']->fail;
        $data['mail']->pourcentageSent = empty($data['mail']->totalMail) ? 0 : number_format(($data['mail']->sent * 100) / $data['mail']->totalMail, 2);
        $data['mail']->allSent = empty($data['mail']->totalMail)
            ? acym_translationSprintf('ACYM_X_MAIL_SUCCESSFULLY_SENT_OF_X', 0, 0)
            : acym_translationSprintf(
                'ACYM_X_MAIL_SUCCESSFULLY_SENT_OF_X',
                $data['mail']->sent,
                $data['mail']->totalMail
            );

        //open rate
        $openRateCampaign = empty($this->selectedMailIds) ? $campaignClass->getOpenRateAllCampaign() : $campaignClass->getOpenRateCampaigns($this->selectedMailIds);
        $data['mail']->pourcentageOpen = empty($openRateCampaign->sent) ? 0 : number_format(($openRateCampaign->open_unique * 100) / $openRateCampaign->sent, 2);
        $data['mail']->allOpen = empty($openRateCampaign->sent)
            ? acym_translationSprintf('ACYM_X_MAIL_OPENED_OF_X', 0, 0)
            : acym_translationSprintf(
                'ACYM_X_MAIL_OPENED_OF_X',
                $openRateCampaign->open_unique,
                $openRateCampaign->sent
            );

        //click rate
        $clickRateCampaign = $urlClickClass->getNumberUsersClicked($this->selectedMailIds);
        $data['mail']->pourcentageClick = empty($data['mail']->sent) ? 0 : number_format(($clickRateCampaign * 100) / $data['mail']->sent, 2);
        $data['mail']->allClick = empty($data['mail']->sent)
            ? acym_translationSprintf('ACYM_X_MAIL_CLICKED_OF_X', 0, 0)
            : acym_translationSprintf(
                'ACYM_X_MAIL_CLICKED_OF_X',
                $clickRateCampaign,
                $data['mail']->sent
            );

        //bounce rate
        $bounceRateCampaign = empty($this->selectedMailIds) ? $campaignClass->getBounceRateAllCampaign() : $campaignClass->getBounceRateCampaigns($this->selectedMailIds);
        $data['mail']->pourcentageBounce = empty($data['mail']->sent) ? 0 : number_format(($bounceRateCampaign->bounce_unique * 100) / $data['mail']->sent, 2);
        $data['mail']->allBounce = empty($data['mail']->sent)
            ? acym_translationSprintf('ACYM_X_BOUNCE_OF_X', 0, 0)
            : acym_translationSprintf(
                'ACYM_X_BOUNCE_OF_X',
                $bounceRateCampaign->bounce_unique,
                $data['mail']->sent
            );

        if (!empty($this->selectedMailIds)) {
            //unsubscribe rate
            $mailStat = $mailStatClass->getByMailIds($this->selectedMailIds);
            $data['mail']->pourcentageUnsub = empty($data['mail']->sent) ? 0 : number_format(($mailStat->unsubscribe_total * 100) / $data['mail']->sent, 2);
            $data['mail']->allUnsub = empty($data['mail']->sent)
                ? acym_translationSprintf('ACYM_X_USERS_UNSUBSCRIBED_OF_X', 0, 0)
                : acym_translationSprintf(
                    'ACYM_X_USERS_UNSUBSCRIBED_OF_X',
                    $mailStat->unsubscribe_total,
                    $data['mail']->sent
                );
        }

        $this->prepareDevicesStats($data);
        $this->prepareOpenSourcesStats($data);
        $this->prepareLineChart($data['mail'], $this->selectedMailIds);
    }

    private function decode(&$detailedStats, $columnsToDecode = ['mail_subject', 'mail_name'])
    {
        foreach ($detailedStats as $oneDetailedStat) {
            foreach ($columnsToDecode as $column) {
                if (!empty($oneDetailedStat->$column)) {
                    $oneDetailedStat->$column = acym_utf8Decode($oneDetailedStat->$column);
                }
            }
        }
    }

    private function prepareLinksDetailsListing(&$data)
    {
        $data['search'] = $this->getVarFiltersListing('string', 'links_details_search', '');
        $data['ordering'] = $this->getVarFiltersListing('string', 'links_details_ordering', 'id');
        $data['orderingSortOrder'] = $this->getVarFiltersListing('string', 'links_details_ordering_sort_order', 'desc');

        if (empty($this->selectedMailIds)) return;

        $pagination = new PaginationHelper();
        $urlClickClass = new UrlClickClass();

        $detailedStatsPerPage = $pagination->getListLimit();
        $page = $this->getVarFiltersListing('int', 'links_details_pagination_page', 1);

        $urlClicks = $urlClickClass->getUrlsFromMailsWithDetails(
            [
                'ordering' => $data['ordering'],
                'search' => $data['search'],
                'detailedStatsPerPage' => $detailedStatsPerPage,
                'offset' => ($page - 1) * $detailedStatsPerPage,
                'ordering_sort_order' => $data['orderingSortOrder'],
                'mail_ids' => $this->selectedMailIds,
            ]
        );

        $this->decode($urlClicks['links_details']);

        // Prepare the pagination
        $pagination->setStatus($urlClicks['total'], $page, $detailedStatsPerPage);

        $data['pagination'] = $pagination;
        $data['links_details'] = $urlClicks['links_details'];
        $data['query'] = $urlClicks['query'];
    }

    private function prepareListReceivers(&$data)
    {
        if (empty($this->selectedMailIds)) return;

        $mailStatClass = new MailStatClass();
        $mailClass = new MailClass();

        $data['mailStat'] = $mailStatClass->getByMailIds($this->selectedMailIds);
        $data['lists'] = $mailClass->getAllListsByMailId($this->selectedMailIds);
    }

    private function exportGlobalFormatted()
    {
        $exportHelper = new ExportHelper();
        $data = [];
        $this->prepareDefaultPageInfo($data);
        $data['show_date_filters'] = true;
        $data['page_title'] = false;
        $timeLinechart = acym_getVar('string', 'time_linechart', 'month');


        $this->prepareMailFilter($data);
        $this->prepareClickStats($data);
        $this->preparecharts($data);
        $this->prepareDefaultRoundCharts($data);
        $this->prepareDefaultLineChart($data);
        $this->prepareDefaultDevicesChart($data);
        $this->prepareDefaultBrowsersChart($data);

        $globalDonut = [
            $data['mail']->pourcentageSent,
            $data['mail']->pourcentageOpen,
            $data['mail']->pourcentageClick,
            $data['mail']->pourcentageBounce,
            $data['mail']->pourcentageUnsub,
        ];
        $mailName = empty($this->selectedMailIds) ? acym_translation('ACYM_ALL_MAILS') : $data['mailInformation']->name;
        $globalLine = $data['mail']->$timeLinechart;

        $exportHelper->exportStatsFormattedCSV($mailName, $globalDonut, $globalLine, $timeLinechart);
        exit;
    }

    private function exportGlobalFull()
    {
        $exportHelper = new ExportHelper();
        $data = [];
        $this->prepareDefaultPageInfo($data);

        $where = '';
        if (!empty($this->selectedMailIds)) $where = 'WHERE mail_id IN ('.implode(',', $this->selectedMailIds).')';

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
}
