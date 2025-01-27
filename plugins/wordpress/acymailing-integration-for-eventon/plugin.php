<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'EventOnInsertion.php';

class plgAcymEventon extends AcymPlugin
{
    use EventOnInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('eventON/eventon.php') || acym_isExtensionActive('eventon-lite/eventon.php');
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'EventON';
        $this->pluginDescription->icon = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/icon.png';
        $this->pluginDescription->category = 'Events management';
        $this->pluginDescription->description = '- Insert events in your emails';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'subtitle' => [__('Event Subtitle', 'eventon'), true],
                'price' => ['ACYM_PRICE', true],
                'image' => ['ACYM_IMAGE', true],
                'intro' => ['ACYM_INTRO_ONLY', true],
                'full' => ['ACYM_FULL_TEXT', false],
                'date' => ['ACYM_DATE', true],
                'location' => ['ACYM_LOCATION', true],
                'organiser' => ['ACYM_ORGANIZER', false],
                'tags' => ['ACYM_TAGS', false],
                'evtype' => [__('Event Type', 'eventon'), false],
                'evtype2' => [__('Event Type 2', 'eventon'), false],
            ];

            $this->initCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->replaceOptions, $this->elementOptions),
                ],
                'hidepast' => [
                    'type' => 'switch',
                    'label' => 'ACYM_HIDE_PAST_EVENTS',
                    'value' => 1,
                ],
                'currency_position' => [
                    'type' => 'radio',
                    'label' => 'ACYM_CURRENCY_POSITION',
                    'data' => [
                        'before' => 'ACYM_BEFORE',
                        'after' => 'ACYM_AFTER',
                    ],
                    'value' => 'after',
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
        return $this->pluginDescription;
    }
}
