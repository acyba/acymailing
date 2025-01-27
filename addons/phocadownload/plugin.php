<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'PhocaInsertion.php';

class plgAcymPhocadownload extends AcymPlugin
{
    use PhocaInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_phocadownload');
        $this->addonDefinition = [
            'name' => 'Phoca Download',
            'description' => '- Insert file listings in your emails',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/phoca-download',
            'category' => 'Files management',
            'level' => 'essential',
        ];

        $this->pluginDescription->name = 'Phoca Download';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        $this->rootCategoryId = 0;

        if ($this->installed) {
            $this->initCustomView();

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
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') {
            return null;
        }

        return $this->pluginDescription;
    }
}
