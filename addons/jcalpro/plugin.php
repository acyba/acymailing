<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'JcalproInsertion.php';

class plgAcymJcalpro extends AcymPlugin
{
    use JcalproInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'JCal Pro',
            'description' => '- Insert events in your emails',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/jcal-pro',
            'category' => 'Events management',
            'level' => 'starter',
        ];
        $this->installed = acym_isExtensionActive('com_jcalpro');

        $this->pluginDescription->name = 'JCal Pro';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'date' => ['ACYM_DATE', true],
                'description' => ['ACYM_DESCRIPTION', true],
                'location' => ['ACYM_LOCATION', true],
                'categories' => ['ACYM_CATEGORIES', false],
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
                'hidepast' => [
                    'type' => 'switch',
                    'label' => 'ACYM_HIDE_PAST_EVENTS',
                    'value' => 1,
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
