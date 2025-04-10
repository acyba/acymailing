<?php

namespace AcyMailing\Controllers\Dashboard;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Classes\MailStatClass;
use AcyMailing\Classes\QueueClass;
use AcyMailing\Controllers\StatsController;
use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\UserClass;

trait Listing
{
    public function listing(): void
    {
        acym_setVar('layout', 'listing');

        if ($this->migration() || $this->walkthrough()) {
            return;
        }

        $campaignClass = new CampaignClass();
        $mailStatClass = new MailStatClass();
        $statsController = new StatsController();
        $listsClass = new ListClass();
        $usersClass = new UserClass();

        $numberOfUsers = $usersClass->getCountTotalUsers();

        $data = [
            'page_title' => true,
            'mail_filter' => '',
            'stats_export' => '',
            'selectedMailid' => '',
            'show_date_filters' => false,
            'campaignsScheduled' => $campaignClass->getCampaignForDashboard(),
            'sentMails' => $mailStatClass->getAllMailsForStats(),
            'userCreated' => $numberOfUsers > 1,
            'listCreated' => $listsClass->getOneList(),
            'totalSubscribers' => $numberOfUsers,
            'campaignCreated' => $campaignClass->getOneCampaign(),
            'campaignSent' => $mailStatClass->getOneSentEmail(),
            'mailStatsCheckedOnce' => $this->config->get('mail_stats_checked_once', 0),
            'notifications' => json_decode($this->config->get('dashboard_notif', '[]'), true),
            'engage_community' => [
                [
                    'title' => 'ACYM_START_SENDING',
                    'text' => 'ACYM_WEBSITE_LINKED',
                    'link' => 'configuration',
                    'icon' => 'acymicon-cogs',
                ],
                [
                    'title' => 'ACYM_SUBSCRIPTION_FORM',
                    'text' => 'ACYM_RAISE_SUBSCRIBER',
                    'link' => 'forms',
                    'icon' => 'acymicon-edit',
                ],
                [
                    'title' => 'ACYM_SET_WELCOME_EMAIL',
                    'text' => 'ACYM_WELCOME_NEW_SUBSCRIBER',
                    'link' => 'campaigns&task=welcome',
                    'icon' => 'acymicon-mail-o',
                ],
                [
                    'title' => 'ACYM_OVERRIDE_WEBSITE',
                    'text' => 'ACYM_HOMOGENOUS_COMMUNICATION',
                    'link' => 'override',
                    'icon' => 'acymicon-edit',
                ],
                [
                    'title' => 'ACYM_ADD_MORE_USERS',
                    'text' => 'ACYM_IMPORT_USERS',
                    'link' => 'users&task=import',
                    'icon' => 'acymicon-user-plus',
                ],
                [
                    'title' => 'ACYM_TRIGGER_ACTION',
                    'text' => 'ACYM_EXTERNAL_SERVICES_ZAPIER',
                    'link' => '',
                    'link_doc' => ACYM_DOCUMENTATION.'addons/zapier',
                    'icon' => 'acymicon-arrows-h',
                ],
                [
                    'title' => 'ACYM_ADVENCED_SCENARIOS',
                    'text' => 'ACYM_CREATE_SCENARIOS_AND_ACTIONS',
                    'link' => 'scenarios',
                    'icon' => 'acymicon-movie',
                ],
                [
                    'title' => 'ACYM_BUILD_MEANINGFUL_PROFILES',
                    'text' => 'ACYM_CREATE_CUSTOM_FIELDS',
                    'link' => 'fields',
                    'icon' => 'acymicon-group',
                ],
                [
                    'title' => 'ACYM_TARGET_YOUR_AUDIENCE',
                    'text' => 'ACYM_CREATE_DIFFERENT_LISTS',
                    'link' => 'segments',
                    'icon' => 'acymicon-user-check',
                ],
                [
                    'title' => 'ACYM_CHOOSE_YOUR_COMMUNICATION',
                    'text' => 'ACYM_CHOOSE_AMONG_DIFFERENT_KIND_OF_EMAIL',
                    'link' => 'campaigns',
                    'icon' => 'acymicon-mobile',
                ],
                [
                    'title' => 'ACYM_SEND_RELATED_INFORMATION',
                    'text' => 'ACYM_INSERT_SPECIFIC_CONTENT',
                    'link' => 'plugins',
                    'icon' => 'acymicon-edit',
                ],
            ],
        ];

        $this->doDisplayBeginnerSteps($data);
        $this->getDashboardNotifications($data);
        $this->getCurrentCampaigns($data);
        $this->getLastTwoCampaigns($data);
        $this->getMainLists($data);

        $statsController->prepareOpenTimeChart($data);
        $statsController->preparecharts($data);
        $statsController->prepareDefaultRoundCharts($data);
        $statsController->prepareDefaultLineChart($data);
        $statsController->prepareDefaultDevicesChart($data);
        $statsController->prepareDefaultBrowsersChart($data);

        $this->prepareStatsView($data);
        $this->getUsersCount($data);
        $statsEvolution = json_decode($this->config->get('statsEvolution', '{}'));
        $data['newSubscribers'] = $statsEvolution->totalNewSubscribersEvolution ?? 0;
        parent::display($data);
    }

