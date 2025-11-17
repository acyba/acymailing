<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'SeblodInsertion.php';

class plgAcymSeblod extends AcymPlugin
{
    use SeblodInsertion;

    private $addedCss;
    private $itemId;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'Seblod',
            'description' => '- Insert Seblod articles in your emails',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/seblod',
            'category' => 'User management',
            'level' => 'starter',
        ];
        $this->installed = acym_isExtensionActive('com_cck');

        $this->pluginDescription->name = 'Seblod';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.svg';

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

    public function getPossibleIntegrations(): ?object
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') return null;

        return $this->pluginDescription;
    }
}
