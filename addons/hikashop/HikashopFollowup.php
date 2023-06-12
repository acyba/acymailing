<?php

trait HikashopFollowup
{
    public function getFollowupTriggerBlock(&$blocks)
    {
        $blocks[] = [
            'name' => acym_translation('ACYM_HIKASHOP_PURCHASE'),
            'description' => acym_translation('ACYM_HIKASHOP_FOLLOW_UP_DESC'),
            'icon' => 'acymicon-cart-arrow-down',
            'link' => acym_completeLink('campaigns&task=edit&step=followupCondition&trigger='.$this->purchaseTriggerName),
            'level' => 2,
            'alias' => $this->purchaseTriggerName,
        ];
    }

    public function getFollowupTriggers(&$triggers)
    {
        $triggers[$this->purchaseTriggerName] = acym_translation('ACYM_HIKASHOP_PURCHASE');
    }

    public function getAcymAdditionalConditionFollowup(&$additionalCondition, $trigger, $followup, $statusArray)
    {
        if ($trigger == $this->purchaseTriggerName) {
            $orderStatus = acym_loadObjectList('SELECT `orderstatus_id` AS value, `orderstatus_name` AS text FROM #__hikashop_orderstatus ORDER BY `orderstatus_name`');
            $multiselectOrderStatus = acym_selectMultiple(
                $orderStatus,
                'followup[condition][order_status]',
                !empty($followup->condition) && !empty($followup->condition['order_status']) ? $followup->condition['order_status'] : [],
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


            $ajaxParams = json_encode([
                'plugin' => __CLASS__,
                'trigger' => 'searchProduct',
            ]);
            $parametersProductSelect = [
                'class' => 'acym__select acym_select2_ajax',
                'data-params' => acym_escape($ajaxParams),
                'data-selected' => !empty($followup->condition) && !empty($followup->condition['products']) ? implode(',', $followup->condition['products']) : '',
            ];
            $multiselectProducts = acym_selectMultiple(
                [],
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

            $ajaxParams = json_encode([
                'plugin' => __CLASS__,
                'trigger' => 'searchCat',
            ]);
            $parametersCategoriesSelect = [
                'class' => 'acym__select acym_select2_ajax',
                'data-params' => acym_escape($ajaxParams),
                'data-selected' => !empty($followup->condition) && !empty($followup->condition['categories']) ? implode(',', $followup->condition['categories']) : '',
            ];
            $multiselectCategories = acym_selectMultiple(
                [],
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
    }

    public function searchCat()
    {
        $ids = $this->getIdsSelectAjax();

        if (!empty($ids)) {
            $cats = acym_loadObjectList(
                'SELECT `category_id` AS id, `category_name` AS name 
				FROM #__hikashop_category 
				WHERE `category_type` = "product" 
					AND `category_id` IN ("'.implode('","', $ids).'") 
				ORDER BY `category_name`'
            );

            $value = [];
            if (!empty($cats)) {
                foreach ($cats as $cat) {
                    $value[] = ['text' => $cat->name, 'value' => $cat->id];
                }
            }
            echo json_encode($value);
            exit;
        }

        $search = acym_getVar('string', 'search', '');
        $cats = acym_loadObjectList(
            'SELECT `category_id` AS id, `category_name` AS name 
			FROM #__hikashop_category 
			WHERE `category_type` = "product" 
				AND `category_name` LIKE '.acym_escapeDB('%'.$search.'%').' 
			ORDER BY `category_name`'
        );
        $categories = [];
        foreach ($cats as $oneCat) {
            $categories[] = [$oneCat->id, $oneCat->name];
        }

        echo json_encode($categories);
        exit;
    }

    public function getFollowupConditionSummary(&$return, $condition, $trigger, $statusArray)
    {
        if ($trigger !== $this->purchaseTriggerName) return;

        if (empty($condition['order_status_status']) || empty($condition['order_status'])) {
            $return[] = acym_translation('ACYM_EVERY_ORDER_STATUS');
        } else {
            acym_arrayToInteger($condition['order_status']);
            $orderStatusToDisplay = acym_loadResultArray(
                'SELECT `orderstatus_name` 
                FROM #__hikashop_orderstatus 
                WHERE `orderstatus_id` IN ('.implode(', ', $condition['order_status']).') 
                ORDER BY `orderstatus_name`'
            );
            $return[] = acym_translationSprintf('ACYM_ORDER_STATUS_X_IN_X', strtolower($statusArray[$condition['order_status_status']]), implode(', ', $orderStatusToDisplay));
        }

        if (empty($condition['products_status']) || empty($condition['products'])) {
            $return[] = acym_translation('ACYM_ANY_PRODUCT');
        } else {
            acym_arrayToInteger($condition['products']);
            $productsToDisplay = acym_loadResultArray(
                'SELECT `product_name` 
                FROM #__hikashop_product 
                WHERE `product_id` IN ('.implode(', ', $condition['products']).') 
                ORDER BY `product_name`'
            );
            $return[] = acym_translationSprintf('ACYM_PRODUCTS_X_IN_X', strtolower($statusArray[$condition['products_status']]), implode(', ', $productsToDisplay));
        }

        if (empty($condition['categories_status']) || empty($condition['categories'])) {
            $return[] = acym_translation('ACYM_EVERY_CATEGORIES');
        } else {
            acym_arrayToInteger($condition['categories']);
            $categoriesToDisplay = acym_loadResultArray(
                'SELECT `category_name` 
                FROM #__hikashop_category 
                WHERE `category_type` = "product" AND `category_id` IN ('.implode(', ', $condition['categories']).') 
                ORDER BY `category_name`'
            );
            $return[] = acym_translationSprintf('ACYM_CATEGORIES_X_IN_X', strtolower($statusArray[$condition['categories_status']]), implode(', ', $categoriesToDisplay));
        }
    }

    public function matchFollowupsConditions(&$followups, $userId, $params)
    {
        foreach ($followups as $key => $followup) {
            if ($followup->trigger != $this->purchaseTriggerName) continue;
            //We check the order status
            if (!empty($followup->condition['order_status_status']) && !empty($followup->condition['order_status'])) {
                $status = $followup->condition['order_status_status'] == 'is';
                $inArray = in_array($params['hika_order_status'], $followup->condition['order_status']);
                if (($status && !$inArray) || (!$status && $inArray)) unset($followups[$key]);
            }

            //We check the products
            if (!empty($followup->condition['products_status']) && !empty($followup->condition['products'])) {
                $status = $followup->condition['products_status'] == 'is';
                $matchedProducts = array_intersect($params['hika_order_product_ids'], $followup->condition['products']);
                $inArray = !empty($matchedProducts);
                if (($status && !$inArray) || (!$status && $inArray)) unset($followups[$key]);
            }

            //We check the categories
            if (!empty($followup->condition['categories_status']) && !empty($followup->condition['categories'])) {
                $status = $followup->condition['categories_status'] == 'is';
                $matchedCategories = array_intersect($params['hika_order_cat_ids'], $followup->condition['categories']);
                $inArray = !empty($matchedCategories);
                if (($status && !$inArray) || (!$status && $inArray)) unset($followups[$key]);
            }
        }
    }
}
