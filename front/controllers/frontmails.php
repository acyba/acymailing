<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Controllers\MailsController;
use AcyMailing\Libraries\acymParameter;

class FrontmailsController extends MailsController
{
    public function __construct()
    {
        if (ACYM_CMS == 'joomla') {
            $menu = acym_getMenu();
            if (is_object($menu)) {
                $params = method_exists($menu, 'getParams') ? $menu->getParams() : $menu->params;
                $menuParams = new acymParameter($params);
                $this->menuClass = $menuParams->get('pageclass_sfx', '');
            }
        }

        $this->authorizedFrontTasks = [
            'autoSave',
            'setNewIconShare',
            'edit',
            'setNewThumbnail',
            'getTemplateAjax',
            'apply',
            'saveAjax',
            'save',
            'sendTest',
            'getMailByIdAjax',
        ];
        $this->loadScripts = [
            'edit' => ['editor-wysid'],
        ];
        parent::__construct();
    }

    protected function setFrontEndParamsForTemplateChoose()
    {
        return acym_currentUserId();
    }

    public function setNewIconShare()
    {
        $menuFront = acym_loadObject('SELECT * FROM #__menu WHERE link LIKE "%index.php?option=com_acym&view=frontcampaigns%"');
        if (empty($menuFront)) return;

        parent::setNewIconShare();
    }
}
