<?php

namespace AcyMailing\Controllers;

use AcyMailing\Libraries\acymController;
use AcyMailing\Controllers\Fields\Listing;
use AcyMailing\Controllers\Fields\Edition;

class FieldsController extends acymController
{
    use Listing;
    use Edition;

    public function __construct()
    {
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_CUSTOM_FIELDS')] = acym_completeLink('fields');
    }
}
