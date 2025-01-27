<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'DpcalendarInsertion.php';

class plgAcymDpcalendar extends AcymPlugin
{
    use DpcalendarInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'DPCalendar',
            'description' => '- Insert events in your emails',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/dpcalendar',
            'category' => 'Events management',
            'level' => 'starter',
        ];
        $this->installed = acym_isExtensionActive('com_dpcalendar');

        $this->pluginDescription->name = 'DPCalendar';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'image' => ['ACYM_IMAGE', true],
                'date' => ['ACYM_DATE', true],
                'venue' => ['ACYM_LOCATION', true],
                'intro' => ['ACYM_INTRO_TEXT', true],
                'desc' => ['ACYM_FULL_TEXT', false],
                'url' => ['ACYM_URL', false],
                'capacity' => ['COM_DPCALENDAR_FIELD_CAPACITY_LABEL', false],
                'closingdate' => ['COM_DPCALENDAR_FIELD_BOOKING_CLOSING_DATE_LABEL', true],
                'tags' => ['ACYM_TAGS', false],
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

    public function getPossibleIntegrations()
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') return null;

        return $this->pluginDescription;
    }
}
