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

        $woocommerceOrderStatus = $this->getOrderStatuses();
        $multiselectOrderStatus = acym_selectMultiple(
            $woocommerceOrderStatus,
            'followup[condition][order_status]',
            !empty($followup->condition) && $followup->condition['order_status'] ? $followup->condition['order_status'] : [],
            ['class' => 'acym__select']
        );
        $multiselectOrderStatus = '<span class="cell large-4 medium-6 acym__followup__condition__select__in-text">'.$multiselectOrderStatus.'</span>';
        $statusOrderStatus = '<span class="cell large-1 medium-2 acym__followup__condition__select__in-text">'.acym_select(
                $statusArray,
                'followup[condition][order_status_status]',
                !empty($followup->condition) ? $followup->condition['order_status_status'] : '',
                ['class' => 'acym__select']
            ).'</span>';
        $additionalCondition['order_status'] = acym_translationSprintf('ACYM_WOOCOMMERCE_ORDER_STATUS_IN', $statusOrderStatus, $multiselectOrderStatus);


        $parametersProductSelect = [
            'class' => 'acym__select acym_select2_ajax',
            'data-params' => acym_escape([
                'plugin' => 'plgAcymWoocommerce',
                'trigger' => 'searchProduct',
            ], false),
            'data-selected' => !empty($followup->condition) && !empty($followup->condition['products']) ? implode(',', $followup->condition['products']) : '',
        ];
        $woocommerceProducts = [];
        $multiselectProducts = acym_selectMultiple(
            $woocommerceProducts,
            'followup[condition][products]',
            !empty($followup->condition) && !empty($followup->condition['products']) ? $followup->condition['products'] : [],
            $parametersProductSelect
        );
        $multiselectProducts = '<span class="cell large-4 medium-6 acym__followup__condition__select__in-text">'.$multiselectProducts.'</span>';
        $statusProducts = '<span class="cell large-1 medium-2 acym__followup__condition__select__in-text">'.acym_select(
                $statusArray,
                'followup[condition][products_status]',
                !empty($followup->condition) ? $followup->condition['products_status'] : '',
                ['class' => 'acym__select']
            ).'</span>';
        $additionalCondition['products'] = acym_translationSprintf('ACYM_WOOCOMMERCE_PRODUCT_IN', $statusProducts, $multiselectProducts);

        $parametersCategoriesSelect = [
            'class' => 'acym__select acym_select2_ajax',
            'data-params' => acym_escape([
                'plugin' => 'plgAcymWoocommerce',
                'trigger' => 'searchCat',
            ], false),
            'data-selected' => !empty($followup->condition) && !empty($followup->condition['categories']) ? implode(',', $followup->condition['categories']) : '',
        ];
        $woocommerceCategories = [];
        $multiselectCategories = acym_selectMultiple(
            $woocommerceCategories,
            'followup[condition][categories]',
            !empty($followup->condition) && !empty($followup->condition['categories']) ? $followup->condition['categories'] : [],
            $parametersCategoriesSelect
        );
        $multiselectCategories = '<span class="cell large-4 medium-6 acym__followup__condition__select__in-text">'.$multiselectCategories.'</span>';
        $statusCategories = '<span class="cell large-1 medium-2 acym__followup__condition__select__in-text">'.acym_select(
                $statusArray,
                'followup[condition][categories_status]',
                !empty($followup->condition) ? $followup->condition['categories_status'] : '',
                ['class' => 'acym__select']
            ).'</span>';
        $additionalCondition['categories'] = acym_translationSprintf('ACYM_WOOCOMMERCE_CATEGORY_IN', $statusCategories, $multiselectCategories);
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
            echo json_encode($value);
            exit;
        }

        $search = acym_getVar('string', 'search', '');
        $cats = $this->getWooCategories([], $search);
        $categories = [];
        foreach ($cats as $oneCat) {
            $categories[] = [$oneCat->term_id, $oneCat->name];
        }

        echo json_encode($categories);
        exit;
    }

    public function matchFollowupsConditions(&$followups, $userId, $params)
    {
        foreach ($followups as $key => $followup) {
            if ($followup->trigger != $this->purchaseTriggerName) continue;
            //We check the order status
            if (!empty($followup->condition['order_status_status']) && !empty($followup->condition['order_status'])) {
                $status = $followup->condition['order_status_status'] == 'is';
                $inArray = in_array('wc-'.$params['woo_order_status'], $followup->condition['order_status']);
                if (($status && !$inArray) || (!$status && $inArray)) unset($followups[$key]);
            }

            //We check the products
            if (!empty($followup->condition['products_status']) && !empty($followup->condition['products'])) {
                $status = $followup->condition['products_status'] == 'is';
                $inArray = false;
                foreach ($params['woo_order_product_ids'] as $product_id) {
                    if (in_array($product_id, $followup->condition['products'])) {
                        $inArray = true;
                        break;
                    }
                }
                if (($status && !$inArray) || (!$status && $inArray)) unset($followups[$key]);
            }

            //We check the categories
            if (!empty($followup->condition['categories_status']) && !empty($followup->condition['categories'])) {
                $status = $followup->condition['categories_status'] == 'is';
                $inArray = false;
                foreach ($params['woo_order_cat_ids'] as $cat_id) {
                    if (in_array($cat_id, $followup->condition['categories'])) {
                        $inArray = true;
                        break;
                    }
                }
                if (($status && !$inArray) || (!$status && $inArray)) unset($followups[$key]);
            }
        }
    }

    public function getFollowupConditionSummary(&$return, $condition, $trigger, $statusArray)
    {
        if ($trigger == $this->purchaseTriggerName) {
            if (empty($condition['order_status_status']) || empty($condition['order_status'])) {
                $return[] = acym_translation('ACYM_EVERY_ORDER_STATUS');
            } else {
                $woocommerceOrderStatus = $this->getOrderStatuses();
                $orderStatusToDisplay = [];
                foreach ($woocommerceOrderStatus as $key => $orderStatus) {
                    if (in_array($key, $condition['order_status'])) $orderStatusToDisplay[] = $orderStatus;
                }
                $return[] = acym_translationSprintf('ACYM_ORDER_STATUS_X_IN_X', strtolower($statusArray[$condition['order_status_status']]), implode(', ', $orderStatusToDisplay));
            }

            if (empty($condition['products_status']) || empty($condition['products'])) {
                $return[] = acym_translation('ACYM_ANY_PRODUCT');
            } else {
                $args = [
                    'post__in' => $condition['products'],
                    'post_type' => 'product',
                ];
                $posts = new WP_Query($args);

                $productsToDisplay = [];
                if ($posts->have_posts()) {
                    foreach ($posts->get_posts() as $post) {
                        $productsToDisplay[] = $post->post_title;
                    }
                }
                $return[] = acym_translationSprintf('ACYM_PRODUCTS_X_IN_X', strtolower($statusArray[$condition['products_status']]), implode(', ', $productsToDisplay));
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
                $return[] = acym_translationSprintf('ACYM_CATEGORIES_X_IN_X', strtolower($statusArray[$condition['categories_status']]), implode(', ', $categoriesToDisplay));
            }
        }
    }
}
