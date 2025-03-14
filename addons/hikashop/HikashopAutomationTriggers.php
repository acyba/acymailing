<?php

use AcyMailing\Classes\AutomationClass;
use AcyMailing\Classes\FollowupClass;
use AcyMailing\Classes\UserClass;
use AcyMailing\Helpers\ScenarioHelper;

trait HikashopAutomationTriggers
{
    // Add trigger configuration for Hikashop order status change and wishlist
    public function onAcymDeclareTriggers(&$triggers, &$defaultValues)
    {
        if (!include_once rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php') {
            return;
        }

        $triggers['user']['hikashopNewOrder'] = new stdClass();
        $triggers['user']['hikashopNewOrder']->name = acym_translationSprintf('ACYM_X_NEW_ORDER', 'HikaShop');
        $triggers['user']['hikashopNewOrder']->option = '<input type="hidden" name="[triggers][user][hikashopNewOrder][hidden]" value="">';

        $triggers['user']['hikashopWishlistUpdated'] = new stdClass();
        $triggers['user']['hikashopWishlistUpdated']->name = acym_translationSprintf('ACYM_X_ADD_TO_WISHLIST', 'HikaShop');
        $triggers['user']['hikashopWishlistUpdated']->option = '<input type="hidden" name="[triggers][user][hikashopWishlistUpdated][hidden]" value="">';

        $rows = acym_loadObjectList('SELECT * FROM #__hikashop_orderstatus');
        if (empty($rows)) {
            return;
        }
        $cats = [];
        foreach ($rows as $row) {
            $name = hikashop_orderStatus($row->orderstatus_namekey);
            if ($name == $row->orderstatus_namekey) {
                $name = $row->orderstatus_name;
            }
            $cats[$row->orderstatus_namekey] = $name;
        }

        $selectedValue = empty($defaultValues['hikashoporder']['status']) ? [] : $defaultValues['hikashoporder']['status'];

        $triggers['user']['hikashoporder'] = new stdClass();
        $triggers['user']['hikashoporder']->name = acym_translationSprintf('ACYM_ORDER_STATUS_CHANGED', 'HikaShop', '');
        $triggers['user']['hikashoporder']->option = acym_selectMultiple(
            $cats,
            '[triggers][user][hikashoporder][status]',
            $selectedValue,
            ['data-class' => 'acym__select']
        );
    }

    public function onAcymDeclareTriggersScenario(&$triggers, &$defaultValues)
    {
        $this->onAcymDeclareTriggers($triggers, $defaultValues);
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        if (empty($data['userId'])) {
            return;
        }

        $triggers = $step->triggers;

        if (!empty($triggers['hikashoporder']) && !empty($data['order'])) {
            if (in_array($data['order']->order_status, $triggers['hikashoporder']['status'])) {
                $execute = true;
            }
        }
        if (!empty($triggers['hikashopNewOrder']) && !empty($data['order'])) {
            if ($data['order']->order_status === 'confirmed') {
                $execute = true;
            }
        }
        if (!empty($triggers['hikashopWishlistUpdated'])) {
            if ($data['cart_type'] === 'wishlist') {
                $execute = true;
            }
        }
    }

