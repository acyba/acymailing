<?php

use AcyMailing\Core\AcymPlugin;

require_once __DIR__.DIRECTORY_SEPARATOR.'HikashopAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'HikashopAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'HikashopAutomationTriggers.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'HikashopFollowup.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'HikashopInsertion.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'HikashopSubscription.php';

class plgAcymHikashop extends AcymPlugin
{
    use HikashopAutomationConditions;
    use HikashopAutomationFilters;
    use HikashopAutomationTriggers;
    use HikashopFollowup;
    use HikashopInsertion;
    use HikashopSubscription;

    private $purchaseTriggerName = 'hikashop_purchase';

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->addonDefinition = [
            'name' => 'HikaShop',
            'description' => '- Insert products and generate coupons in your emails<br>- Filter your users based on their purchases<br>- Trigger automations when an order gets confirmed',
            'documentation' => 'https://docs.acymailing.com/addons/joomla-add-ons/hikashop',
            'category' => 'E-commerce solutions',
            'level' => 'starter',
        ];
        $this->installed = acym_isExtensionActive('com_hikashop');

        $this->pluginDescription->name = 'HikaShop';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.ico';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'price' => ['ACYM_PRICE', true],
                'image' => ['ACYM_IMAGE', true],
                'shortdesc' => ['ACYM_SHORT_DESCRIPTION', true],
                'desc' => ['ACYM_DESCRIPTION', false],
                'readmore' => ['ACYM_READ_MORE', false],
            ];

            $this->initCustomView();

            $frontData = [
                'all' => 'ACYM_ALL_ELEMENTS',
                'hide' => 'ACYM_DONT_SHOW',
            ];
            if (acym_isExtensionActive('com_hikamarket')) {
                $frontData['user'] = 'ACYM_DISPLAY_OWN_USER_PRODUCTS';
            }

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->replaceOptions, $this->elementOptions),
                ],
                'front' => [
                    'type' => 'select',
                    'label' => 'ACYM_FRONT_ACCESS',
                    'value' => 'all',
                    'data' => $frontData,
                ],
                'vat' => [
                    'type' => 'switch',
                    'label' => 'ACYM_PRICE_WITH_TAX',
                    'value' => 1,
                ],
                'stock' => [
                    'type' => 'switch',
                    'label' => 'ACYM_ONLY_IN_STOCK',
                    'value' => 0,
                ],
            ];
        } else {
            $this->settings = [
                'not_installed' => '1',
            ];
        }
    }

    public function getPossibleIntegrations(): ?object
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') return null;

        return $this->pluginDescription;
    }
}
