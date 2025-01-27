<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'EasyprofileAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'EasyprofileAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'EasyprofileInsertion.php';

class plgAcymEasyprofile extends AcymPlugin
{
    use EasyprofileAutomationConditions;
    use EasyprofileAutomationFilters;
    use EasyprofileInsertion;

    private $epfields = [];
    private $bannedFields = [];

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'EasyProfile',
            'description' => '- Insert User information in your emails from the EasyProfile extension.',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/easyprofile',
            'category' => 'User management',
            'level' => 'starter',
        ];
        $this->pluginDescription->name = 'Easy Profile';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        $this->installed = acym_isExtensionActive('com_jsn');
        if ($this->installed) {
            $this->epfields = acym_loadObjectList('SELECT `title`, `alias`, `type`, `params` FROM #__jsn_fields');
            $jsnColumns = acym_getColumns('jsn_users', false);
            $jUserColumns = acym_getColumns('users', false);
            foreach ($this->epfields as $key => $field) {
                if (!empty($field->params) && is_string($field->params)) {
                    $this->epfields[$key]->params = json_decode($field->params);
                }

                if (in_array($field->alias, $jsnColumns)) {
                    $this->epfields[$key]->table = '#__jsn_users';
                } elseif (in_array($field->alias, $jUserColumns)) {
                    $this->epfields[$key]->table = '#__users';
                } else {
                    unset($this->epfields[$key]);
                }
            }
            $this->bannedFields = ['password', 'avatar'];

            $this->initCustomView(true);

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->elementOptions, $this->replaceOptions),
                ],
                'front' => [
                    'type' => 'select',
                    'label' => 'ACYM_FRONT_ACCESS',
                    'value' => 'all',
                    'data' => [
                        'all' => 'ACYM_ALL_ELEMENTS',
                        'hide' => 'ACYM_DONT_SHOW',
                    ],
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