    // Trigger on HikaShop status change
    public function onAfterOrderUpdate(&$order)
    {
        if (empty($order->order_id) || empty($order->order_status)) {
            return;
        }

        // Get HikaShop user from the order
        if (empty($order->order_user_id)) {
            $class = hikashop_get('class.order');
            $old = $class->get($order->order_id);
            if (empty($old)) return;
            $order->order_user_id = $old->order_user_id;
        }
        $hikaUserClass = hikashop_get('class.user');
        $hikaUser = $hikaUserClass->get($order->order_user_id);
        if (empty($hikaUser)) return;

        // Trigger the automation
        $userClass = new UserClass();
        $user = $userClass->getOneByEmail(!empty($hikaUser->email) ? $hikaUser->email : $hikaUser->user_email);
        if (empty($user->id)) return;

        //We get the order status id
        $orderStatus = acym_loadResult('SELECT `orderstatus_id` FROM #__hikashop_orderstatus WHERE orderstatus_namekey = '.acym_escapeDB($order->order_status));

        //We get the products ids
        $productIds = acym_loadResultArray('SELECT product_id FROM #__hikashop_order_product WHERE order_id = '.intval($order->order_id));
        acym_arrayToInteger($productIds);
        if (!empty($productIds)) {
            $productIdsParent = acym_loadResultArray('SELECT product_parent_id FROM #__hikashop_product WHERE product_id IN ('.implode(',', $productIds).')');
            $productIds = array_unique(array_merge($productIds, $productIdsParent));
        }

        //We get the categories ids
        acym_arrayToInteger($productIds);
        $categoriesIds = empty($productIds) ? [] : acym_loadResultArray('SELECT category_id FROM #__hikashop_product_category WHERE product_id IN ('.implode(',', $productIds).')');

        $params = [
            'hika_order_status' => $orderStatus,
            'hika_order_product_ids' => $productIds,
            'hika_order_cat_ids' => $categoriesIds,
            'hika_order_id' => $order->order_id,
        ];

        $followupClass = new FollowupClass();
        $followupClass->addFollowupEmailsQueue($this->purchaseTriggerName, $user->id, $params);

        $automationClass = new AutomationClass();

        $automationClass->trigger('hikashopNewOrder', [
            'userId' => $user->id,
            'order' => $order,
        ]);
        $automationClass->trigger('hikashoporder', [
            'userId' => $user->id,
            'order' => $order,
        ]);

        $scenarioHelper = new ScenarioHelper();

        $scenarioHelper->trigger('hikashopNewOrder', [
            'userId' => $user->id,
            'order' => $order,
        ]);
        $scenarioHelper->trigger('hikashoporder', [
            'userId' => $user->id,
            'order' => $order,
        ]);
    }

    public function onAfterCartSave(&$element)
    {
        // Get Hikashop user from the cart
        if (empty($element->user_id)) {
            $class = hikashop_get('class.cart');
            $old = $class->get($element->cart_id);
            if (empty($old)) return;
            $element->user_id = $old->user_id;
        }

        $hikaUserClass = hikashop_get('class.user');
        $hikaUser = $hikaUserClass->get($element->user_id);
        if (empty($hikaUser)) return;

        $userClass = new UserClass();
        $user = $userClass->getOneByEmail(!empty($hikaUser->email) ? $hikaUser->email : $hikaUser->user_email);
        if (empty($user->id)) return;

        $automationClass = new AutomationClass();
        $automationClass->trigger('hikashopWishlistUpdated', [
            'userId' => $user->id,
            'cart_type' => $element->cart_type,
        ]);

        $scenarioHelper = new ScenarioHelper();
        $scenarioHelper->trigger('hikashopWishlistUpdated', [
            'userId' => $user->id,
            'cart_type' => $element->cart_type,
        ]);
    }

    // Build Hikashop trigger display for the summary
    public function onAcymDeclareSummary_triggers(&$automation)
    {
        if (!empty($automation->triggers['hikashoporder']['status'])) {
            //$return = acym_translation('ACYM_HIKASHOP_ORDER_STATUS_TO').' ';
            $status = implode(', ', $automation->triggers['hikashoporder']['status']);
            $automation->triggers['hikashoporder'] = acym_translationSprintf('ACYM_ORDER_STATUS_CHANGED', 'HikaShop', $status);
        }
        if (isset($automation->triggers['hikashopNewOrder'])) {
            $automation->triggers['hikashopNewOrder'] = acym_translationSprintf('ACYM_X_NEW_ORDER', 'HikaShop');
        }
        if (isset($automation->triggers['hikashopWishlistUpdated'])) {
            $automation->triggers['hikashopWishlistUpdated'] = acym_translationSprintf('ACYM_X_ADD_TO_WISHLIST', 'HikaShop');
        }
    }
}
