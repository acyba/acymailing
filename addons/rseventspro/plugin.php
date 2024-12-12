<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'RseventsproAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'RseventsproAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'RseventsproInsertion.php';

class plgAcymRseventspro extends acymPlugin
{
    use RseventsproAutomationConditions;
    use RseventsproAutomationFilters;
    use RseventsproInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'RSEvents!Pro',
            'description' => '- Insert events in your emails<br>- Filter users based on their event subscriptions',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/rsevents-pro',
            'category' => 'Events management',
            'level' => 'enterprise',
        ];
        $this->installed = acym_isExtensionActive('com_rseventspro');

        $this->pluginDescription->name = 'RSEvents!Pro';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'icon' => ['ACYM_IMAGE', true],
                'date' => ['ACYM_DATE', true],
                'short' => ['ACYM_SHORT_DESCRIPTION', true],
                'desc' => ['ACYM_DESCRIPTION', false],
                'location' => ['ACYM_LOCATION', true],
                'cats' => ['COM_RSEVENTSPRO_GLOBAL_CATEGORIES', false],
                'tags' => ['COM_RSEVENTSPRO_GLOBAL_TAGS', false],
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

    public function getPossibleIntegrations()
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') return null;

        return $this->pluginDescription;
    }
}
