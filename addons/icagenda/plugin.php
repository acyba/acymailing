<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'IcagendaAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'IcagendaAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'IcagendaInsertion.php';

class plgAcymIcagenda extends AcymPlugin
{
    use IcagendaAutomationConditions;
    use IcagendaAutomationFilters;
    use IcagendaInsertion;

    public function __construct()
    {
        parent::__construct();

        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'iCagenda',
            'description' => '- Insert events in your emails<br>- Filter your users based on their event registrations',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/icagenda',
            'category' => 'Events management',
            'level' => 'essential',
        ];
        $this->installed = acym_isExtensionActive('com_icagenda');
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'iCagenda';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            acym_loadLanguageFile('com_icagenda', JPATH_SITE);
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'image' => ['ACYM_IMAGE', true],
                'date' => [acym_translation('COM_ICAGENDA_EVENT_DATE_FUTUR'), true],
                'venue' => [acym_translation('COM_ICAGENDA_EVENT_PLACE'), true],
                'short' => ['ACYM_SHORT_DESCRIPTION', true],
                'desc' => ['ACYM_DESCRIPTION', false],
                'email' => [acym_translation('COM_ICAGENDA_EVENT_MAIL'), false],
                'phone' => [acym_translation('COM_ICAGENDA_EVENT_PHONE'), false],
                'availableseats' => [acym_translation('COM_ICAGENDA_EVENT_NUMBER_OF_SEATS_AVAILABLE'), true],
                'totalseats' => [acym_translation('COM_ICAGENDA_EVENT_NUMBER_OF_SEATS'), false],
                'website' => [acym_translation('COM_ICAGENDA_EVENT_WEBSITE'), false],
                'cat' => ['ACYM_CATEGORY', false],
            ];

            $this->initCustomView(true);

            $tags = array_merge($this->displayOptions, $this->replaceOptions, $this->customOptions);
            foreach ($this->elementOptions as $key => $value) {
                if (!isset($tags[$key])) {
                    $tags[$key] = $value;
                }
            }

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => $tags,
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
