<?php

use AcyMailing\Types\DelayType;

trait HikashopAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        $categories = [
            'any' => acym_translation('ACYM_ANY_CATEGORY'),
        ];
        $cats = acym_loadObjectList('SELECT `category_id`, `category_name` FROM #__hikashop_category WHERE `category_type` = "product" ORDER BY `category_name`');
        foreach ($cats as $oneCat) {
            $categories[$oneCat->category_id] = $oneCat->category_name;
        }

        $conditions['user']['hikapurchased'] = new stdClass();
        $conditions['user']['hikapurchased']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'HikaShop', acym_translation('ACYM_PURCHASED'));
        $conditions['user']['hikapurchased']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['hikapurchased']->option .= '<div class="cell acym_vcenter shrink">'.acym_translation('ACYM_BOUGHT').'</div>';

        $conditions['user']['hikapurchased']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode([
            'plugin' => __CLASS__,
            'trigger' => 'searchProduct',
        ]);
        $conditions['user']['hikapurchased']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][hikapurchased][product]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_AT_LEAST_ONE_PRODUCT'),
                'data-params' => $ajaxParams,
            ]
        );
        $conditions['user']['hikapurchased']->option .= '</div>';

        $conditions['user']['hikapurchased']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['hikapurchased']->option .= acym_select(
            $categories,
            'acym_condition[conditions][__numor__][__numand__][hikapurchased][category]',
            'any',
            ['class' => 'acym__select']
        );
        $conditions['user']['hikapurchased']->option .= '</div>';

        // Filter on vendor
        if (acym_isExtensionActive('com_hikamarket')) {
            $conditions['user']['hikapurchased']->option .= '<div class="cell acym_vcenter shrink">'.acym_translation('ACYM_FROM').'</div>';
            $conditions['user']['hikapurchased']->option .= '<div class="intext_select_automation cell">';
            $ajaxParams = json_encode([
                'plugin' => __CLASS__,
                'trigger' => 'searchVendor',
            ]);
            $conditions['user']['hikapurchased']->option .= acym_select(
                [],
                'acym_condition[conditions][__numor__][__numand__][hikapurchased][vendor]',
                null,
                [
                    'class' => 'acym__select acym_select2_ajax',
                    'data-placeholder' => acym_translation('ACYM_ANY_VENDOR'),
                    'data-params' => $ajaxParams,
                ]
            );
            $conditions['user']['hikapurchased']->option .= '</div>';
        }

        $conditions['user']['hikapurchased']->option .= '</div>';

        $conditions['user']['hikapurchased']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['hikapurchased']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][hikapurchased][datemin]', '', 'cell shrink');
        $conditions['user']['hikapurchased']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['hikapurchased']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_DATE_CREATED').'</span>';
        $conditions['user']['hikapurchased']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['hikapurchased']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][hikapurchased][datemax]', '', 'cell shrink');
        $conditions['user']['hikapurchased']->option .= '</div>';


        $orderStatuses = acym_loadObjectList('SELECT `orderstatus_id` AS value, `orderstatus_name` AS text FROM #__hikashop_orderstatus ORDER BY `orderstatus_name`');

        $paymentMethods = ['any' => acym_translation('ACYM_ANY_PAYMENT_METHOD')];
        $payments = acym_loadObjectList('SELECT `payment_id`, `payment_name` FROM #__hikashop_payment ORDER BY `payment_name`');
        foreach ($payments as $oneMethod) {
            $paymentMethods[$oneMethod->payment_id] = $oneMethod->payment_name;
        }

        $delayType = new DelayType();
        $conditions['user']['hikareminder'] = new stdClass();
        $conditions['user']['hikareminder']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'HikaShop', acym_translation('ACYM_REMINDER'));
        $conditions['user']['hikareminder']->option = '<div class="cell">';
        $conditions['user']['hikareminder']->option .= acym_translationSprintf(
            'ACYM_ORDER_WITH_STATUS',
            $delayType->display('acym_condition[conditions][__numor__][__numand__][hikareminder][days]', 1, 1, '__numor____numand__'),
            '<div class="intext_select_automation cell margin-right-1">'.acym_select(
                $orderStatuses,
                'acym_condition[conditions][__numor__][__numand__][hikareminder][status]',
                null,
                ['class' => 'acym__select']
            ).'</div>'
        );
        $conditions['user']['hikareminder']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['hikareminder']->option .= acym_select(
            $paymentMethods,
            'acym_condition[conditions][__numor__][__numand__][hikareminder][payment]',
            'any',
            ['class' => 'acym__select']
        );
        $conditions['user']['hikareminder']->option .= '</div>';
        $conditions['user']['hikareminder']->option .= '</div>';

        $conditions['user']['hikawishlist'] = new stdClass();
        $conditions['user']['hikawishlist']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'HikaShop', acym_translation('ACYM_WISHLIST'));
        $conditions['user']['hikawishlist']->option = '<div class="intext_select_automation cell">';
        $conditions['user']['hikawishlist']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][hikawishlist][product]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_ANY_PRODUCT'),
                'data-params' => $ajaxParams,
            ]
        );
        $conditions['user']['hikawishlist']->option .= '</div>';
        $conditions['user']['hikawishlist']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['hikawishlist']->option .= acym_select(
            $categories,
            'acym_condition[conditions][__numor__][__numand__][hikawishlist][category]',
            'any',
            ['class' => 'acym__select']
        );
        $conditions['user']['hikawishlist']->option .= '</div>';
    }

    /**
     * Function called with ajax to search in products
     */
    public function searchProduct()
    {
        $ids = $this->getIdsSelectAjax();

        if (!empty($ids)) {
            $value = '';
            $elements = acym_loadObjectList('SELECT `product_name` AS name, `product_id` AS id FROM #__hikashop_product WHERE `product_id` IN ("'.implode('","', $ids).'")');

            $value = [];
            if (!empty($elements)) {
                foreach ($elements as $element) {
                    $value[] = [
                        'text' => $element->name,
                        'value' => $element->id,
                    ];
                }
            }
            echo json_encode($value);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT `product_id`, `product_name` FROM `#__hikashop_product` WHERE `product_name` LIKE '.acym_escapeDB('%'.$search.'%').' ORDER BY `product_name`'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->product_id, $oneElement->product_name];
        }

        echo json_encode($return);
        exit;
    }

    public function searchVendor()
    {
        $ids = $this->getIdsSelectAjax();
        if (!empty($ids)) {
            $value = '';
            $elements = acym_loadObjectList('SELECT `vendor_name` AS name, `vendor_id` AS id FROM #__hikamarket_vendor WHERE `vendor_id` IN ("'.implode('","', $ids).'")');

            $value = [];
            if (!empty($elements)) {
                foreach ($elements as $element) {
                    $value[] = [
                        'text' => $element->name,
                        'value' => $element->id,
                    ];
                }
            }
            echo json_encode($value);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $elements = acym_loadObjectList(
            'SELECT `vendor_id`, `vendor_name` FROM `#__hikamarket_vendor` WHERE `vendor_name` LIKE '.acym_escapeDB('%'.$search.'%').' ORDER BY `vendor_name`'
        );

        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->vendor_id, $oneElement->vendor_name];
        }

        echo json_encode($return);
        exit;
    }

    public function onAcymProcessCondition_hikapurchased(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_hikapurchased($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_hikapurchased(&$query, $options, $num)
    {
        $query->join['hikapurchased_hika_user'.$num] = '#__hikashop_user AS hika_user'.$num.' ON user.email = hika_user'.$num.'.user_email';
        $query->join['hikapurchased_order'.$num] = '#__hikashop_order AS order'.$num.' ON hika_user'.$num.'.user_id = order'.$num.'.order_user_id';

        $query->where[] = 'order'.$num.'.order_user_id != 0';
        $query->where[] = 'order'.$num.'.order_type = "sale"';
        $query->where[] = 'order'.$num.'.order_status = "confirmed"';

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
            if (!empty($options['datemin'])) {
                $query->where[] = 'order'.$num.'.order_created > '.acym_escapeDB($options['datemin']);
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $query->where[] = 'order'.$num.'.order_created < '.acym_escapeDB($options['datemax']);
            }
        }

        if (!empty($options['product'])) {
            $query->join['hikapurchased_order_product'.$num] = '#__hikashop_order_product AS hikaop'.$num.' ON order'.$num.'.order_id = hikaop'.$num.'.order_id';
            $query->where[] = 'hikaop'.$num.'.product_id = '.intval($options['product']);
        } elseif (!empty($options['category']) && $options['category'] !== 'any') {
            $query->join['hikapurchased_order_product'.$num] = '#__hikashop_order_product AS hikaop'.$num.' ON order'.$num.'.order_id = hikaop'.$num.'.order_id';
            $query->join['hikapurchased_product'.$num] = '#__hikashop_product AS hikap'.$num.' ON hikaop'.$num.'.product_id = hikap'.$num.'.product_id';
            $query->join['hikapurchased_order_cat'.$num] = '#__hikashop_product_category AS hikapc'.$num.' ON hikap'.$num.'.product_id = hikapc'.$num.'.product_id OR hikap'.$num.'.product_parent_id = hikapc'.$num.'.product_id';
            $query->where[] = 'hikapc'.$num.'.category_id = '.intval($options['category']);
        }

        // Filter on the vendor (Hikamarket)
        // Don't applly if there is a filter on a product
        if (acym_isExtensionActive('com_hikamarket') && empty($options['product']) && !empty($options['vendor'])) {
            if (empty($query->join['hikapurchased_order_product'.$num])) {
                $query->join['hikapurchased_order_product'.$num] = '#__hikashop_order_product AS hikaop'.$num.' ON order'.$num.'.order_id = hikaop'.$num.'.order_id';
            }
            $query->join['hikapurchased_product'.$num] = '#__hikashop_product AS hikap'.$num.' ON hikaop'.$num.'.product_id = hikap'.$num.'.product_id';
            $query->where[] = 'hikap'.$num.'.product_vendor_id = '.(int)$options['vendor'];
        }
    }

    public function onAcymProcessCondition_hikareminder(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_hikareminder($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_hikareminder(&$query, $options, $num)
    {
        if (!include_once rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php') return;

        $options['days'] = intval($options['days']);

        if (version_compare(HIKASHOP_VERSION, '4.0.0', '>=')) {
            $orderStatuses = acym_loadObjectList('SELECT `orderstatus_id` AS id,  `orderstatus_namekey` AS name FROM #__hikashop_orderstatus', 'id');
            $orderStatus = $orderStatuses[$options['status']]->name;
        } else {
            $orderStatus = $options['status'];
        }
        $query->join['hikareminder_hika_user'.$num] = '#__hikashop_user AS hika_user'.$num.' ON user.email = hika_user'.$num.'.user_email';
        $query->join['hikareminder_order'.$num] = '#__hikashop_order AS order'.$num.' ON order'.$num.'.order_user_id = hika_user'.$num.'.user_id';

        $query->where[] = 'order'.$num.'.order_user_id != 0';
        $query->where[] = 'order'.$num.'.order_type = "sale"';
        $query->where[] = 'order'.$num.'.order_status = '.acym_escapeDB($orderStatus);

        $query->where[] = 'FROM_UNIXTIME(order'.$num.'.order_created, "%Y-%m-%d") = '.acym_escapeDB(date('Y-m-d', time() - $options['days']));

        if (!empty($options['payment']) && $options['payment'] != 'any') {
            $query->where[] = 'order'.$num.'.order_payment_id = '.intval($options['payment']);
        }
    }

    public function onAcymProcessCondition_hikawishlist(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_hikawishlist($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_hikawishlist(&$query, $options, $num)
    {
        if (!include_once rtrim(JPATH_ADMINISTRATOR, DS).DS.'components'.DS.'com_hikashop'.DS.'helpers'.DS.'helper.php') return;

        $query->join['hikawishlist_hika_user'.$num] = '#__hikashop_user AS hika_user'.$num.' ON user.email = hika_user'.$num.'.user_email';
        $query->join['hikawishlist_cart'.$num] = '#__hikashop_cart AS hika_cart'.$num.' ON hika_user'.$num.'.user_id = hika_cart'.$num.'.user_id';

        $query->where[] = 'hika_cart'.$num.'.cart_type = "wishlist"';

        if (!empty($options['product'])) {
            $query->join['hikawishlist_cart_product'.$num] = '#__hikashop_cart_product AS hika_cart_product'.$num.' ON hika_cart'.$num.'.cart_id = hika_cart_product'.$num.'.cart_id';
            $query->where[] = 'hika_cart_product'.$num.'.product_id = '.intval($options['product']);
        } elseif (!empty($options['category']) && $options['category'] !== 'any') {
            $query->join['hikawishlist_cart_product'.$num] = '#__hikashop_cart_product AS hika_cart_product'.$num.' ON hika_cart'.$num.'.cart_id = hika_cart_product'.$num.'.cart_id';
            $query->join['hikawishlist_cat'.$num] = '#__hikashop_product_category AS hikapc'.$num.' ON hika_cart_product'.$num.'.product_id = hikapc'.$num.'.product_id';
            $query->where[] = 'hikapc'.$num.'.category_id = '.intval($options['category']);
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        if (!empty($automationCondition['hikapurchased'])) {
            if (empty($automationCondition['hikapurchased']['product'])) {
                $product = acym_translation('ACYM_AT_LEAST_ONE_PRODUCT');
            } else {
                $product = acym_loadResult('SELECT `product_name` FROM #__hikashop_product WHERE `product_id` = '.intval($automationCondition['hikapurchased']['product']));
            }

            $cats = acym_loadObjectList('SELECT `category_id`, `category_name` FROM #__hikashop_category WHERE `category_type` = "product"', 'category_id');
            $category = empty($cats[$automationCondition['hikapurchased']['category']]) ? acym_translation(
                'ACYM_ANY_CATEGORY'
            ) : $cats[$automationCondition['hikapurchased']['category']]->category_name;

            $finalText = acym_translationSprintf('ACYM_CONDITION_PURCHASED', $product, $category);

            if (acym_isExtensionActive('com_hikamarket') && empty($automationCondition['hikapurchased']['product'])) {
                $finalText .= ' '.acym_translation('ACYM_FROM').' ';
                if (empty($automationCondition['hikapurchased']['vendor'])) {
                    $finalText .= acym_translation('ACYM_ANY_VENDOR');
                } else {
                    $vendorName = acym_loadResult('SELECT vendor_name FROM #__hikamarket_vendor WHERE vendor_id = '.(int)$automationCondition['hikapurchased']['vendor']);
                    $finalText .= $vendorName;
                }
            }

            $dates = [];
            if (!empty($automationCondition['hikapurchased']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['hikapurchased']['datemin'], true);
            }

            if (!empty($automationCondition['hikapurchased']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['hikapurchased']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }

        if (!empty($automationCondition['hikareminder'])) {

            $orderStatuses = acym_loadObjectList('SELECT `orderstatus_id`, `orderstatus_name` FROM #__hikashop_orderstatus', 'orderstatus_id');
            $paymentMethods = acym_loadObjectList('SELECT `payment_id`, `payment_name` FROM #__hikashop_payment', 'payment_id');

            $delayType = new DelayType();
            $delay = $delayType->get((int)$automationCondition['hikareminder']['days'], 1);

            $paymentName = @$paymentMethods[$automationCondition['hikareminder']['payment']]->payment_name;
            if (empty($paymentName)) $paymentName = 'ACYM_ANY_PAYMENT_METHOD';
            $automationCondition = acym_translationSprintf(
                'ACYM_CONDITION_HIKASHOP_REMINDER',
                acym_translation($paymentName),
                $delay->value,
                strtolower($delay->typeText),
                $orderStatuses[$automationCondition['hikareminder']['status']]->orderstatus_name
            );
        }

        if (!empty($automationCondition['hikawishlist'])) {
            if (!(empty($automationCondition['hikawishlist']['product']))) {
                $product = acym_loadResult('SELECT `product_name` FROM #__hikashop_product WHERE product_id = '.acym_escapeDB($automationCondition['hikawishlist']['product']));
            }
            if (!(empty($automationCondition['hikawishlist']['category'])) && $automationCondition['hikawishlist']['category'] !== 'any') {
                $cats = acym_loadObjectList('SELECT `category_id`, `category_name` FROM #__hikashop_category WHERE `category_type` = "product"', 'category_id');
            }
            $category = empty($cats[$automationCondition['hikawishlist']['category']]) ? acym_translation(
                'ACYM_ANY_CATEGORY'
            ) : $cats[$automationCondition['hikawishlist']['category']]->category_name;

            $automationCondition = acym_translationSprintf(
                'ACYM_WISH_LISTED',
                !empty($product) ? $product : acym_translation('ACYM_ANY_PRODUCT'),
                $category
            );
        }
    }
}
