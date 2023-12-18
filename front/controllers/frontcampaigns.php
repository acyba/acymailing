<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Classes\CampaignClass;
use AcyMailing\Controllers\CampaignsController;
use AcyMailing\Libraries\acymParameter;

class FrontcampaignsController extends CampaignsController
{
    public function __construct()
    {
        parent::__construct();

        if (ACYM_CMS == 'joomla') {
            $menu = acym_getMenu();
            if (is_object($menu)) {
                $params = method_exists($menu, 'getParams') ? $menu->getParams() : $menu->params;
                $menuParams = new acymParameter($params);
                $this->menuClass = $menuParams->get('pageclass_sfx', '');
            }
        }

        $this->loadScripts = [
            'edit_email' => [
                'vue-applications' => ['entity_select'],
                'editor-wysid',
            ],
            'recipients' => [
                'vue-applications' => ['entity_select'],
            ],
            'send_settings' => [
                'editor-wysid',
            ],
            'summary' => [
                'vue-applications' => ['modal_users_summary'],
            ],
        ];

        $this->menuAlias = [
            'index.php?option=com_acym&view=frontcampaigns&layout=listing' => 'index.php?option=com_acym&view=frontcampaigns&layout=campaigns',
        ];
        $this->allowedTasks = [
            'index.php?option=com_acym&view=frontcampaigns&layout=campaigns' => [
                'campaigns',
                'newEmail',
                'edit',
                'save',
                'saveAjax',
                'deleteAttachmentAjax',
                'countNumberOfRecipients',
                'saveAsDraftCampaign',
                'confirmCampaign',
                'stopScheduled',
                'duplicate',
                'delete',
                'welcome',
                'unsubscribe',
                'clearFilters',
                'addQueue',
                'listing',
            ],
        ];
    }

    protected function setFrontEndParamsForTemplateChoose()
    {
        return acym_currentUserId();
    }

    public function delete()
    {
        acym_checkToken();
        $ids = acym_getVar('array', 'elements_checked', []);

        $initialNumberOfCampaigns = count($ids);

        $campaignClass = new CampaignClass();
        $campaignClass->onlyManageableCampaigns($ids);

        if ($initialNumberOfCampaigns != count($ids)) {
            die('Access denied for campaign deletion');
        }

        parent::delete();
    }

    public function edit()
    {
        $nextstep = acym_getVar('string', 'nextstep', '');
        $step = acym_getVar('string', 'step', '');
        if (empty($nextstep)) {
            $nextstep = $step;
        }

        $allowedSteps = [
            'listing',
            'choosetemplate',
            'editemail',
            'recipients',
            'sendsettings',
            'summary',
        ];

        if (!in_array(strtolower($nextstep), $allowedSteps)) {
            die('Access denied for this campaign edition step');
        }

        return parent::edit();
    }
}
