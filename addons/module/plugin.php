<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'ModuleInsertion.php';

class plgAcymModule extends acymPlugin
{
    use ModuleInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';

        $this->pluginDescription->name = acym_translation('ACYM_MODULE');
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        $this->settings = [
            'front' => [
                'type' => 'select',
                'label' => 'ACYM_FRONT_ACCESS',
                'value' => 'hide',
                'data' => [
                    'all' => 'ACYM_ALL_ELEMENTS',
                    'hide' => 'ACYM_DONT_SHOW',
                ],
            ],
        ];
    }

    public function getPossibleIntegrations()
    {
        if (!acym_isAdmin() && $this->getParam('front', 'hide') !== 'all') return null;

        return $this->pluginDescription;
    }
}
