<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Controllers\MailsController;

class FrontmailsController extends MailsController
{
    public function __construct()
    {
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
