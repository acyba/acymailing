<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'JeventsAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'JeventsAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'JeventsAutomationTriggers.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'JeventsInsertion.php';

class plgAcymJevents extends AcymPlugin
{
    use JeventsAutomationConditions;
    use JeventsAutomationFilters;
    use JeventsAutomationTriggers;
    use JeventsInsertion;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'JEvents',
            'description' => '- Insert events in your emails<br>- Filter your users based on their event registrations<br>- Trigger automations based on starting events',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/jevents',
            'category' => 'Events management',
            'level' => 'starter',
        ];
        $this->installed = acym_isExtensionActive('com_jevents');

        $this->pluginDescription->name = 'JEvents';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.ico';

        if ($this->installed) {
            $this->initCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->customOptions, $this->replaceOptions, $this->elementOptions),
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
