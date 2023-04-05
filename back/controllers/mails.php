<?php

namespace AcyMailing\Controllers;

use AcyMailing\Libraries\acymController;
use AcyMailing\Controllers\Mails\Listing;
use AcyMailing\Controllers\Mails\Edition;
use AcyMailing\Controllers\Mails\Automation;

class MailsController extends acymController
{
    use Listing;
    use Edition;
    use Automation;

    public function __construct()
    {
        parent::__construct();
        $type = acym_getVar('string', 'type');
        $this->setBreadcrumb($type);
        acym_header('X-XSS-Protection:0');
    }

    /**
     * Define the mails breadcrumb
     *
     * @param $type
     */
    protected function setBreadcrumb($type)
    {
        $mailClass = $this->currentClass;
        switch ($type) {
            case $mailClass::TYPE_AUTOMATION:
                $breadcrumbTitle = 'ACYM_AUTOMATION';
                $breadcrumbUrl = acym_completeLink('automation');
                break;
            case $mailClass::TYPE_FOLLOWUP:
                $breadcrumbTitle = 'ACYM_EMAILS';
                $breadcrumbUrl = acym_completeLink('mails');
                break;
            default:
                $breadcrumbTitle = 'ACYM_TEMPLATES';
                $breadcrumbUrl = acym_completeLink('mails');
        }

        $this->breadcrumb[acym_translation($breadcrumbTitle)] = $breadcrumbUrl;
    }


    // If needed to be deleted, delete the views
    //public function choose()
    //{
    //    acym_setVar('layout', 'choose');
    //
    //    $this->breadcrumb[acym_translation('ACYM_CREATE')] = '';
    //
    //    // Get filters data
    //    $searchFilter = acym_getVar('string', 'mailchoose_search', '');
    //    $tagFilter = acym_getVar('string', 'mailchoose_tag', 0);
    //    $ordering = acym_getVar('string', 'mailchoose_ordering', 'creation_date');
    //    $orderingSortOrder = acym_getVar('string', 'mailchoose_ordering_sort_order', 'DESC');
    //
    //    // Get pagination data
    //    $mailsPerPage = 12;
    //    $page = $this->getVarFiltersListing('int', 'mailchoose_pagination_page', 1);
    //
    //    $mailClass = $this->currentClass;
    //    $matchingMails = $mailClass->getMatchingElements(
    //        [
    //            'ordering' => $ordering,
    //            'ordering_sort_order' => $orderingSortOrder,
    //            'search' => $searchFilter,
    //            'elementsPerPage' => $mailsPerPage,
    //            'offset' => ($page - 1) * $mailsPerPage,
    //            'tag' => $tagFilter,
    //        ]
    //    );
    //
    //    // Prepare the pagination
    //    $pagination = new PaginationHelper();
    //    $pagination->setStatus($matchingMails['total'], $page, $mailsPerPage);
    //
    //    $tagClass = new TagClass();
    //    $mailsData = [
    //        'allMails' => $matchingMails['elements'],
    //        'allTags' => $tagClass->getAllTagsByType('mail'),
    //        'pagination' => $pagination,
    //        'search' => $searchFilter,
    //        'tag' => $tagFilter,
    //        'ordering' => $ordering,
    //        'type' => acym_getVar('string', 'type'),
    //    ];
    //
    //
    //    parent::display($mailsData);
    //}
}

