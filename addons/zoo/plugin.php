<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'ZooInsertion.php';

class plgAcymZoo extends AcymPlugin
{
    use ZooInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'Zoo',
            'description' => '- Insert Zoo content in your emails<br>- Insert content by category',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/zoo',
            'category' => 'Content management',
            'level' => 'starter',
        ];
        $this->installed = acym_isExtensionActive('com_zoo');
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'Zoo';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.svg';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'teaser_desc' => ['TEASER DESCRIPTION', true],
                'desc' => ['ACYM_DESCRIPTION', false],
                'teaser_image' => ['TEASER IMAGE', true],
                'image' => ['ACYM_IMAGE', false],
                'extra' => ['ACYM_EXTRA_INFORMATION', false],
            ];

            $this->initCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->replaceOptions, $this->customOptions, $this->elementOptions),
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

    public function getPossibleIntegrations(): ?stdClass
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') return null;

        return $this->pluginDescription;
    }
}
