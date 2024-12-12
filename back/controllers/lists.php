<?php

namespace AcyMailing\Controllers;

use AcyMailing\Libraries\acymController;
use AcyMailing\Controllers\Lists\Listing;
use AcyMailing\Controllers\Lists\Edition;
use AcyMailing\Controllers\Lists\Ajax;

class ListsController extends acymController
{
    use Listing;
    use Edition;
    use Ajax;

    public const LIST_EDITION_TABS_GENERAL = 'general';
    public const LIST_EDITION_TABS_SUBSCRIBERS = 'subscribers';
    public const LIST_EDITION_TABS_UNSUBSCRIPTIONS = 'unsubscriptions';

    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_LISTS')] = acym_completeLink('lists');
        $this->loadScripts = [
            'settings' => [
                'colorpicker',
                'vue-applications' => [
                    'list_subscribers',
                    'entity_select',
                ],
            ],
        ];
    }
}
