<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Classes\ListClass;
use AcyMailing\Classes\MailClass;
use AcyMailing\Controllers\ListsController;
use AcyMailing\Helpers\ToolbarHelper;
use AcyMailing\Core\AcymParameter;


class FrontlistsController extends ListsController
{
    public function __construct()
    {
        parent::__construct();

        if (ACYM_CMS === 'joomla') {
            $menu = acym_getMenu();
            if (is_object($menu)) {
                $params = method_exists($menu, 'getParams') ? $menu->getParams() : $menu->params;
                $menuParams = new AcymParameter($params);
                $this->menuClass = $menuParams->get('pageclass_sfx', '');
            }
        }

        $this->allowedTasks = [
            'index.php?option=com_acym&view=frontlists&layout=listing' => [
                'setAjaxListing',
                'loadMoreSubscribers',
                'listing',
                'settings',
                'apply',
                'save',
                'delete',
                'saveSubscribers',
                'unsetWelcome',
                'unsetUnsubscribe',
                'setInactive',
                'setActive',
            ],
            'index.php?option=com_acym&view=frontusers&layout=listing' => [
                'ajaxCreateNewList',
            ],
            'index.php?option=com_acym&view=frontcampaigns&layout=campaigns' => [
                'setAjaxListing',
                'usersSummary',
            ],
        ];
    }

    protected function prepareListsListing(&$data)
    {
        // Prepare the pagination
        $listsPerPage = $data['pagination']->getListLimit();
        $page = $this->getVarFiltersListing('int', 'lists_pagination_page', 1);
        $idCurrentUser = acym_currentUserId();

        if (empty($idCurrentUser)) return;

        // Get the matching lists
        $matchingLists = $this->getMatchingElementsFromData(
            [
                'search' => $data['search'],
                'tag' => $data['tag'],
                'ordering' => $data['ordering'],
                'ordering_sort_order' => $data['orderingSortOrder'],
                'elementsPerPage' => $listsPerPage,
                'offset' => ($page - 1) * $listsPerPage,
                'status' => $data['status'],
                'creator_id' => acym_currentUserId(),
            ],
            $data['status'],
            $page
        );
        $data['pagination']->setStatus($matchingLists['total'], $page, $listsPerPage);

        $data['menuClass'] = $this->menuClass;
        $data['lists'] = $matchingLists['elements'];
        $data['listNumberPerStatus'] = $matchingLists['status'];
    }

    protected function prepareToolbar(&$data)
    {
        $toolbarHelper = new ToolbarHelper();
        $toolbarHelper->addSearchBar($data['search'], 'lists_search', 'ACYM_SEARCH');
        $toolbarHelper->addButton(acym_translation('ACYM_CREATE_NEW_LIST'), ['data-task' => 'settings'], '', true);

        $data['toolbar'] = $toolbarHelper;
    }

    protected function prepareWelcomeUnsubData(&$data)
    {
        $data['tmpls'] = [];
        $data['menuClass'] = $this->menuClass;
        if (empty($data['listInformation']->id)) return;

        $mailClass = new MailClass();

        foreach ([MailClass::TYPE_WELCOME => 'welcome', MailClass::TYPE_UNSUBSCRIBE => 'unsub'] as $full => $short) {
            $mailId = acym_getVar('int', $short.'mailid', 0);
            if (empty($data['listInformation']->{$full.'_id'}) && !empty($mailId)) {
                $data['listInformation']->{$full.'_id'} = $mailId;
                $listInfoSave = clone $data['listInformation'];
                unset($listInfoSave->subscribers);
                $listClass = new ListClass();
                if (!$listClass->save($listInfoSave)) {
                    acym_enqueueMessage(acym_translation('ACYM_ERROR_SAVE_LIST'), 'error');
                }
            }

            $returnLink = acym_completeLink('frontlists&task=settings&listId='.$data['listInformation']->id.'&edition=1&'.$short.'mailid={mailid}');
            if (empty($data['listInformation']->{$full.'_id'})) {
                $data['tmpls'][$short.'TmplUrl'] = acym_completeLink(
                    'frontmails&task=edit&step=editEmail&type='.$full.'&type_editor=acyEditor&list_id='.$data['listInformation']->id.'&return='.urlencode(
                        base64_encode($returnLink)
                    ).'&'.acym_getFormToken()
                );
            } else {
                $data['tmpls'][$short.'TmplUrl'] = acym_completeLink(
                    'frontmails&task=edit&id='.$data['listInformation']->{$full.'_id'}.'&type='.$full.'&return='.urlencode(base64_encode($returnLink)).'&'.acym_getFormToken()
                );
            }

            $data['tmpls'][$full] = !empty($data['listInformation']->{$full.'_id'}) ? $mailClass->getOneById($data['listInformation']->{$full.'_id'}) : '';
        }
    }

    public function delete()
    {
        acym_checkToken();
        $ids = acym_getVar('array', 'elements_checked', []);

        $initialNumberOfLists = count($ids);

        $listClass = new ListClass();
        $listClass->onlyManageableLists($ids);

        if ($initialNumberOfLists != count($ids)) {
            die('Access denied for list deletion');
        }

        parent::delete();
    }

    public function setInactive()
    {
        acym_checkToken();
        $ids = acym_getVar('array', 'elements_checked', []);

        $listClass = new ListClass();
        $listClass->onlyManageableLists($ids);

        if (!empty($ids)) {
            $listClass->setInactive($ids);
        }

        $this->listing();
    }

    public function setActive()
    {
        acym_checkToken();
        $ids = acym_getVar('array', 'elements_checked', []);

        $listClass = new ListClass();
        $listClass->onlyManageableLists($ids);

        if (!empty($ids)) {
            $listClass->setActive($ids);
        }

        $this->listing();
    }
}
