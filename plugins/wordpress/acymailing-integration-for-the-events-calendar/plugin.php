<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'TheEventsCalendarAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'TheEventsCalendarAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'TheEventsCalendarInsertion.php';

class plgAcymTheeventscalendar extends acymPlugin
{
    use TheEventsCalendarAutomationConditions;
    use TheEventsCalendarAutomationFilters;
    use TheEventsCalendarInsertion;

    private $rtecInstalled;
    private $eventTicketsInstalled;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('the-events-calendar/the-events-calendar.php');
        $this->rtecInstalled = acym_isExtensionActive('registrations-for-the-events-calendar/registrations-for-the-events-calendar.php');
        $this->eventTicketsInstalled = acym_isExtensionActive('event-tickets/event-tickets.php');
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'The Events Calendar';
        $this->pluginDescription->icon = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/icon.png';
        $this->pluginDescription->category = 'Events management';
        $this->pluginDescription->description = '- Insert events in your emails<br />- Filter users by event subscription';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'price' => ['ACYM_PRICE', true],
                'image' => ['ACYM_IMAGE', true],
                'intro' => ['ACYM_INTRO_ONLY', true],
                'full' => ['ACYM_FULL_TEXT', false],
                'date' => ['ACYM_DATE', true],
                'location' => ['ACYM_LOCATION', true],
                'website' => [__('Event Website', 'the-events-calendar'), false],
                'tags' => ['ACYM_TAGS', false],
                'cats' => ['ACYM_CATEGORIES', false],
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
