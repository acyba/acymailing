<?php

namespace AcyMailing\Controllers\Mails;

use AcyMailing\Classes\MailClass;
use AcyMailing\Classes\TagClass;
use AcyMailing\Helpers\ExportHelper;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Helpers\UpdateHelper;

trait Listing
{
    public function listing()
    {
        acym_setVar('layout', 'listing');

        // Get filters data
        $searchFilter = $this->getVarFiltersListing('string', 'mails_search', '');
        $tagFilter = $this->getVarFiltersListing('string', 'mails_tag', '');
        $ordering = $this->getVarFiltersListing('string', 'mails_ordering', 'creation_date');
        $orderingSortOrder = $this->getVarFiltersListing('cmd', 'mails_ordering_sort_order', 'desc');

        $pagination = new PaginationHelper();
        // Get pagination data
        $mailsPerPage = $pagination->getListLimit();
        $page = $this->getVarFiltersListing('int', 'mails_pagination_page', 1);
        $mailClass = $this->currentClass;
        $status = $mailClass::TYPE_STANDARD;

        $requestData = [
            'ordering' => $ordering,
            'search' => $searchFilter,
            'elementsPerPage' => $mailsPerPage,
            'offset' => ($page - 1) * $mailsPerPage,
            'tag' => $tagFilter,
            'status' => $status,
            'ordering_sort_order' => $orderingSortOrder,
            'onlyStandard' => true,
        ];
        $matchingMails = $this->getMatchingElementsFromData($requestData, $status, $page);


        $matchingMailsNb = count($matchingMails['elements']);

        if (empty($matchingMailsNb) && $page > 1) {
            $this->setVarFiltersListing('mails_pagination_page', 1);
            $this->listing();

            return;
        }

        // Prepare the pagination
        $pagination->setStatus($matchingMails['total'], $page, $mailsPerPage);

        ob_start();
        require acym_getView('mails', 'listing_import', true);
        $templateImportView = ob_get_clean();

        $tagClass = new TagClass();
        $mailsData = [
            'allMails' => $matchingMails['elements'],
            'allTags' => $tagClass->getAllTagsByType('mail'),
            'pagination' => $pagination,
            'search' => $searchFilter,
            'tag' => $tagFilter,
            'ordering' => $ordering,
            'status' => $status,
            'mailNumberPerStatus' => $matchingMails['status'],
            'orderingSortOrder' => $orderingSortOrder,
            'templateImportView' => $templateImportView,
            'mailClass' => $mailClass,
        ];

        if (!empty($mailsData['tag'])) {
            $mailsData['status_toolbar'] = [
                'mails_tag' => $mailsData['tag'],
            ];
        }

        $this->prepareToolbar($mailsData);
        parent::display($mailsData);
    }

    public function prepareToolbar(&$data)
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'mails_search', 'ACYM_SEARCH');
        $toolbarHelper->addFilterByTag($data, 'mails_tag', 'acym__templates__filter__tags acym__select');
        $toolbarHelper->addButton(acym_translation('ACYM_ADD_DEFAULT_TMPL'), ['data-task' => 'installDefaultTmpl', 'id' => 'acym__mail__install-default'], 'content_copy');
        $otherContent = acym_modal(
            '<i class="acymicon-download"></i>'.acym_translation('ACYM_IMPORT'),
            $data['templateImportView'],
            'acym__template__import__reveal',
            '',
            'class="button button-secondary cell medium-6 large-shrink" data-reload="true" data-ajax="false"'
        );

        $otherContent .= acym_modal(
            '<i class="acymicon-add"></i>'.acym_translation('ACYM_CREATE'),
            '<div class="cell grid-x grid-margin-x">
                <button type="button" data-task="edit" data-editor="html" class="acym__create__template button cell large-auto small-6 margin-top-1 button-secondary">'.acym_translation(
                'ACYM_HTML_EDITOR'
            ).'</button>
                <button type="button" data-task="edit" data-editor="acyEditor" class="acym__create__template button cell medium-auto margin-top-1">'.acym_translation(
                'ACYM_DD_EDITOR'
            ).'</button>
            </div>',
            '',
            '',
            'class="acym_vcenter button cell medium-6 large-shrink"',
            true,
            false
        );
        $toolbarHelper->addOtherContent($otherContent);

        $data['toolbar'] = $toolbarHelper;
    }

    public function doUploadTemplate()
    {
        $mailClass = $this->currentClass;
        $mailClass->doupload();

        $this->listing();
    }

    public function export()
    {
        acym_checkToken();

        // Get passed data and check if we have everything we need
        $templateId = acym_getVar('int', 'templateId', 0);

        if (empty($templateId)) exit;

        $template = $this->currentClass->getOneById($templateId);

        // We have all we need for the export, prepare the headers for the download
        $exportHelper = new ExportHelper();
        $exportHelper->exportTemplate($template);

        exit;
    }

    public function installDefaultTmpl()
    {
        $updateHelper = new UpdateHelper();
        $updateHelper->installTemplates();

        $this->listing();
    }

    public function massDuplicate()
    {
        $ids = acym_getVar('array', 'elements_checked', []);
        if (!empty($ids)) $this->duplicate($ids);
        $this->listing();
    }

    public function oneDuplicate()
    {
        $templateId = acym_getVar('int', 'templateId', 0);

        if (empty($templateId)) {
            acym_enqueueMessage(acym_translation('ACYM_TEMPLATE_DUPLICATE_ERROR'), 'error');
            $this->listing();

            return;
        }

        $this->duplicate([$templateId]);
        $this->listing();
    }

    public function duplicate($templates = [])
    {
        $mailClass = $this->currentClass;
        $tmplError = [];
        foreach ($templates as $templateId) {
            $oldTemplate = $mailClass->getOneById($templateId);

            if (empty($oldTemplate)) {
                $tmplError[] = $templateId;
                continue;
            }

            $newTemplate = $oldTemplate;
            $newTemplate->id = 0;
            $newTemplate->name = $oldTemplate->name.'_copy';
            unset($newTemplate->thumbnail);

            $mailClass->save($newTemplate);
        }
        if (!empty($tmplError)) {
            acym_enqueueMessage(acym_translationSprintf('ACYM_TEMPLATE_X_DUPLICATE_ERROR', implode(', ', $tmplError)), 'error');
        }
    }

    public function delete()
    {
        parent::delete();

        $returnListing = acym_getVar('string', 'return_listing', '');
        if (!empty($returnListing)) {
            if (acym_isAdmin()) {
                acym_redirect(acym_completeLink($returnListing, false, true));
            } else {
                $itemId = acym_getVar('int', 'acym_itemid');
                $itemId = empty($itemId) ? '' : '&Itemid='.$itemId;
                $urlRedirect = acym_completeLink($returnListing.$itemId, false, true);
                acym_redirect($urlRedirect);
            }
        }
    }

    public function getMailByIdAjax()
    {
        acym_checkToken();
        $mailId = acym_getVar('int', 'id', 0);
        if (empty($mailId)) acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_FIND_MAIL'), [], false);

        $mailClass = new MailClass();
        $mail = $mailClass->getOneById($mailId);
        if (empty($mail)) acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_FIND_MAIL'), [], false);

        if (!$mailClass->hasUserAccess($mailId)) {
            die('Access denied for this mail');
        }

        acym_sendAjaxResponse('', $mail);
    }
}
