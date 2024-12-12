<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'EventBookingAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'EventBookingAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'EventBookingAutomationTriggers.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'EventBookingInsertion.php';

class plgAcymEventbooking extends acymPlugin
{
    use EventBookingAutomationConditions;
    use EventBookingAutomationFilters;
    use EventBookingAutomationTriggers;
    use EventBookingInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'Event Booking',
            'description' => '- Insert events in your emails<br>- Filter your users based on their event registrations<br>- Trigger automations based on starting events',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/event-booking',
            'category' => 'Events management',
            'level' => 'starter',
        ];
        $this->installed = acym_isExtensionActive('com_eventbooking');
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'Events Booking';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            acym_loadLanguageFile('com_eventbooking', JPATH_SITE);
            acym_loadLanguageFile('com_eventbooking', JPATH_ADMINISTRATOR);
            acym_loadLanguageFile('com_eventbookingcommon', JPATH_ADMINISTRATOR);

            $this->displayOptions = [
                'eb_title' => [acym_translation('EB_TITLE'), true],
                'eb_price' => [acym_translation('EB_PRICE'), true],
                'eb_price_text' => [acym_translation('EB_PRICE_TEXT'), false],
                'eb_sdate' => [acym_translation('EB_EVENT_DATE'), true],
                'eb_edate' => [acym_translation('EB_EVENT_END_DATE'), true],
                'eb_imageee' => [acym_translation('EB_EVENT_IMAGE'), true],
                'eb_short' => [acym_translation('EB_SHORT_DESCRIPTION'), true],
                'eb_desc' => [acym_translation('EB_DESCRIPTION'), false],
                'eb_cats' => [acym_translation('EB_CATEGORIES'), false],
                'eb_location' => [acym_translation('ACYM_LOCATION'), true],
                'eb_capacity' => [acym_translation('EB_CAPACITY'), false],
                'eb_regstart' => [acym_translation('EB_REGISTRATION_START_DATE'), false],
                'eb_cut' => [acym_translation('EB_CUT_OFF_DATE'), false],
                'eb_early' => [acym_translation('EB_EARLY_BIRD_DISCOUNT_DATE'), false],
                'eb_cancel_before' => [acym_translation('EB_CANCEL_BEFORE_DATE'), false],
                'eb_registrant_edit_close' => [acym_translation('EB_REGISTRANT_EDIT_CLOSE_DATE'), false],
                'eb_created' => [acym_translation('ACYM_DATE_CREATED'), false],
                'eb_max_end' => [acym_translation('EB_VALIDATION_MAXIMUM').' '.acym_translation('EB_EVENT_END_DATE'), false],
                'eb_late_fee' => [acym_translation('EB_LATE_FEE_DATE'), false],
                'eb_deposit_until' => [acym_translation('EB_DEPOSIT_UNTIL_DATE'), false],
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
        return $this->pluginDescription;
    }
}
