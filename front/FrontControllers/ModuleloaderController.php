<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Core\AcymController;
use Joomla\CMS\Helper\ModuleHelper;

class ModuleloaderController extends AcymController
{
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultTask('loadAjax');

        $this->publicFrontTasks = [
            'loadAjax',
        ];
    }

    public function loadAjax(): void
    {
        $securityKey = $this->config->get('security_key');
        $providedKey = acym_getVar('string', 'seckey');
        if (empty($securityKey) || !hash_equals((string)$securityKey, (string)$providedKey)) {
            acym_sendAjaxResponse(acym_translation('ACYM_UNAUTHORIZED_ACCESS'), [], false);
        }

        $moduleId = acym_getVar('int', 'moduleId');
        if (empty($moduleId)) {
            acym_sendAjaxResponse(acym_translation('ACYM_MODULE_NOT_FOUND'), [], false);
        }

        $module = acym_loadObject(
            'SELECT * FROM #__modules
            WHERE id = '.intval($moduleId).'
                AND published = 1
                AND client_id = 0'
        );
        if (empty($module)) {
            acym_sendAjaxResponse(acym_translation('ACYM_MODULE_NOT_FOUND'), [], false);
        }

        $module->user = substr($module->module, 0, 4) == 'mod_' ? 0 : 1;
        $module->name = $module->user ? $module->title : substr($module->module, 4);
        $module->style = null;
        $module->module = preg_replace('/[^A-Z0-9_\.-]/i', '', $module->module);

        $params = [];

        $moduleOutput = ModuleHelper::renderModule($module, $params);
        acym_sendAjaxResponse('', ['output' => $moduleOutput]);
    }
}
