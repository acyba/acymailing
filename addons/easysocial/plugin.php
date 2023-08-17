<?php

use AcyMailing\Libraries\acymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'EasysocialAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'EasysocialAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'EasysocialInsertion.php';

class plgAcymEasysocial extends acymPlugin
{
    use EasysocialAutomationConditions;
    use EasysocialAutomationFilters;
    use EasysocialInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'EasySocial',
            'description' => '- Insert user information in emails<br>- Insert events in your emails<br>- Filter users on their profile type<br>- Filter users on their group<br>- Filter users on their badges<br>- Filter users on their fields<br>- Filter users attending events',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/easysocial',
            'category' => 'User management',
            'level' => 'starter',
        ];
        $this->installed = acym_isExtensionActive('com_easysocial');
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'EasySocial';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'image' => ['ACYM_IMAGE', true],
                'date' => ['ACYM_DATE', true],
                'location' => ['ACYM_LOCATION', true],
                'desc' => ['ACYM_DESCRIPTION', true],
                'url' => ['ACYM_URL', false],
                'capacity' => ['FIELDS_EVENT_GUESTLIMIT_DEFAULT_TITLE', false],
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
