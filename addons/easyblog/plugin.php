<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'EasyblogInsertion.php';

class plgAcymEasyblog extends AcymPlugin
{
    use EasyblogInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'EasyBlog',
            'description' => '- Insert EasyBlog content in your emails',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/easyblog',
            'category' => 'Content management',
            'level' => 'essential',
        ];
        $this->installed = acym_isExtensionActive('com_easyblog');

        $this->pluginDescription->name = 'EasyBlog';
        $this->rootCategoryId = 0;
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'intro' => ['ACYM_INTRO_TEXT', true],
                'content' => ['ACYM_CONTENT', false],
                'cat' => ['ACYM_CATEGORIES', false],
                'readmore' => ['ACYM_READ_MORE', true],
            ];

            $this->initCustomView(true);

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
