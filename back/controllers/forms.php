<?php

namespace AcyMailing\Controllers;

use AcyMailing\Classes\FormClass;
use AcyMailing\Helpers\PaginationHelper;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Libraries\acymController;

class FormsController extends acymController
{
    public function __construct()
    {
        $this->loadScripts = [
            'edit' => ['vue-applications' => ['forms_edit'], 'colorpicker'],
        ];
        parent::__construct();
        $this->breadcrumb[acym_translation('ACYM_SUBSCRIPTION_FORMS')] = acym_completeLink('forms');
    }

    public function listing()
    {
        acym_setVar('layout', 'listing');
        $pagination = new PaginationHelper();
        $searchFilter = $this->getVarFiltersListing('string', 'forms_search', '');
        $status = $this->getVarFiltersListing('string', 'forms_status', '');
        $tagFilter = $this->getVarFiltersListing('string', 'forms_tag', '');
        $ordering = $this->getVarFiltersListing('string', 'forms_ordering', 'id');
        $orderingSortOrder = $this->getVarFiltersListing('string', 'forms_ordering_sort_order', 'asc');
        $formClass = new FormClass();

        // Get pagination data
        $formsPerPage = $pagination->getListLimit();
        $page = $this->getVarFiltersListing('int', 'forms_pagination_page', 1);

        $requestData = [
            'ordering' => $ordering,
            'search' => $searchFilter,
            'elementsPerPage' => $formsPerPage,
            'offset' => ($page - 1) * $formsPerPage,
            'tag' => $tagFilter,
            'ordering_sort_order' => $orderingSortOrder,
            'status' => $status,
        ];

        $matchingForms = $this->getMatchingElementsFromData($requestData, $status, $page);

        // Prepare the pagination
        $pagination->setStatus($matchingForms['total']->total, $page, $formsPerPage);

        $filters = [
            'all' => $matchingForms['total']->total,
            'active' => $matchingForms['total']->totalActive,
            'inactive' => $matchingForms['total']->total - $matchingForms['total']->totalActive,
        ];

        $data = [
            'allForms' => $matchingForms['elements'],
            'pagination' => $pagination,
            'search' => $searchFilter,
            'ordering' => $ordering,
            'tag' => $tagFilter,
            'status' => $status,
            'orderingSortOrder' => $orderingSortOrder,
            'formsNumberPerStatus' => $filters,
            'formTypes' => $formClass->getTranslatedTypes(),
            'formClass' => $formClass,
        ];

        $this->prepareToolbar($data);

        parent::display($data);
    }

