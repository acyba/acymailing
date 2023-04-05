<?php

use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\AutomationClass;

trait WooCommerceAutomationTriggers
{
    private $purchaseTriggerName = 'woocommerce_purchase';

    public function onAcymDeclareTriggers(&$triggers, &$defaultValues)
    {
        $orderStatuses = $this->getOrderStatuses(true);

        $triggers['user']['woocommerce_order_change'] = new stdClass();
        $triggers['user']['woocommerce_order_change']->name = acym_translation('ACYM_ON_WOOCOMMERCE_ORDER_CHANGE');
        $triggers['user']['woocommerce_order_change']->option = '<div class="grid-x grid-margin-x" style="height: 40px;">';
        $triggers['user']['woocommerce_order_change']->option .= '<div class="cell medium-shrink acym_vcenter">'.acym_translation('ACYM_FROM').'</div>';
        $triggers['user']['woocommerce_order_change']->option .= '<div class="cell medium-4">'.acym_select(
                $orderStatuses,
                '[triggers][user][woocommerce_order_change][from]',
                empty($defaultValues['woocommerce_order_change']['from']) ? 0 : $defaultValues['woocommerce_order_change']['from'],
                'data-class="acym__select"'
            ).'</div>';
        $triggers['user']['woocommerce_order_change']->option .= '<div class="cell medium-shrink acym_vcenter">'.acym_translation('ACYM_TO').'</div>';
        $triggers['user']['woocommerce_order_change']->option .= '<div class="cell medium-4">'.acym_select(
                $orderStatuses,
                '[triggers][user][woocommerce_order_change][to]',
                empty($defaultValues['woocommerce_order_change']['to']) ? 'wc-completed' : $defaultValues['woocommerce_order_change']['to'],
                'data-class="acym__select"'
            ).'</div>';
        $triggers['user']['woocommerce_order_change']->option .= '</div>';
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        $triggers = $step->triggers;
        $from = false;
        $to = false;
        if (!empty($triggers['woocommerce_order_change'])) {
            // Woocommerce removes the "wc-" prefix on the native order statuses that are sent in the hook
            $fromStatus = 'wc-'.$data['statusFrom'];
            $toStatus = 'wc-'.$data['statusTo'];
            if ($fromStatus === $triggers['woocommerce_order_change']['from'] || $triggers['woocommerce_order_change']['from'] === '0') $from = true;
            if ($toStatus === $triggers['woocommerce_order_change']['to'] || $triggers['woocommerce_order_change']['to'] === '0') $to = true;

            if ($from && $to) $execute = true;
        }
    }

    public function onAcymDeclareSummary_triggers(&$automation)
    {
        if (empty($automation->triggers['woocommerce_order_change'])) return;

        $orderStatuses = $this->getOrderStatuses(true);

        $automation->triggers['woocommerce_order_change'] = acym_translationSprintf(
            'ACYM_TRIGGER_WOOCOMMERCE_ORDER_CHANGE_SUMMARY',
            $orderStatuses[$automation->triggers['woocommerce_order_change']['from']],
            $orderStatuses[$automation->triggers['woocommerce_order_change']['to']]
        );
    }

    public function onWooCommerceOrderStatusChange($orderId, $statusFrom, $statusTo, $order)
    {
        $userClass = new UserClass();
        $wpUserId = $order->get_user_id();
        if (!empty($wpUserId)) $acyUser = $userClass->getOneByCMSId($wpUserId);
        if (empty($acyUser)) {
            $billingEmail = $order->get_billing_email();
            if (!empty($billingEmail)) {
                $acyUser = $userClass->getOneByEmail($billingEmail);
            }
        }
        if (empty($acyUser)) return;

        $items = $order->get_items();
        $productIds = [];
        $categoriesIds = [];
        foreach ($items as $item) {
            $productIds[] = $item->get_product_id();
            $terms = get_the_terms($item->get_product_id(), 'product_cat');
            if (!empty($terms)) {
                foreach ($terms as $term) {
                    if (!in_array($term->term_id, $categoriesIds)) {
                        $categoriesIds[] = $term->term_id;
                    }
                }
            }
        }

        $params = [
            'woo_order_status' => $statusTo,
            'woo_order_product_ids' => $productIds,
            'woo_order_cat_ids' => $categoriesIds,
        ];

        $followupClass = new FollowupClass();
        $followupClass->addFollowupEmailsQueue($this->purchaseTriggerName, $acyUser->id, $params);

        $automationClass = new AutomationClass();
        $automationClass->trigger(
            'woocommerce_order_change',
            [
                'userId' => $acyUser->id,
                'statusFrom' => $statusFrom,
                'statusTo' => $statusTo,
            ]
        );
    }
}
