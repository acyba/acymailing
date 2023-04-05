<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Controllers\CampaignsController;
use AcyMailing\Libraries\acymParameter;

class FrontcampaignsController extends CampaignsController
{
    public function __construct()
    {
        if (!acym_level(ACYM_ENTERPRISE)) {
            acym_redirect(acym_rootURI(), 'ACYM_ONLY_AVAILABLE_ENTERPRISE_VERSION', 'warning');
        }
        if (ACYM_CMS == 'joomla') {
            $menu = acym_getMenu();
            if (is_object($menu)) {
                $params = method_exists($menu, 'getParams') ? $menu->getParams() : $menu->params;
                $menuParams = new acymParameter($params);
                $this->menuClass = $menuParams->get('pageclass_sfx', '');
            }
        }

        $this->loadScripts = [
            'edit' => ['vue-applications' => ['entity_select'], 'editor-wysid'],
        ];
        $this->authorizedFrontTasks = [
            'campaigns',
            'saveAsDraftCampaign',
            'addQueue',
            'save',
            'edit',
            'newEmail',
            'welcome',
            'unsubscribe',
            'countNumberOfRecipients',
            'editEmail',
            'saveAjax',
            'confirmCampaign',
            'stopScheduled',
            'duplicate',
            'delete',
            'deleteAttachmentAjax',
        ];
        $this->urlsFrontMenu = ['index.php?option=com_acym&view=frontcampaigns&layout=campaigns', 'index.php?option=com_acym&view=frontcampaigns&layout=listing'];
        parent::__construct();
    }

    protected function setFrontEndParamsForTemplateChoose()
    {
        return acym_currentUserId();
    }
}
