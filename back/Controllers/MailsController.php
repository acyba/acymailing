<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\MailClass;
use AcyMailing\Core\AcymController;
use AcyMailing\Controllers\Mails\Listing;
use AcyMailing\Controllers\Mails\Edition;
use AcyMailing\Controllers\Mails\Automation;

class MailsController extends AcymController
{
    use Listing;
    use Edition;
    use Automation;

    public function __construct()
    {
        parent::__construct();

        $type = acym_getVar('string', 'type', '');
        $this->setBreadcrumb($type);
        acym_header('X-XSS-Protection:0');
    }

    protected function setBreadcrumb(string $type): void
    {
        switch ($type) {
            case MailClass::TYPE_AUTOMATION:
                $breadcrumbTitle = 'ACYM_AUTOMATION';
                $breadcrumbUrl = acym_completeLink('automation');
                break;
            case MailClass::TYPE_FOLLOWUP:
                $breadcrumbTitle = 'ACYM_EMAILS';
                $breadcrumbUrl = acym_completeLink('mails');
                break;
            default:
                $breadcrumbTitle = 'ACYM_TEMPLATES';
                $breadcrumbUrl = acym_completeLink('mails');
        }

        $this->breadcrumb[acym_translation($breadcrumbTitle)] = $breadcrumbUrl;
    }
}