    public function prepareToolbar(&$data)
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'forms_search', 'ACYM_SEARCH');
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE'), ['data-task' => 'newForm'], 'add', true);

        $data['toolbar'] = $toolbarHelper;
    }

    public function newForm()
    {
        acym_setVar('layout', 'new_form');

        if (ACYM_CMS === 'joomla') {
            $moduleId = acym_loadResult('SELECT extension_id FROM #__extensions WHERE element = "mod_acym"');

            if (empty($moduleId)) {
                $widgetUrl = 'index.php?option=com_modules&view=select';
            } else {
                $widgetUrl = 'index.php?option=com_modules&task=module.add&eid='.intval($moduleId);
            }
        } else {
            $widgetUrl = 'widgets.php';
        }

        $data = [
            'widget_link' => acym_route($widgetUrl),
            'popup_link' => acym_completeLink('forms&task=edit&type=popup'),
            'header_link' => acym_completeLink('forms&task=edit&type=header'),
            'footer_link' => acym_completeLink('forms&task=edit&type=footer'),
        ];

        if (ACYM_CMS === 'wordpress') {
            $data['shortcode_link'] = acym_completeLink('forms&task=edit&type=shortcode');
        }

        parent::display($data);
    }

    public function edit()
    {
        acym_setVar('layout', 'edit');
        $formClass = new FormClass();
        $id = acym_getVar('int', 'id', 0);
        $type = acym_getVar('string', 'type', ACYM_CMS === 'wordpress' ? $formClass::SUB_FORM_TYPE_SHORTCODE : $formClass::SUB_FORM_TYPE_POPUP);
        if (!acym_level(ACYM_ENTERPRISE) && in_array(
                $type,
                [$formClass::SUB_FORM_TYPE_POPUP, $formClass::SUB_FORM_TYPE_HEADER, $formClass::SUB_FORM_TYPE_FOOTER]
            )) {
            acym_enqueueMessage(acym_translation('ACYM_NOT_ALLOWED_CREATE_TYPE_FORM'), 'info');
            $this->listing();

            return;
        }

        if (empty($id)) {
            $form = $formClass->initEmptyForm($type);
            $this->breadcrumb[acym_translation('ACYM_NEW_FORM')] = acym_completeLink('forms&task=edit&id=0&type='.$type);
        } else {
            $form = $formClass->getOneById($id);
            $form = $formClass->getFormWithMissingParams($form);
            $this->breadcrumb[$form->name] = acym_completeLink('forms&task=edit&id='.$id);
        }

        if (empty($form)) {
            $this->listing();

            return;
        }

        $allPagesCMS = ['all' => acym_translation('ACYM_ALL_PAGES')];
        $allPagesCMS = array_replace($allPagesCMS, acym_getAllPages());


        $languagesName = ['all' => acym_translation('ACYM_ALL_LANGUAGE_NAME')];
        foreach (acym_getLanguages() as $languageCode => $languageArray) {
            $languagesName[$languageCode] = $languageArray->name;
        }

        $data = [
            'all_languages' => $languagesName,
            'form' => $form,
            'all_pages' => $allPagesCMS,
            'menu_render_settings' => $formClass->prepareMenuHtml($form, 'options'),
            'menu_render_style' => $formClass->prepareMenuHtml($form, 'styles'),
        ];

        parent::display($data);
    }

    public function updateFormPreview()
    {
        $formArray = acym_getVar('array', 'form');
        if (empty($formArray)) {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_GET_FORM_INFORMATION'), [], false);
        }

        $formClass = new FormClass();
        $form = $formClass->getFormWithMissingParams($formArray);
        if (empty($form)) {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_GET_FORM_INFORMATION'), [], false);
        }

        $data = [
            'html' => $formClass->renderForm($form, true),
        ];

        if (empty($data['html'])) {
            acym_sendAjaxResponse(acym_translation('ACYM_SOMETHING_WENT_WRONG_GENERATION_FORM'), [], false);
        }

        acym_sendAjaxResponse('', $data);
    }

    public function saveAjax()
    {
        acym_checkToken();
        $formArray = acym_getVar('array', 'form');
        if (empty($formArray)) {
            acym_sendAjaxResponse(acym_translation('ACYM_COULD_NOT_GET_FORM_INFORMATION'), [], false);
        }

        $formClass = new FormClass();
        $form = $formClass->getFormWithMissingParams($formArray);
        foreach ($form as $column => $value) {
            if (is_array($value)) {
                $form->$column = json_encode($value);
            } else {
                $form->$column = $value;
            }
        }

        $id = $formClass->save($form);
        if (empty($id)) {
            acym_sendAjaxResponse(acym_translation('ACYM_SOMETHING_WENT_WRONG_FORM_SAVING'), [], false);
        } else {
            acym_sendAjaxResponse(acym_translation('ACYM_FORM_WELL_SAVED'), ['id' => $id]);
        }
    }

    public function getArticles()
    {
        $searchedTerm = acym_getVar('string', 'searchedterm', '');

        echo json_encode(acym_getArticles($searchedTerm));
        exit;
    }

    public function getArticlesById()
    {
        $id = acym_getVar('int', 'article_id', 0);

        echo json_encode(acym_getArticleById($id));
        exit;
    }
}
