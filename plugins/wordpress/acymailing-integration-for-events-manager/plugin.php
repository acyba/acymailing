<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'EventsManagerAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'EventsManagerAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'EventsManagerInsertion.php';

class plgAcymEventsmanager extends AcymPlugin
{
    use EventsManagerAutomationConditions;
    use EventsManagerAutomationFilters;
    use EventsManagerInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('events-manager/events-manager.php');
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'Events Manager';
        $this->pluginDescription->icon = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/icon.svg';
        $this->pluginDescription->category = 'Events management';
        $this->pluginDescription->description = '- Insert events in your emails<br />- Filter users by event subscription';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'image' => ['ACYM_FEATURED_IMAGE', true],
                'intro' => ['ACYM_INTRO_TEXT', true],
                'fulltext' => ['ACYM_FULL_TEXT', false],
                'date' => ['ACYM_DATE', false],
                'location' => ['ACYM_LOCATION', true],
                'cutoff' => [__('Booking Cut-Off Date', 'events-manager'), false],
                'cats' => ['ACYM_CATEGORIES', false],
                'tags' => ['ACYM_TAGS', false],
                'author' => ['ACYM_AUTHOR', false],
                'attributes' => [__('Attributes', 'events-manager'), false],
                'customfields' => ['ACYM_CUSTOM_FIELDS', false],
                'readmore' => ['ACYM_READ_MORE', false],
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
