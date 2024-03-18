<?php

namespace AcyMailing\FrontControllers;

use AcyMailing\Libraries\acymController;
use Joomla\CMS\Helper\ModuleHelper;

class ModuleloaderController extends acymController
{
    public function __construct()
    {
        parent::__construct();
        $this->setDefaultTask('loadAjax');

        $this->publicFrontTasks = [
            'loadAjax',
        ];
    }

    public function loadAjax()
    {
        if ($this->config->get('security_key') !== acym_getVar('string', 'seckey')) {
            acym_sendAjaxResponse(acym_translation('ACYM_UNAUTHORIZED_ACCESS'), [], false);
        }

        $moduleId = acym_getVar('int', 'moduleId');
        if (empty($moduleId)) {
            acym_sendAjaxResponse(acym_translation('ACYM_MODULE_NOT_FOUND'), [], false);
        }

        $module = acym_loadObject('SELECT * FROM #__modules WHERE id = '.intval($moduleId));
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
