<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'DocmanInsertion.php';

class plgAcymDocman extends AcymPlugin
{
    use DocmanInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'DOCman',
            'description' => '- Insert documents in your emails',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/docman',
            'category' => 'Files management',
            'level' => 'essential',
        ];
        $this->installed = acym_isExtensionActive('com_docman');
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'DOCman';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'details' => ['ACYM_DETAILS', true],
                'image' => ['ACYM_IMAGE', false],
                'download' => ['ACYM_DOWNLOAD', false],
                'intro' => ['ACYM_INTRO_ONLY', true],
                'content' => ['ACYM_FULL_TEXT', false],
                'readmore' => ['ACYM_READ_MORE', false],
            ];

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
                'itemid' => [
                    'type' => 'text',
                    'label' => 'ACYM_MENU_ID',
                    'value' => '',
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
