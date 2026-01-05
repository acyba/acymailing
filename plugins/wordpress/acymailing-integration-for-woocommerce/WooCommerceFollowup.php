<?php

trait WooCommerceFollowup
{
    public function getFollowupTriggerBlock(&$blocks)
    {
        $blocks[] = [
            'name' => acym_translation('ACYM_WOOCOMMERCE_PURCHASE'),
            'description' => acym_translation('ACYM_WOOCOMMERCE_FOLLOW_UP_DESC'),
            'icon' => 'acymicon-cart-arrow-down',
            'link' => acym_completeLink('campaigns&task=edit&step=followupCondition&trigger='.$this->purchaseTriggerName),
            'level' => 2,
            'alias' => $this->purchaseTriggerName,
        ];
    }

    public function getFollowupTriggers(&$triggers)
    {
        $triggers[$this->purchaseTriggerName] = acym_translation('ACYM_WOOCOMMERCE_PURCHASE');
    }

    public function getAcymAdditionalConditionFollowup(&$additionalCondition, $trigger, $followup, $statusArray)
    {
        if ($trigger !== $this->purchaseTriggerName) {
            return;
        }

        $multiselectOrderStatus = '<span class="cell large-4 medium-6 acym__followup__condition__select__in-text">'.acym_selectMultiple(
                $this->getOrderStatuses(),
                'followup[condition][order_status]',
                !empty($followup->condition['order_status']) ? $followup->condition['order_status'] : [],
                ['class' => 'acym__select']
            ).'</span>';

        $statusOrderStatus = '<span class="cell large-1 medium-2 acym__followup__condition__select__in-text">'.acym_select(
                $statusArray,
                'followup[condition][order_status_status]',
                !empty($followup->condition['order_status_status']) ? $followup->condition['order_status_status'] : '',
                ['class' => 'acym__select']
            ).'</span>';

        $additionalCondition['order_status'] = acym_translationSprintf('ACYM_WOOCOMMERCE_ORDER_STATUS_IN', $statusOrderStatus, $multiselectOrderStatus);


        $multiselectProducts = '<span class="cell large-4 medium-6 acym__followup__condition__select__in-text">'.acym_selectMultiple(
                [],
                'followup[condition][products]',
                !empty($followup->condition['products']) ? $followup->condition['products'] : [],
                [
                    'class' => 'acym__select acym_select2_ajax',
                    'data-params' => [
                        'plugin' => 'plgAcymWoocommerce',
                        'trigger' => 'searchProduct',
                        'variations' => true,
                    ],
                    'data-selected' => !empty($followup->condition['products']) ? implode(',', $followup->condition['products']) : '',
                ]
            ).'</span>';

        $statusProducts = '<span class="cell large-1 medium-2 acym__followup__condition__select__in-text">'.acym_select(
                $statusArray,
                'followup[condition][products_status]',
                !empty($followup->condition['products_status']) ? $followup->condition['products_status'] : '',
                ['class' => 'acym__select']
            ).'</span>';

        $additionalCondition['products'] = acym_translationSprintf('ACYM_WOOCOMMERCE_PRODUCT_IN', $statusProducts, $multiselectProducts);


        $multiselectCategories = '<span class="cell large-4 medium-6 acym__followup__condition__select__in-text">'.acym_selectMultiple(
                [],
                'followup[condition][categories]',
                !empty($followup->condition['categories']) ? $followup->condition['categories'] : [],
                [
                    'class' => 'acym__select acym_select2_ajax',
                    'data-params' => [
                        'plugin' => 'plgAcymWoocommerce',
                        'trigger' => 'searchCat',
                    ],
                    'data-selected' => !empty($followup->condition['categories']) ? implode(',', $followup->condition['categories']) : '',
                ]
            ).'</span>';

        $statusCategories = '<span class="cell large-1 medium-2 acym__followup__condition__select__in-text">'.acym_select(
                $statusArray,
                'followup[condition][categories_status]',
                !empty($followup->condition['categories_status']) ? $followup->condition['categories_status'] : '',
                ['class' => 'acym__select']
            ).'</span>';

        $additionalCondition['categories'] = acym_translationSprintf('ACYM_WOOCOMMERCE_CATEGORY_IN', $statusCategories, $multiselectCategories);

        if ($this->wcsInstalled) {
            $additionalCondition['order_type'] = acym_translation('ACYM_ORDER_TYPE').'<span class="cell large-2 medium-6 acym__followup__condition__select__in-text">'.acym_select(
                    $this->orderTypes,
                    'followup[condition][order_type]',
                    empty($followup->condition['order_type']) ? '' : $followup->condition['order_type'],
                    ['class' => 'acym__select']
                ).'</span>';
        }
    }

    public function searchCat()
    {
        $ids = $this->getIdsSelectAjax();

        if (!empty($ids)) {
            $cats = $this->getWooCategories($ids);

            $value = [];
            if (!empty($cats)) {
                foreach ($cats as $cat) {
                    $value[] = ['text' => $cat->name, 'value' => $cat->term_id];
                }
            }
            echo wp_json_encode($value);
            exit;
        }

        $search = acym_getVar('string', 'search', '');
        $cats = $this->getWooCategories([], $search);
        $categories = [];
        foreach ($cats as $oneCat) {
            $categories[] = [$oneCat->term_id, $oneCat->name];
        }

        echo wp_json_encode($categories);
        exit;
    }

