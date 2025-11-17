<?php

namespace AcyMailing\Helpers;

use AcyMailing\Classes\FieldClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\UrlClickClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\UserStatClass;
use AcyMailing\Core\AcymObject;

class UserHelper extends AcymObject
{
    public function getUserData(object $user): array
    {
        $userData = [];
        $this->prepareProfileData($user, $userData);
        $this->prepareSubscriptionData($user, $userData);
        $this->prepareStatisticsData($user, $userData);
        $this->prepareClickStatisticsData($user, $userData);

        return $userData;
    }

    private function prepareProfileData(object $user, array &$exportedData): void
    {
        $userClass = new UserClass();
        $userClass->getAllUserFields($user);

        $profileData = [];

        $fieldsClass = new FieldClass();
        $fields = $fieldsClass->getAll('namekey');

        $uploadFolder = acym_rootURI().trim(acym_cleanPath(html_entity_decode(acym_getFilesFolder(true))), DS.' ').DS;
        $coreFields = ['confirmation_date', 'confirmation_ip', 'language'];

        foreach ($user as $column => $value) {
            if (is_null($value) || strlen($value) === 0) {
                continue;
            }

            if ($column === 'confirmation_date') {
                if (empty($value)) {
                    continue;
                }
                $value = acym_getDate($value, '%Y-%m-%d %H:%M:%S');
            }

            if (empty($fields[$column]) || $fields[$column]->core == 1) {
                if (in_array($column, $coreFields)) {
                    $profileData[] = [
                        'name' => ucfirst(str_replace('_', ' ', $column)),
                        'value' => $value,
                    ];
                }
                continue;
            }

            if ($fields[$column]->type === 'date') {
                $decodedValue = @json_decode($value, true);
                $valueTmp = is_array($decodedValue) ? $decodedValue : $value;

                $value = is_array($valueTmp) ? implode('/', $valueTmp) : $valueTmp;
            }

            if (in_array($fields[$column]->type, ['gravatar', 'file'])) {
                $value = $uploadFolder.'userfiles'.DS.$value;
            }

            $profileData[] = [
                'name' => $fields[$column]->name,
                'value' => $value,
            ];
        }

        $exportedData[] = [
            'group_id' => 'acyprofile',
            'group_label' => 'Newsletter profile',
            'item_id' => 'user-'.$user->id,
            'data' => $profileData,
        ];
    }

    private function prepareSubscriptionData(object $user, array &$exportedData): void
    {
        $userClass = new UserClass();
        $subscription = $userClass->getUserSubscriptionById($user->id);

        if (empty($subscription)) {
            return;
        }

        $subscriptionData = [];

        foreach ($subscription as $oneSubscription) {
            $listName = empty($oneSubscription->display_name) ? $oneSubscription->name : $oneSubscription->display_name;

            $value = acym_translation('ACYM_SUBSCRIPTION_DATE').' '.acym_getDate($oneSubscription->subscription_date, '%Y-%m-%d %H:%M:%S');

            if (!empty($oneSubscription->unsubscribe_date) && $oneSubscription->status == 0) {
                $value .= ' / '.acym_translation('ACYM_UNSUBSCRIPTION_DATE').' '.acym_getDate($oneSubscription->unsubscribe_date, '%Y-%m-%d %H:%M:%S');
            }

            $subscriptionData[] = [
                'name' => $listName,
                'value' => $value,
            ];
        }

        $exportedData[] = [
            'group_id' => 'acysubscription',
            'group_label' => acym_translation('ACYM_LISTS'),
            'item_id' => 'subscription-'.$user->id,
            'data' => $subscriptionData,
        ];
    }

    private function prepareStatisticsData(object $user, array &$exportedData): void
    {
        $mailClass = new MailClass();
        $userStatClass = new UserStatClass();
        $statistics = $userStatClass->getAllUserStatByUserId($user->id);
        if (empty($statistics)) {
            return;
        }
        $statistics = $mailClass->decode($statistics);

        foreach ($statistics as $oneStatistics) {
            if (empty($oneStatistics->sent)) {
                continue;
            }

            $statisticsData = [];
            $statisticsData[] = [
                'name' => acym_translation('ACYM_EMAIL_SUBJECT'),
                'value' => strlen($oneStatistics->subject) > 50 ? substr($oneStatistics->subject, 0, 50).'...' : $oneStatistics->subject,
            ];

            $statisticsData[] = [
                'name' => acym_translation('ACYM_SEND_DATE'),
                'value' => acym_getDate($oneStatistics->send_date, '%Y-%m-%d %H:%M:%S'),
            ];

            $statisticsData[] = [
                'name' => acym_translation('ACYM_OPENED'),
                'value' => acym_translation(empty($oneStatistics->open) ? 'ACYM_NO' : 'ACYM_YES'),
            ];

            if (!empty($oneStatistics->open_date)) {
                $statisticsData[] = [
                    'name' => acym_translation('ACYM_OPEN_DATE'),
                    'value' => acym_getDate($oneStatistics->open_date, '%Y-%m-%d %H:%M:%S'),
                ];
            }

            if (!empty($oneStatistics->device)) {
                $statisticsData[] = [
                    'name' => acym_translation('ACYM_DEVICE_COLUMN_STAT'),
                    'value' => $oneStatistics->device,
                ];
            }

            if (!empty($oneStatistics->opened_with)) {
                $statisticsData[] = [
                    'name' => acym_translation('ACYM_OPENED_WITH_COLUMN_STAT'),
                    'value' => $oneStatistics->opened_with,
                ];
            }

            $exportedData[] = [
                'group_id' => 'acystatistics',
                'group_label' => acym_translation('ACYM_STATISTICS'),
                'item_id' => 'statistics-'.$user->id.'-'.$oneStatistics->mail_id,
                'data' => $statisticsData,
            ];
        }
    }

    private function prepareClickStatisticsData(object $user, array &$exportedData): void
    {
        $urlClickClass = new UrlClickClass();
        $statistics = $urlClickClass->getClicksByUserId($user->id);
        if (empty($statistics)) {
            return;
        }

        foreach ($statistics as $oneStatistics) {
            if (empty($oneStatistics->click)) {
                continue;
            }

            $statisticsData = [];
            $statisticsData[] = [
                'name' => acym_translation('ACYM_URL'),
                'value' => strlen($oneStatistics->url) > 200 ? substr($oneStatistics->url, 0, 197).'...' : $oneStatistics->url,
            ];

            $statisticsData[] = [
                'name' => acym_translation('ACYM_CLICK_DATE'),
                'value' => acym_getDate($oneStatistics->date_click, '%Y-%m-%d %H:%M:%S'),
            ];

            $statisticsData[] = [
                'name' => acym_translation('ACYM_TOTAL_CLICKS'),
                'value' => $oneStatistics->click,
            ];

            $exportedData[] = [
                'group_id' => 'acyclickstatistics',
                'group_label' => acym_translation('ACYM_USER_CLICK_DETAILS'),
                'item_id' => 'clickstatistics-'.$user->id.'-'.$oneStatistics->url_id,
                'data' => $statisticsData,
            ];
        }
    }
}
