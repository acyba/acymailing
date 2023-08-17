<?php

use AcyMailing\Libraries\acymPlugin;
use Automattic\WooCommerce\Utilities\OrderUtil;

require_once __DIR__.DIRECTORY_SEPARATOR.'WooCommerceAutomationConditions.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'WooCommerceAutomationFilters.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'WooCommerceAutomationTriggers.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'WooCommerceCampaignType.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'WooCommerceEmailOverrides.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'WooCommerceFollowup.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'WooCommerceInsertion.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'WooCommerceSubscription.php';
require_once __DIR__.DIRECTORY_SEPARATOR.'WooCommerceTracking.php';

class plgAcymWoocommerce extends acymPlugin
{
    use WooCommerceAutomationConditions;
    use WooCommerceAutomationFilters;
    use WooCommerceAutomationTriggers;
    use WooCommerceCampaignType;
    use WooCommerceEmailOverrides;
    use WooCommerceFollowup;
    use WooCommerceInsertion;
    use WooCommerceSubscription;
    use WooCommerceTracking;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('woocommerce/woocommerce.php');
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'WooCommerce';
        $this->pluginDescription->icon = ACYM_PLUGINS_URL.'/'.basename(__DIR__).'/icon.png';
        $this->pluginDescription->category = 'Content management';
        $this->pluginDescription->description = '- Insert products and generate coupons in your emails<br />- Filter users based on their purchases';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'price' => ['ACYM_PRICE', true],
                'shortdesc' => ['ACYM_SHORT_DESCRIPTION', true],
                'desc' => ['ACYM_DESCRIPTION', false],
                'cats' => ['ACYM_CATEGORIES', false],
                'attribs' => ['ACYM_DETAILS', false],
            ];

            $this->initCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->replaceOptions, $this->elementOptions),
                ],
                'track' => [
                    'type' => 'switch',
                    'label' => 'ACYM_TRACKING',
                    'value' => 0,
                    'info' => 'ACYM_TRACKING_WOOCOMMERCE_DESC',
                ],
                'cookie_expire' => [
                    'type' => 'number',
                    'label' => 'ACYM_COOKIE_EXPIRATION',
                    'value' => 1,
                    'info' => 'ACYM_COOKIE_EXPIRATION_DESC',
                    'post_text' => acym_translation('ACYM_HOURS'),
                ],
                'remove_VAT' => [
                    'type' => 'switch',
                    'label' => 'ACYM_REMOVE_VAT',
                    'value' => 0,
                    'info' => 'ACYM_REMOVE_VAT_DESC',
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

    public function onAcymInitWordpressAddons()
    {
        add_filter('woocommerce_checkout_fields', [$this, 'addSubscriptionFieldWC']);
        add_action('woocommerce_checkout_order_processed', [$this, 'subscribeUserOnCheckoutWC'], 15, 3);
        if (acym_isTrackingSalesActive()) {
            add_action('woocommerce_payment_successful_result', [$this, 'trackingWoocommerce'], 10, 2);
            $this->trackingWoocommerceAddCookie();
        }
        add_action('woocommerce_order_status_changed', [$this, 'onWooCommerceOrderStatusChange'], 50, 4);
        add_filter('woocommerce_mail_callback_params', [$this, 'onWooCommerceEmailSend'], 10, 2);
    }

    private function isHposActive(): bool
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && method_exists(
                OrderUtil::class,
                'custom_orders_table_usage_is_enabled'
            ) && OrderUtil::custom_orders_table_usage_is_enabled()) {
            return true;
        } else {
            return false;
        }
    }
}
