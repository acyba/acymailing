<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'ModernEventsCalendarAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'ModernEventsCalendarAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'ModernEventsCalendarInsertion.php';

class plgAcymModerneventscalendar extends acymPlugin
{
    use ModernEventsCalendarAutomationConditions;
    use ModernEventsCalendarAutomationFilters;
    use ModernEventsCalendarInsertion;

    const MEC_LITE = 'modern-events-calendar-lite';
    const MEC_FULL = 'mec';

    protected $fullInstalled = false;
    protected $liteInstalled = false;
    protected $textDomain;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->liteInstalled = acym_isExtensionActive('modern-events-calendar-lite/modern-events-calendar-lite.php');
        $this->fullInstalled = acym_isExtensionActive('modern-events-calendar/mec.php');
        $this->installed = $this->liteInstalled || $this->fullInstalled;
        $this->textDomain = self::MEC_LITE;
        if ($this->fullInstalled) $this->textDomain = self::MEC_FULL;

        $this->pluginDescription->name = 'M.E. Calendar';
        $this->pluginDescription->icon = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/icon.png';
        $this->pluginDescription->category = 'Events management';
        $this->pluginDescription->features = '["content","automation"]';
        $this->pluginDescription->description = '- Insert events in your emails<br />- Filter users by event subscription';
        $this->rootCategoryId = 0;

        if ($this->installed) {
            $diplayBookingOption = [
                'bookingsLimit' => ['ACYM_BOOKING_LIMIT', false],
                'attendees' => ['ACYM_ATTENDEES', false],
            ];
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'price' => ['ACYM_PRICE', true],
                'image' => ['ACYM_IMAGE', true],
                'intro' => ['ACYM_INTRO_ONLY', true],
                'full' => ['ACYM_FULL_TEXT', false],
                'date' => ['ACYM_DATE', true],
                'location' => ['ACYM_LOCATION', true],
                'moreinfo' => [__('More Info', $this->textDomain), false],
                'tags' => ['ACYM_TAGS', false],
                'cats' => ['ACYM_CATEGORIES', false],
                'labels' => [__('Event Labels', $this->textDomain), false],
                'organizer' => ['ACYM_ORGANIZER', false],
                'otherOrganizer' => ['ACYM_OTHER_ORGANIZER', false],
                'otherLocation' => ['ACYM_OTHER_LOCATION', false],
                'eventNextOccurrences' => ['ACYM_NEXT_OCCURRENCES', false],
            ];

            if ($this->fullInstalled) {
                $bookingIsOn = get_option('mec_options', []);
                if ($bookingIsOn['settings']['booking_status']) {
                    $this->displayOptions = array_merge($this->displayOptions, $diplayBookingOption);
                }
            }
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
