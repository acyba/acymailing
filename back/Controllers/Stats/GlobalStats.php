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
use AcyMailing\Libraries\Browser\BrowserDetection;
use AcyMailing\Classes\UserClass;

trait GlobalStats
{
    public function globalStats(): void
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
        if (count($this->selectedMailIds) == 1) {
            if ($data['isAbTest']) {
                $this->prepareAbTestMails($data);
            } elseif (acym_isMultilingual()) {
                $this->prepareMultilingualMails($data);
            }
        }
        $this->prepareMailFilter($data);

        parent::display($data);
    }

    private function prepareMailFilter(array &$data): void
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

        if (!empty($this->selectedMailIds)) {
            $attributes['data-selected'] = implode(',', $this->selectedMailIds);
        }

        $data['mail_filter'] = acym_selectMultiple(
            [],
            'mail_ids',
            [],
            $attributes
        );

        $data['emailVersionsFilters'] = '';

        if (!empty($data['emailVersions'])) {
            $data['emailVersionsFilters'] = acym_select(
                $data['emailVersions'],
                'mail_id_version',
                $this->selectedMailIds[0],
                [
                    'class' => 'acym__select acym__stats__select__language',
                ]
            );
        }
        if (!empty($data['emailTranslations'])) {
            $data['emailVersionsFilters'] = acym_select(
                $data['emailTranslations'],
                'mail_id_version',
                $this->selectedMailIds[0],
                [
                    'class' => 'acym__select acym__stats__select__language',
                ]
            );
        }
    }

    private function prepareClickStats(array &$data): void
    {
        if (empty($data['selectedMailid'])) {
            return;
        }

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
            $helperMailer->statClick(intval($data['mailInformation']->id), 0, true);
            $data['mailInformation']->body = preg_replace('#&(amp;)?autoSubId=[^"]+"#Uis', '"', $helperMailer->body);
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

    public function prepareDefaultRoundCharts(array &$data): void
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
                $oneChart['percentage'],
                $type,
                '',
                acym_translation($oneChart['text'])
            );
            $data['example_round_chart'] .= '</div>';
        }
    }

    public function prepareDefaultLineChart(array &$data): void
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
        $data['example_line_chart'] = acym_lineChart($dataMonth, $dataDay, $dataHour);
    }

    public function prepareDefaultDevicesChart(array &$data): void
    {
        $defaultData = [
            'ACYM_MOBILE' => 25662,
            'ACYM_DESKTOP' => 12471,
            'ACYM_OTHER' => 3548,
            'ACYM_UNKNOWN' => 6213,
        ];

        $data['example_devices_chart'] = acym_pieChart($defaultData, '', acym_translation('ACYM_DEVICES'));
    }

    public function prepareDefaultBrowsersChart(array &$data): void
    {
        $exampleData = [
            'Apple Mail' => 15835,
            'Google Chrome' => 13375,
            'Safari' => 2667,
            'Outlook' => 2458,
            'Edge' => 1190,
            'Firefox' => 826,
            'ACYM_OTHER' => 4775,
            'ACYM_UNKNOWN' => 6123,
        ];

        $data['example_source_chart'] = acym_pieChart($exampleData, '', acym_translation('ACYM_OPENED_WITH'));
    }

    public function setDataForChartLine(): void
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

        echo acym_lineChart($statsCampaignSelected->month, $statsCampaignSelected->day, $statsCampaignSelected->hour, true);
        exit;
    }

    private function getValues(string $modifier, string $intervalCode, array $campaignOpens, array $campaignClicks, string $dateCode, bool $hour = false): array
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
            $one = $date->format($dateCode);

            $current = [];
            $current['open'] = empty($opens[$one]) ? 0 : $opens[$one];
            $current['click'] = empty($clicks[$one]) ? 0 : $clicks[$one];

            $key = $hour ? $one.':00' : $one;
            $result[$key] = $current;
        }

        return $result;
    }

    public function prepareLineChart(object &$statsCampaignSelected, array $mailIdsOfCampaign, string $newStart = '', string $newEnd = ''): void
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

    public function exportGlobal(): void
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

    public function prepareOpenTimeChart(array &$data): void
    {
        $userStatClass = new UserStatClass();
        $statsDB = $userStatClass->getOpenTimeStats($this->selectedMailIds);

        if (empty($statsDB['total_open'])) {
            $data['openTime'] = $userStatClass->getDefaultStat();
            $data['empty_open'] = true;

            return;
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
    }

    public function prepareStatByList(array &$data): void
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
            $nbSent = empty($item->nbSent) ? 1 : $item->nbSent;

            $data['emailsSent'][$listId] = [
                'label' => $data['lists'][$listId]->name,
                'value' => $item->nbSent,
                'color' => $data['lists'][$listId]->color,
            ];
            $data['emailsOpen'][$listId] = [
                'label' => $data['lists'][$listId]->name,
                'value' => round(($item->nbOpen * 100) / $nbSent),
                'color' => $data['lists'][$listId]->color,
            ];
            $data['bounces'][$listId] = [
                'label' => $data['lists'][$listId]->name,
                'value' => round(($item->nbBounce * 100) / $nbSent),
                'color' => $data['lists'][$listId]->color,
            ];
            $data['unsubscribed'][$listId] = [
                'label' => $data['lists'][$listId]->name,
                'value' => round(($item->nbUnsub * 100) / $nbSent),
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

    private function prepareAbTestMails(array &$data): void
    {
        if (empty($this->selectedMailIds)) return;

        $mailClass = new MailClass();

        $mailVersions = [];

        if (empty($data['mailInformation']->parent_id)) {
            $mailVersions = $mailClass->getParentAndChildMails($data['mailInformation']->id);
        } elseif (!empty($data['mailInformation']->parent_id)) {
            $parentEmail = $mailClass->getOneById($data['mailInformation']->parent_id);
            if (empty($parentEmail)) return;
            $mailVersions = $mailClass->getParentAndChildMails($parentEmail->id);
        }

        $data['emailVersions'] = [];

        foreach ($mailVersions as $email) {
            $data['emailVersions'][$email->id] = $email->name.' - '.$email->subject;
        }
    }

    private function prepareMultilingualMails(array &$data): void
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

    private function prepareDevicesStats(array &$data): void
    {
        $campaignClass = new CampaignClass();
        $devicesCampaign = $campaignClass->getDevicesWithCountByMailId($this->selectedMailIds);

        $defaultDataDevices = [
            'ACYM_MOBILE' => 0,
            'ACYM_DESKTOP' => 0,
            'ACYM_OTHER' => 0,
            'ACYM_UNKNOWN' => 0,
        ];
        $data['devices'] = $defaultDataDevices;

        foreach ($devicesCampaign as $oneDevice) {
            if (empty($oneDevice->number)) {
                continue;
            }

            if (empty($oneDevice->device)) {
                $device = 'ACYM_UNKNOWN';
            } elseif (in_array($oneDevice->device, array_merge(BrowserDetection::PLATFORMS_MOBILE, BrowserDetection::LEGACY_PLATFORMS_MOBILE))) {
                $device = 'ACYM_MOBILE';
            } elseif (in_array($oneDevice->device, array_merge(BrowserDetection::PLATFORMS_DESKTOP, BrowserDetection::LEGACY_PLATFORMS_DESKTOP))) {
                $device = 'ACYM_DESKTOP';
            } else {
                $device = 'ACYM_OTHER';
            }

            $data['devices'][$device] += $oneDevice->number;
        }

        if ($data['devices'] === $defaultDataDevices) {
            $data['devices'] = [];
        }
    }

    private function prepareOpenSourcesStats(array &$data): void
    {
        $userStatClass = new UserStatClass();
        $openedFromStats = $userStatClass->getOpenSourcesStats($this->selectedMailIds);

        $data['openedWith'] = [];
        foreach ($openedFromStats as $oneSource) {
            if (empty($oneSource->number)) {
                continue;
            }

            if (!empty($oneSource->opened_with) && isset(BrowserDetection::LEGACY_OPENED_WITH_MAP[$oneSource->opened_with])) {
                $oneSource->opened_with = BrowserDetection::LEGACY_OPENED_WITH_MAP[$oneSource->opened_with];
            }

            if (empty($oneSource->opened_with)) {
                $oneSource->opened_with = 'ACYM_UNKNOWN';
            }

            $data['openedWith'][$oneSource->opened_with] = $oneSource->number;
        }
    }

    public function preparecharts(array &$data): void
    {
        $mailStatClass = new MailStatClass();

        $data['mail'] = $mailStatClass->getSentFailByMailIds($this->selectedMailIds);
        if (empty($data['mail'])) return;

        $campaignClass = new CampaignClass();
        $urlClickClass = new UrlClickClass();

        //For the total opening, the doughnut chart
        $data['mail']->totalMail = $data['mail']->sent + $data['mail']->fail;
        $data['mail']->percentageSent = empty($data['mail']->totalMail) ? 0 : number_format(($data['mail']->sent * 100) / $data['mail']->totalMail, 2);
        $data['mail']->allSent = empty($data['mail']->totalMail)
            ? acym_translationSprintf('ACYM_X_MAIL_SUCCESSFULLY_SENT_OF_X', 0, 0)
            : acym_translationSprintf(
                'ACYM_X_MAIL_SUCCESSFULLY_SENT_OF_X',
                $data['mail']->sent,
                $data['mail']->totalMail
            );

        //open rate
        $openRateCampaign = empty($this->selectedMailIds) ? $campaignClass->getOpenRateAllCampaign() : $campaignClass->getOpenRateCampaigns($this->selectedMailIds);
        $data['mail']->percentageOpen = empty($openRateCampaign->sent) ? 0 : number_format(($openRateCampaign->open_unique * 100) / $openRateCampaign->sent, 2);
        $data['mail']->allOpen = empty($openRateCampaign->sent)
            ? acym_translationSprintf('ACYM_X_MAIL_OPENED_OF_X', 0, 0)
            : acym_translationSprintf(
                'ACYM_X_MAIL_OPENED_OF_X',
                $openRateCampaign->open_unique,
                $openRateCampaign->sent
            );

        //click rate
        $clickRateCampaign = $urlClickClass->getNumberUsersClicked($this->selectedMailIds);
        $data['mail']->percentageClick = empty($data['mail']->sent) ? 0 : number_format(($clickRateCampaign * 100) / $data['mail']->sent, 2);
        $data['mail']->allClick = empty($data['mail']->sent)
            ? acym_translationSprintf('ACYM_X_MAIL_CLICKED_OF_X', 0, 0)
            : acym_translationSprintf(
                'ACYM_X_MAIL_CLICKED_OF_X',
                $clickRateCampaign,
                $data['mail']->sent
            );

        //bounce rate
        $bounceRateCampaign = empty($this->selectedMailIds) ? $campaignClass->getBounceRateAllCampaign() : $campaignClass->getBounceRateCampaigns($this->selectedMailIds);
        $data['mail']->percentageBounce = empty($data['mail']->sent) ? 0 : number_format(($bounceRateCampaign->bounce_unique * 100) / $data['mail']->sent, 2);
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
            $data['mail']->percentageUnsub = empty($data['mail']->sent) ? 0 : number_format(($mailStat->unsubscribe_total * 100) / $data['mail']->sent, 2);
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
        $this->prepareComparedStats($data);
    }

    private function decode(array &$detailedStats, array $columnsToDecode = ['mail_subject', 'mail_name']): void
    {
        foreach ($detailedStats as $oneDetailedStat) {
            foreach ($columnsToDecode as $column) {
                if (!empty($oneDetailedStat->$column)) {
                    $oneDetailedStat->$column = acym_utf8Decode($oneDetailedStat->$column);
                }
            }
        }
    }

    private function prepareLinksDetailsListing(array &$data): void
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
        $pagination->setStatus((int)$urlClicks['total']->total, $page, $detailedStatsPerPage);

        $data['pagination'] = $pagination;
        $data['links_details'] = $urlClicks['links_details'];
        $data['query'] = $urlClicks['query'];
    }

    private function prepareListReceivers(array &$data): void
    {
        if (empty($this->selectedMailIds)) return;

        $mailStatClass = new MailStatClass();
        $mailClass = new MailClass();

        $data['mailStat'] = $mailStatClass->getByMailIds($this->selectedMailIds);
        $data['lists'] = $mailClass->getAllListsByMailId($this->selectedMailIds);
    }

    private function exportGlobalFormatted(): void
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
            $data['mail']->percentageSent,
            $data['mail']->percentageOpen,
            $data['mail']->percentageClick,
            $data['mail']->percentageBounce,
            $data['mail']->percentageUnsub,
        ];
        $mailName = empty($this->selectedMailIds) ? acym_translation('ACYM_ALL_MAILS') : $data['mailInformation']->name;
        $globalLine = $data['mail']->$timeLinechart;

        $exportHelper->exportStatsFormattedCSV($mailName, $globalDonut, $globalLine, $timeLinechart);
        exit;
    }

    private function exportGlobalFull(): void
    {
        $exportHelper = new ExportHelper();
        $data = [];
        $this->prepareDefaultPageInfo($data);

        $where = '';
        if (!empty($this->selectedMailIds)) {
            $where = 'WHERE mail_id IN ('.implode(',', $this->selectedMailIds).')';
        }

        $columnsMailStat = acym_getColumns('mail_stat');
        $columnsToExport = [];

        $columnsToExport['mail.subject'] = acym_translation('ACYM_EMAIL_SUBJECT');
        foreach ($columnsMailStat as $column) {
            if ($column === 'mail_id') {
                continue;
            }

            $translation = acym_translation('ACYM_'.strtoupper($column).'_COLUMN_STAT');
            if ($column === 'send_date') {
                $translation = acym_translation('ACYM_SEND_DATE');
            } elseif ($column === 'click_total') {
                $translation = acym_translation('ACYM_TOTAL_CLICKS');
            } elseif ($column === 'click_unique') {
                $translation = acym_translation('ACYM_UNIQUE_CLICKS');
            }

            $columnsToExport['mailstat.'.$column] = $translation;
        }

        $query = 'SELECT '.implode(', ', array_keys($columnsToExport)).' FROM #__acym_mail_stat AS mailstat LEFT JOIN #__acym_mail AS mail ON mail.id = mailstat.mail_id '.$where;
        $exportHelper->exportStatsFullCSV($query, $columnsToExport);
        exit;
    }

    private function prepareComparedStats(array &$data): void
    {
        if (empty($data['totalSubscribers']) || empty($data['mail']->percentageOpen) || empty($data['mail']->percentageClick) || empty($data['mail']->percentageBounce)) {
            return;
        }

        $currentDate = acym_date('now', 'Y-m-d');

        $newStats = [
            'date' => $currentDate,
            'value' => [
                'totalSubscribers' => $data['totalSubscribers'],
                'openRate' => $data['mail']->percentageOpen,
                'clickRate' => $data['mail']->percentageClick,
                'bounceRate' => $data['mail']->percentageBounce,
                'totalNewSubscribers' => $data['totalSubscribers'],
            ],
        ];

        $keys = [
            'totalSubscribersHistory' => true,
            'percentageOpenHistory' => false,
            'percentageClickHistory' => false,
            'percentageBounceHistory' => false,
            'totalNewSubscribersHistory' => false,
        ];

        $oldEvolutionData = json_decode($this->config->get('statsEvolution', '{}'), true);

        foreach ($keys as $key => $isPercentage) {
            $history = json_decode($this->config->get($key, '[]'), true);

            if (!empty($history) && end($history)['date'] === $currentDate) {
                continue;
            }

            $history[] = ['date' => $currentDate, 'value' => $newStats['value'][$this->getStatKey($key)]];

            if (count($history) > 30) {
                array_shift($history);
            }

            $this->config->save([$key => json_encode($history)]);

            $evolutionKey = $this->getEvolutionKey($key);
            $oldEvolutionData[$evolutionKey] = $this->calculateEvolution($history, $isPercentage);
        }

        $this->config->save(['statsEvolution' => json_encode($oldEvolutionData)]);

        if (!empty($data)) {
            $this->updateDashboardData($data);
        }
    }

    private function getStatKey(string $key): ?string
    {
        return [
                   'totalSubscribersHistory' => 'totalSubscribers',
                   'percentageOpenHistory' => 'openRate',
                   'percentageClickHistory' => 'clickRate',
                   'percentageBounceHistory' => 'bounceRate',
                   'totalNewSubscribersHistory' => 'totalNewSubscribers',
               ][$key] ?? null;
    }

    private function getEvolutionKey(string $key): ?string
    {
        return [
                   'totalSubscribersHistory' => 'totalSubscribersEvolution',
                   'percentageOpenHistory' => 'openRateEvolution',
                   'percentageClickHistory' => 'clickRateEvolution',
                   'percentageBounceHistory' => 'bounceRateEvolution',
                   'totalNewSubscribersHistory' => 'totalNewSubscribersEvolution',
               ][$key] ?? null;
    }

    private function calculateEvolution(array $history, bool $isPercentage = false): ?float
    {
        if (empty($history)) {
            return null;
        }

        $oldValue = $history[0]['value'] ?? 0;
        $newValue = end($history)['value'] ?? 0;

        if ($oldValue === 0) {
            return null;
        }

        if ($isPercentage) {
            // For totalSubscribers: percentage variation
            return round((($newValue - $oldValue) / $oldValue) * 100, 2);
        } else {
            // For other stats: absolute difference
            return round($newValue - $oldValue, 2);
        }
    }

    private function updateDashboardData(array &$data): void
    {
        $evolutionData = json_decode($this->config->get('statsEvolution', '{}'), true);

        if (!empty($evolutionData)) {
            $data['totalSubscribersEvolution'] = $evolutionData['totalSubscribersEvolution'] ?? null;
            $data['openRateEvolution'] = $evolutionData['openRateEvolution'] ?? null;
            $data['clickRateEvolution'] = $evolutionData['clickRateEvolution'] ?? null;
            $data['bounceRateEvolution'] = $evolutionData['bounceRateEvolution'] ?? null;
            $data['totalNewSubscribersEvolution'] = $evolutionData['totalNewSubscribersEvolution'] ?? null;
        }
    }
}