    public function matchFollowupsConditions(&$followups, $userId, $params)
    {
        foreach ($followups as $key => $followup) {
            if ($followup->trigger != $this->purchaseTriggerName) {
                continue;
            }

            //We check the order status
            if (!empty($followup->condition['order_status_status']) && !empty($followup->condition['order_status'])) {
                $status = $followup->condition['order_status_status'] === 'is';
                $inArray = in_array('wc-'.$params['woo_order_status'], $followup->condition['order_status']);
                if ($status !== $inArray) {
                    unset($followups[$key]);
                    continue;
                }
            }

            //We check the products
            if (!empty($followup->condition['products_status']) && !empty($followup->condition['products'])) {
                $status = $followup->condition['products_status'] === 'is';
                $inArray = false;
                foreach ($params['woo_order_product_ids'] as $product_id) {
                    if (in_array($product_id, $followup->condition['products'])) {
                        $inArray = true;
                        break;
                    }
                }

                if ($status !== $inArray) {
                    unset($followups[$key]);
                    continue;
                }
            }

            //We check the categories
            if (!empty($followup->condition['categories_status']) && !empty($followup->condition['categories'])) {
                $status = $followup->condition['categories_status'] === 'is';
                $inArray = false;
                foreach ($params['woo_order_cat_ids'] as $cat_id) {
                    if (in_array($cat_id, $followup->condition['categories'])) {
                        $inArray = true;
                        break;
                    }
                }

                if ($status !== $inArray) {
                    unset($followups[$key]);
                    continue;
                }
            }

            if (!empty($followup->condition['order_type'])) {
                $order = wc_get_order($params['woo_order_id']);

                if (in_array($followup->condition['order_type'], ['original', 'regular'])) {
                    if ($order->get_meta('_subscription_renewal') || $order->get_meta('_subscription_switch')) {
                        unset($followups[$key]);
                    } elseif ($followup->condition['order_type'] === 'regular' && !empty($order->get_parent_id())) {
                        unset($followups[$key]);
                    }
                } elseif ($followup->condition['order_type'] === 'parent') {
                    if (!empty($order->get_parent_id())) {
                        unset($followups[$key]);
                    }
                } else {
                    if ($followup->condition['order_type'] === 'renewal') {
                        $metaKey = '_subscription_renewal';
                    } elseif ($followup->condition['order_type'] === 'resubscribe') {
                        $metaKey = '_subscription_resubscribe';
                    } else {
                        $metaKey = '_subscription_switch';
                    }

                    $metaKey = apply_filters('woocommerce_subscriptions_admin_order_type_filter_meta_key', $metaKey, $followup->condition['order_type']);

                    if (empty($order->get_meta($metaKey))) {
                        unset($followups[$key]);
                    }
                }
            }
        }
    }

    public function getFollowupConditionSummary(&$return, $condition, $trigger, $statusArray)
    {
        if ($trigger !== $this->purchaseTriggerName) {
            return;
        }

        if (empty($condition['order_status_status']) || empty($condition['order_status'])) {
            $return[] = acym_translation('ACYM_EVERY_ORDER_STATUS');
        } else {
            $woocommerceOrderStatus = $this->getOrderStatuses();
            $orderStatusToDisplay = [];
            foreach ($woocommerceOrderStatus as $key => $orderStatus) {
                if (in_array($key, $condition['order_status'])) $orderStatusToDisplay[] = $orderStatus;
            }
            $return[] = acym_translationSprintf('ACYM_ORDER_STATUS_X_IN_X', acym_strtolower($statusArray[$condition['order_status_status']]), implode(', ', $orderStatusToDisplay));
        }

        if (empty($condition['products_status']) || empty($condition['products'])) {
            $return[] = acym_translation('ACYM_ANY_PRODUCT');
        } else {
            $args = [
                'post__in' => $condition['products'],
                'post_type' => ['product', 'product_variation'],
            ];
            $posts = new WP_Query($args);

            $productsToDisplay = [];
            if ($posts->have_posts()) {
                foreach ($posts->get_posts() as $post) {
                    $productsToDisplay[] = $post->post_title;
                }
            }
            $return[] = acym_translationSprintf('ACYM_PRODUCTS_X_IN_X', acym_strtolower($statusArray[$condition['products_status']]), implode(', ', $productsToDisplay));
        }

        if (empty($condition['categories_status']) || empty($condition['categories'])) {
            $return[] = acym_translation('ACYM_EVERY_CATEGORIES');
        } else {
            $cats = $this->getWooCategories($condition['categories']);

            $categoriesToDisplay = [];
            if (!empty($cats)) {
                foreach ($cats as $cat) {
                    $categoriesToDisplay[] = $cat->name;
                }
            }
            $return[] = acym_translationSprintf('ACYM_CATEGORIES_X_IN_X', acym_strtolower($statusArray[$condition['categories_status']]), implode(', ', $categoriesToDisplay));
        }

        if ($this->wcsInstalled && !empty($condition['order_type']) && !empty($this->orderTypes[$condition['order_type']])) {
            $return[] = acym_translation('ACYM_ORDER_TYPE').': '.$this->orderTypes[$condition['order_type']];
        }
    }
}
