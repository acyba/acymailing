<?php

namespace AcyMailing\WpInit;

use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\UserHelper;

class Data
{
    public function __construct()
    {
        add_filter('wp_privacy_personal_data_exporters', [$this, 'registerDataExporters']);
    }

    public function registerDataExporters(array $exporters): array
    {
        $exporters['acymailing'] = [
            'exporter_friendly_name' => 'AcyMailing',
            'callback' => [$this, 'exportUserDataByEmail'],
        ];

        return $exporters;
    }

    public function exportUserDataByEmail(string $emailAddress, int $page = 1): array
    {
        $userClass = new UserClass();
        $user = $userClass->getOneByEmail($emailAddress);

        if (empty($user)) {
            return [
                'data' => [],
                'done' => true,
            ];
        }

        $userHelper = new UserHelper();

        return [
            'data' => $userHelper->getUserData($user),
            'done' => true,
        ];
    }
}
