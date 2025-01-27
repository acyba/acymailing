<?php

use AcyMailing\Core\AcymPlugin;
use Joomla\CMS\Component\ComponentHelper;

require_once __DIR__.DIRECTORY_SEPARATOR.'VirtuemartAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'VirtuemartAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'VirtuemartAutomationTriggers.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'VirtuemartInsertion.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'VirtuemartSubscription.php';

class plgAcymVirtuemart extends AcymPlugin
{
    use VirtuemartAutomationConditions;
    use VirtuemartAutomationFilters;
    use VirtuemartAutomationTriggers;
    use VirtuemartInsertion;
    use VirtuemartSubscription;

    private $lang = null;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'VirtueMart',
            'description' => '- Insert products in your emails<br>- Create and send personal coupons to your receivers<br>- Filter your users based on their VM fields<br>- Filter users based on their shopper groups<br>- Filter customers based on their purchases',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/virtuemart',
            'category' => 'E-commerce solutions',
            'level' => 'enterprise',
        ];
        $this->installed = acym_isExtensionActive('com_virtuemart');
        if ($this->installed) {
            $params = ComponentHelper::getParams('com_languages');
            $this->lang = strtolower(str_replace('-', '_', $params->get('site', 'en-GB')));
        }

        $this->pluginDescription->name = 'VirtueMart';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';
        $this->rootCategoryId = 0;

        if ($this->installed && acym_getVar('string', 'option', '') === 'com_acym') {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'price' => ['ACYM_PRICE', true],
                'image' => ['ACYM_IMAGE', true],
                'shortdesc' => ['ACYM_SHORT_DESCRIPTION', true],
                'desc' => ['ACYM_DESCRIPTION', false],
                'cats' => ['ACYM_CATEGORIES', false],
                'readmore' => ['ACYM_READ_MORE', false],
            ];

            $this->initCustomView(true);

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->replaceOptions, $this->elementOptions, $this->customOptions),
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
                'vat' => [
                    'type' => 'switch',
                    'label' => 'ACYM_PRICE_WITH_TAX',
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
