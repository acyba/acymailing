<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Classes\MailClass;
use AcyMailing\Controllers\MailsController;
use AcyMailing\Libraries\acymParameter;

class FrontmailsController extends MailsController
{
    public function __construct()
    {
        parent::__construct();

        if (ACYM_CMS === 'joomla') {
            $menu = acym_getMenu();
            if (is_object($menu)) {
                $params = method_exists($menu, 'getParams') ? $menu->getParams() : $menu->params;
                $menuParams = new acymParameter($params);
                $this->menuClass = $menuParams->get('pageclass_sfx', '');
            }
        }

        $this->loadScripts = [
            'edit' => ['editor-wysid'],
        ];

        $this->allowedTasks = [
            'index.php?option=com_acym&view=frontlists&layout=listing' => [
                'autoSave',
                'sendTest',
                'getTemplateAjax',
                'setNewIconShare',
                'delete',
                'saveAjax',
                'save',
                'getMailByIdAjax',
                'apply',
                'edit',
            ],
            'index.php?option=com_acym&view=frontcampaigns&layout=campaigns' => [
                'autoSave',
                'sendTest',
                'getTemplateAjax',
                'setNewIconShare',
                'delete',
                'edit',
                'saveAjax',
                'save',
                'apply',
            ],
        ];
    }

    protected function setFrontEndParamsForTemplateChoose()
    {
        return acym_currentUserId();
    }

    public function delete()
    {
        $ids = acym_getVar('array', 'elements_checked', []);

        $mailClass = new MailClass();
        foreach ($ids as $id) {
            if (!$mailClass->hasUserAccess($id)) {
                die('Access denied for mail deletion');
            }
        }

        parent::delete();
    }
}
