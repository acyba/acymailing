<?php

namespace AcyMailing\Controllers;

use AcyMailing\Core\AcymController;
use AcyMailing\Controllers\Forms\Listing;
use AcyMailing\Controllers\Forms\Edition;

class FormsController extends AcymController
{
    use Listing;
    use Edition;

    public function __construct()
    {
        $this->loadScripts = [
            'edit' => ['vue-applications' => ['forms_edit'], 'colorpicker'],
        ];
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_SUBSCRIPTION_FORMS')] = acym_completeLink('forms');
    }
}
