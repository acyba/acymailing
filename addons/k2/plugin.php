<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'K2Insertion.php';

class plgAcymK2 extends AcymPlugin
{
    use K2Insertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'K2 Content',
            'description' => '- Insert K2 items in your emails',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/k2-content',
            'category' => 'Content management',
            'level' => 'essential',
        ];
        $this->installed = acym_isExtensionActive('com_k2');

        $this->pluginDescription->name = 'K2';
        $this->rootCategoryId = 0;
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->initCustomView(true);

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->replaceOptions, $this->elementOptions),
                ],
                'front' => [
                    'type' => 'select',
                    'label' => 'ACYM_FRONT_ACCESS',
                    'value' => 'all',
                    'data' => [
                        'all' => 'ACYM_ALL_ELEMENTS',
                        'author' => 'ACYM_ONLY_AUTHORS_ELEMENTS',
                        'hide' => 'ACYM_DONT_SHOW',
                    ],
                ],
            ];
        } else {
            $this->settings = [
                'not_installed' => '1',
            ];
        }
    }

    public function getPossibleIntegrations()
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') return null;

        return $this->pluginDescription;
    }
}
