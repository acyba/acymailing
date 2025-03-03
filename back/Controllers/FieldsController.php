<?php

namespace AcyMailing\Controllers;

use AcyMailing\Core\AcymController;
use AcyMailing\Controllers\Fields\Listing;
use AcyMailing\Controllers\Fields\Edition;

class FieldsController extends AcymController
{
    use Listing;
    use Edition;

    public function __construct()
    {
        parent::__construct();

        $this->breadcrumb[acym_translation('ACYM_CUSTOM_FIELDS')] = acym_completeLink('fields');
    }
}