    private function doDisplayBeginnerSteps(array $data): void
    {
        if ($this->config->get('install_date', time()) < strtotime('-14 days')) {
            $this->config->save(['show_beginner_steps' => 0]);
        } elseif (!$data['listCreated'] || $data['totalSubscribers'] <= 1 || !$data['campaignCreated'] || !$data['campaignSent'] || $data['mailStatsCheckedOnce'] != 1) {
            $this->config->save(['show_beginner_steps' => 1]);
        } else {
            $this->config->save(['show_beginner_steps' => 0]);
        }
    }

    public function getDashboardNotifications(array &$data): void
    {
        $data['dashboardNotifications'] = '';

        if (empty($data['notifications'])) {
            return;
        }

        $data['dashboardNotifications'] = '<div class="acym__dashboard__notifications">';

        $notifications = array_map(fn($notif) => is_array($notif) ? (object)$notif : $notif, $data['notifications']);
        $notifications = array_filter($notifications, fn($notif) => isset($notif->message, $notif->level, $notif->date));

        foreach ($notifications as $notif) {
            if ($notif->level === 'info') {
                continue;
            }

            $fullMessageHover = $notif->message ?? '';

            $fullMessageHover = ($fullMessageHover !== $notif->message)
                ? 'data-acym-full="'.acym_escape($fullMessageHover).'"'
                : '';

            $logo = ($notif->level === 'warning')
                ? 'acymicon-exclamation-triangle'
                : 'acymicon-exclamation-circle';

            $date = acym_date($notif->date ?? 'now', 'Y-m-d H:i:s', false);

            $data['dashboardNotifications'] .= '<div class="cell grid-x acym__dashboard__notification acym__dashboard__notification__'.$notif->level.'">'
                .'<div class="cell shrink small-1 align-center grid-x acym__dashboard__notification__icon"><i class="cell shrink '.$logo.'"></i></div>'
                .'<div class="cell grid-x small-10"><p class="cell acym__dashboard__notification__message" '.$fullMessageHover.'>'.$notif->message
                .'<div class="cell acym__dashboard__notification__date">'.$date.'</div></div>';

            if (!isset($notif->removable) || $notif->removable != 0) {
                $data['dashboardNotifications'] .= '<i class="cell shrink small-1 acym__dashboard__notification__delete acymicon-close" data-id="'.acym_escape(
                        $notif->name
                    ).'"></i>';
            }

            $data['dashboardNotifications'] .= '</div><span class="acym__dashboard__light__separator separator"></span>';
        }

        $data['dashboardNotifications'] .= '</div>';
    }


    private function prepareStatsView(array &$data): void
    {
        $data['stats'] = [
            'ACYM_SUBSCRIBERS' => [
                'value' => $data['totalSubscribers'] ?? 0,
                'evolution' => $data['totalSubscribersEvolution'] ?? 0,
                'icon' => 'acymicon-group',
            ],
            'ACYM_OPEN_RATE' => [
                'value' => $data['mail']->percentageOpen ?? 0,
                'evolution' => $data['openRateEvolution'] ?? 0,
                'icon' => 'acymicon-mail-o-open',
            ],
            'ACYM_CLICK_RATE' => [
                'value' => $data['mail']->percentageClick ?? 0,
                'evolution' => $data['clickRateEvolution'] ?? 0,
                'icon' => 'acymicon-trigger',
            ],
            'ACYM_BOUNCE_RATE' => [
                'value' => $data['mail']->percentageBounce ?? 0,
                'evolution' => $data['bounceRateEvolution'] ?? 0,
                'icon' => 'acymicon-exclamation-triangle',
            ],
        ];
    }

    public function getCurrentCampaigns(array &$data): void
    {
        $queueClass = new QueueClass();
        $campaignsPerPage = 3;
        $activeCampaigns = $queueClass->getMatchingCampaigns(
            [
                'status' => 'sending',
                'campaignsPerPage' => $campaignsPerPage,
                'offset' => 0,
            ]
        );
        $pausedCampaigns = $queueClass->getMatchingCampaigns(
            [
                'status' => 'paused',
                'campaignsPerPage' => $campaignsPerPage,
                'offset' => 0,
            ]
        );
        $data['campaigns'] = array_merge($pausedCampaigns['elements'], $activeCampaigns['elements']);
    }

    public function getLastTwoCampaigns(array &$data): void
    {
        $campaignsPerPage = 2;
        $campaignClass = new CampaignClass();
        $lastTwoCampaigns = $campaignClass->getMatchingElements(
            [
                'element_tab' => 'campaigns',
                'ordering' => 'creation_date',
                'status' => 'sent',
                'ordering_sort_order' => 'DESC',
                'elementsPerPage' => $campaignsPerPage,
            ]
        );

        $data['recent_campaigns'] = $lastTwoCampaigns['elements'];
    }

    public function getMainLists(array &$data): void
    {
        $listClass = new ListClass();
        $listPerPage = 5;
        $mainLists = $listClass->getMatchingElements(
            [
                'ordering_sort_order' => 'ASC',
                'elementsPerPage' => $listPerPage,
                'offset' => 0,
                'status' => 'active',
            ],
        );

        $data['main_lists'] = $mainLists['elements'];
    }

    public function getUsersCount(array &$data): void
    {
        $userClass = new UserClass();
        $matchingUsers = $userClass->getMatchingElements(
            [
                'offset' => 0,
                'cms_username' => true,
            ],
        );

        $data['subscribers'] = $matchingUsers['status'];
    }
}
