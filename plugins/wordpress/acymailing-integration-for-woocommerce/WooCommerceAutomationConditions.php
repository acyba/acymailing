<?php

trait WooCommerceAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        $this->declareReminderFilter($conditions);
        $this->declarePurchaseFilter($conditions);
        $this->declareSubscriptionFilter($conditions);
    }

    public function onAcymDeclareConditionsScenario(&$conditions){
        $this->onAcymDeclareConditions($conditions);
    }

    private function declarePurchaseFilter(&$conditions)
    {
        $conditions['user']['woopurchased'] = new stdClass();
        $conditions['user']['woopurchased']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'WooCommerce', acym_translation('ACYM_PURCHASED'));
        $conditions['user']['woopurchased']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['woopurchased']->option .= '<div class="cell acym_vcenter shrink">'.acym_translation('ACYM_BOUGHT').'</div>';

        $conditions['user']['woopurchased']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['woopurchased']->option .= acym_selectMultiple(
            [],
            'acym_condition[conditions][__numor__][__numand__][woopurchased][product]',
            [],
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_PRODUCT'),
                'data-params' => acym_escape([
                    'plugin' => 'plgAcymWoocommerce',
                    'trigger' => 'searchProduct',
                    'variations' => true,
                ]),
            ]
        );
        $conditions['user']['woopurchased']->option .= '</div>';

        $conditions['user']['woopurchased']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['woopurchased']->option .= acym_selectMultiple(
            [],
            'acym_condition[conditions][__numor__][__numand__][woopurchased][category]',
            [],
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_CATEGORY'),
                'data-params' => acym_escape([
                    'plugin' => 'plgAcymWoocommerce',
                    'trigger' => 'searchCat',
                ]),
            ]
        );
        $conditions['user']['woopurchased']->option .= '</div>';

        $conditions['user']['woopurchased']->option .= '</div>';

        $conditions['user']['woopurchased']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['woopurchased']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woopurchased][datemin]', '', 'cell shrink');
        $conditions['user']['woopurchased']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['woopurchased']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_DATE_CREATED').'</span>';
        $conditions['user']['woopurchased']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['woopurchased']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woopurchased][datemax]', '', 'cell shrink');
        $conditions['user']['woopurchased']->option .= '</div>';

        if ($this->wcsInstalled) {
            $conditions['user']['woopurchased']->option .= '<div class="cell grid-x grid-margin-x">';
            $conditions['user']['woopurchased']->option .= '<div class="cell acym_vcenter shrink">'.acym_translation('ACYM_ORDER_TYPE').'</div>';
            $conditions['user']['woopurchased']->option .= '<span class="cell large-2 medium-6">'.acym_select(
                    $this->orderTypes,
                    'acym_condition[conditions][__numor__][__numand__][woopurchased][order_type]',
                    '',
                    ['class' => 'acym__select']
                ).'</span>';
            $conditions['user']['woopurchased']->option .= '</div>';
        }
    }

    private function declareReminderFilter(&$conditions)
    {
        $paymentMethods = ['any' => acym_translation('ACYM_ANY_PAYMENT_METHOD')];
        if (function_exists('WC')) {
            $payments = WC()->payment_gateways()->payment_gateways;
            foreach ($payments as $oneMethod) {
                $paymentMethods[$oneMethod->id] = $oneMethod->title;
            }
        }

        $conditions['user']['wooreminder'] = new stdClass();
        $conditions['user']['wooreminder']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'WooCommerce', acym_translation('ACYM_REMINDER'));
        $conditions['user']['wooreminder']->option = '<div class="cell">';
        $conditions['user']['wooreminder']->option .= acym_translationSprintf(
            'ACYM_ORDER_WITH_STATUS',
            '<input type="number" name="acym_condition[conditions][__numor__][__numand__][wooreminder][days]" value="1" min="1" class="intext_input"/>',
            '<div class="intext_select_automation cell margin-right-1">'.acym_select(
                $this->getOrderStatuses(),
                'acym_condition[conditions][__numor__][__numand__][wooreminder][status]',
                'wc-pending',
                ['class' => 'acym__select']
            ).'</div>'
        );
        $conditions['user']['wooreminder']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['wooreminder']->option .= acym_select(
            $paymentMethods,
            'acym_condition[conditions][__numor__][__numand__][wooreminder][payment]',
            'any',
            ['class' => 'acym__select']
        );
        $conditions['user']['wooreminder']->option .= '</div>';
        $conditions['user']['wooreminder']->option .= '</div>';
    }

    private function declareSubscriptionFilter(&$conditions)
    {
        if (!$this->wcsInstalled) {
            return;
        }

        $conditions['user']['woosubscription'] = new stdClass();
        $conditions['user']['woosubscription']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'WooCommerce', __('Subscription', 'woocommerce-subscriptions'));
        $conditions['user']['woosubscription']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['woosubscription']->option .= '<div class="cell shrink acym_vcenter">';
        $conditions['user']['woosubscription']->option .= acym_translation('ACYM_HAS_SUBSCRIPTION');
        $conditions['user']['woosubscription']->option .= '</div>';

        $conditions['user']['woosubscription']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['woosubscription']->option .= acym_selectMultiple(
            [],
            'acym_condition[conditions][__numor__][__numand__][woosubscription][product]',
            [],
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_PRODUCT'),
                'data-params' => acym_escape([
                    'plugin' => 'plgAcymWoocommerce',
                    'trigger' => 'searchProduct',
                    'variations' => true,
                    'onlySubscriptions' => true,
                ]),
            ]
        );
        $conditions['user']['woosubscription']->option .= '</div>';

        $conditions['user']['woosubscription']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['woosubscription']->option .= acym_selectMultiple(
            [],
            'acym_condition[conditions][__numor__][__numand__][woosubscription][category]',
            [],
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_CATEGORY'),
                'data-params' => acym_escape([
                    'plugin' => 'plgAcymWoocommerce',
                    'trigger' => 'searchCat',
                ]),
            ]
        );
        $conditions['user']['woosubscription']->option .= '</div>';

        $subscriptionStatuses = [];
        $statuses = wcs_get_subscription_statuses();
        foreach ($statuses as $status => $statusName) {
            $subscriptionStatuses[$status] = $statusName;
        }
        $conditions['user']['woosubscription']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['woosubscription']->option .= acym_selectMultiple(
            $subscriptionStatuses,
            'acym_condition[conditions][__numor__][__numand__][woosubscription][status]',
            [],
            [
                'class' => 'acym__select',
                'data-placeholder' => acym_translation('ACYM_STATUS'),
            ]
        );
        $conditions['user']['woosubscription']->option .= '</div>';

        $conditions['user']['woosubscription']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['woosubscription']->option .= acym_select(
            [
                'any' => acym_translation('ACYM_RENEWAL_TYPE'),
                'automatic' => acym_translation('ACYM_AUTO'),
                'manual' => acym_translation('ACYM_MANUAL'),
            ],
            'acym_condition[conditions][__numor__][__numand__][woosubscription][renewal_type]',
            'any',
            ['class' => 'acym__select']
        );
        $conditions['user']['woosubscription']->option .= '</div>';

        $conditions['user']['woosubscription']->option .= '</div>';

        $conditions['user']['woosubscription']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['woosubscription']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woosubscription][datemin]', '', 'cell shrink');
        $conditions['user']['woosubscription']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['woosubscription']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_START_DATE').'</span>';
        $conditions['user']['woosubscription']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['woosubscription']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woosubscription][datemax]', '', 'cell shrink');
        $conditions['user']['woosubscription']->option .= '</div>';

        $conditions['user']['woosubscription']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['woosubscription']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woosubscription][nextdatemin]', '', 'cell shrink');
        $conditions['user']['woosubscription']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['woosubscription']->option .= '<span class="acym_vcenter">'.__('Next Payment', 'woocommerce-subscriptions').'</span>';
        $conditions['user']['woosubscription']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['woosubscription']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woosubscription][nextdatemax]', '', 'cell shrink');
        $conditions['user']['woosubscription']->option .= '</div>';

        $conditions['user']['woosubscription']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['woosubscription']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woosubscription][enddatemin]', '', 'cell shrink');
        $conditions['user']['woosubscription']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['woosubscription']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_END_DATE').'</span>';
        $conditions['user']['woosubscription']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['woosubscription']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woosubscription][enddatemax]', '', 'cell shrink');
        $conditions['user']['woosubscription']->option .= '</div>';
    }

    private function getWooCategories($ids = [], $nameSearch = '')
    {
        $query = 'SELECT term.`term_id`, term.`name` 
			FROM #__terms AS term 
			JOIN #__term_taxonomy AS tax 
				ON term.`term_id` = tax.`term_id` 
			WHERE tax.`taxonomy` = "product_cat" ';
        if (!empty($ids)) {
            acym_arrayToInteger($ids);
            $query .= ' AND term.`term_id` IN ("'.implode('","', $ids).'")';
        }
        if (!empty($nameSearch)) {
            $query .= ' AND term.`name` LIKE '.acym_escapeDB('%'.$nameSearch.'%');
        }
        $query .= ' ORDER BY term.`name`';

        return acym_loadObjectList($query, 'term_id');
    }

    public function searchProduct()
    {
        $ids = $this->getIdsSelectAjax();

        if (!empty($ids)) {
            $args = [
                'post__in' => $ids,
                'post_type' => ['product', 'product_variation'],
            ];
            $posts = new WP_Query($args);

            $value = [];
            if ($posts->have_posts()) {
                foreach ($posts->get_posts() as $post) {
                    $value[] = [
                        'text' => $post->post_title,
                        'value' => $post->ID,
                    ];
                }
            }
            echo json_encode($value);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $variations = acym_getVar('boolean', 'variations', false);

        $productType = ['product'];
        if ($variations) {
            $productType[] = 'product_variation';
        }

        $onlySubscriptions = acym_getVar('boolean', 'onlySubscriptions', false);

        $search_results = new WP_Query([
            's' => $search,
            'post_status' => 'publish',
            'ignore_sticky_posts' => 1,
            'post_type' => $productType,
            'posts_per_page' => 20,
        ]);

        if ($search_results->have_posts()) {
            while ($search_results->have_posts()) {
                $search_results->the_post();
                if (!$onlySubscriptions || !empty(get_post_meta($search_results->post->ID, '_subscription_price'))) {
                    $return[] = [$search_results->post->ID, $search_results->post->post_title];
                }
            }
        }

        echo json_encode($return);
        exit;
    }

    private function processConditionFilter_woopurchased(&$query, $options, $num)
    {
        $this->prepareOptions($options);

        if ($this->isHposActive()) {
            $query->join['woopurchased_order'.$num] = '#__wc_orders AS order'.$num.' ON order'.$num.'.billing_email = user.email AND order'.$num.'.billing_email != ""';
            $query->where[] = 'order'.$num.'.type = "shop_order"';
            $query->where[] = 'order'.$num.'.status = "wc-completed"';

            if (!empty($options['datemin'])) {
                $options['datemin'] = acym_replaceDate($options['datemin']);
                if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
                if (!empty($options['datemin'])) {
                    $query->where[] = 'order'.$num.'.date_created_gmt > '.acym_escapeDB(acym_date($options['datemin'], 'Y-m-d H:i:s', false));
                }
            }
            if (!empty($options['datemax'])) {
                $options['datemax'] = acym_replaceDate($options['datemax']);
                if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
                if (!empty($options['datemax'])) {
                    $query->where[] = 'order'.$num.'.date_created_gmt < '.acym_escapeDB(acym_date($options['datemax'], 'Y-m-d H:i:s', false));
                }
            }

            if (!empty($options['product'])) {
                $query->join['woopurchased_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' ON order'.$num.'.id = woooi'.$num.'.order_id AND woooi'.$num.'.order_item_type = "line_item"';
                $query->join['woopurchased_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' 
                    ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id 
                    AND woooim'.$num.'.meta_key IN ("_product_id", "_variation_id") 
                    AND woooim'.$num.'.meta_value IN ('.implode(',', $options['product']).')';
            } elseif (!empty($options['category'])) {
                $query->join['woopurchased_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' ON order'.$num.'.id = woooi'.$num.'.order_id AND woooi'.$num.'.order_item_type = "line_item"';
                $query->join['woopurchased_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id AND woooim'.$num.'.meta_key = "_product_id"';
                $query->join['woopurchased_cat_map'.$num] = '#__term_relationships AS termrel'.$num.' ON termrel'.$num.'.object_id = woooim'.$num.'.meta_value';
                $query->join['woopurchased_cat'.$num] = '#__term_taxonomy AS termtax'.$num.' 
                    ON termtax'.$num.'.term_taxonomy_id = termrel'.$num.'.term_taxonomy_id 
                    AND termtax'.$num.'.term_id IN ('.implode(',', $options['category']).')';
            }

            if (!empty($options['order_type'])) {
                if (in_array($options['order_type'], ['original', 'regular'])) {
                    $query->leftjoin['woopurchased_meta_order_type'.$num] = '#__wc_orders_meta AS wooom'.$num.' 
                        ON wooom'.$num.'.order_id = order'.$num.'.id 
                        AND wooom'.$num.'.meta_key IN ("_subscription_renewal", "_subscription_switch")';
                    $query->where[] = 'wooom'.$num.'.id IS NULL';

                    if ($options['order_type'] === 'regular') {
                        $query->where[] = 'order'.$num.'.parent_order_id = 0 OR order'.$num.'.parent_order_id IS NULL';
                    }
                } elseif ($options['order_type'] === 'parent') {
                    $query->where[] = 'order'.$num.'.parent_order_id = 0 OR order'.$num.'.parent_order_id IS NULL';
                } else {
                    if ($options['order_type'] === 'renewal') {
                        $metaKey = '_subscription_renewal';
                    } elseif ($options['order_type'] === 'resubscribe') {
                        $metaKey = '_subscription_resubscribe';
                    } else {
                        $metaKey = '_subscription_switch';
                    }

                    $metaKey = apply_filters('woocommerce_subscriptions_admin_order_type_filter_meta_key', $metaKey, $options['order_type']);

                    $query->join['woopurchased_meta_order_type'.$num] = '#__wc_orders_meta AS wooom'.$num.' 
                        ON wooom'.$num.'.order_id = order'.$num.'.id 
                        AND wooom'.$num.'.meta_key = '.acym_escapeDB($metaKey).' 
                        AND wooom'.$num.'.meta_value != 0';
                }
            }
        } else {
            $conditions = [];
            $conditions[] = 'post'.$num.'.post_type = "shop_order"';
            $conditions[] = 'post'.$num.'.post_status = "wc-completed"';

            if (!empty($options['datemin'])) {
                $options['datemin'] = acym_replaceDate($options['datemin']);
                if (!is_numeric($options['datemin'])) $options['datemin'] = strtotime($options['datemin']);
                if (!empty($options['datemin'])) {
                    $conditions[] = 'post'.$num.'.post_date > '.acym_escapeDB(acym_date($options['datemin'], 'Y-m-d H:i:s'));
                }
            }
            if (!empty($options['datemax'])) {
                $options['datemax'] = acym_replaceDate($options['datemax']);
                if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
                if (!empty($options['datemax'])) {
                    $conditions[] = 'post'.$num.'.post_date < '.acym_escapeDB(acym_date($options['datemax'], 'Y-m-d H:i:s'));
                }
            }

            $query->join['woopurchased_post'.$num] = '#__posts AS post'.$num.' ON '.implode(' AND ', $conditions);
            $query->join['woopurchased_postmeta'.$num] = '#__postmeta AS postmeta'.$num.' ON postmeta'.$num.'.post_id = post'.$num.'.ID AND postmeta'.$num.'.meta_value = user.email AND postmeta'.$num.'.meta_value != "" AND postmeta'.$num.'.meta_key = "_billing_email"';
            $query->where[] = 'user.email != ""';

            if (!empty($options['product'])) {
                $query->join['woopurchased_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' ON post'.$num.'.ID = woooi'.$num.'.order_id AND woooi'.$num.'.order_item_type = "line_item"';
                $query->join['woopurchased_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' 
                    ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id 
                    AND woooim'.$num.'.meta_key IN ("_product_id", "_variation_id") 
                    AND woooim'.$num.'.meta_value IN ('.implode(',', $options['product']).')';
            } elseif (!empty($options['category'])) {
                $query->join['woopurchased_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' ON post'.$num.'.ID = woooi'.$num.'.order_id AND woooi'.$num.'.order_item_type = "line_item"';
                $query->join['woopurchased_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id AND woooim'.$num.'.meta_key = "_product_id"';
                $query->join['woopurchased_cat_map'.$num] = '#__term_relationships AS termrel'.$num.' ON termrel'.$num.'.object_id = woooim'.$num.'.meta_value';
                $query->join['woopurchased_cat'.$num] = '#__term_taxonomy AS termtax'.$num.' 
                    ON termtax'.$num.'.term_taxonomy_id = termrel'.$num.'.term_taxonomy_id 
                    AND termtax'.$num.'.term_id IN ('.implode(',', $options['category']).')';
            }

            if (!empty($options['order_type'])) {
                if (in_array($options['order_type'], ['original', 'regular'])) {
                    $query->leftjoin['woopurchased_meta_order_type'.$num] = '#__postmeta AS metatype'.$num.' 
                        ON metatype'.$num.'.post_id = post'.$num.'.ID 
                        AND metatype'.$num.'.meta_key IN ("_subscription_renewal", "_subscription_switch")';
                    $query->where[] = 'metatype'.$num.'.meta_id IS NULL';

                    if ($options['order_type'] === 'regular') {
                        $query->where[] = 'post'.$num.'.post_parent = 0';
                    }
                } elseif ($options['order_type'] === 'parent') {
                    $query->where[] = 'post'.$num.'.post_parent = 0';
                } else {
                    if ($options['order_type'] === 'renewal') {
                        $metaKey = '_subscription_renewal';
                    } elseif ($options['order_type'] === 'resubscribe') {
                        $metaKey = '_subscription_resubscribe';
                    } else {
                        $metaKey = '_subscription_switch';
                    }

                    $metaKey = apply_filters('woocommerce_subscriptions_admin_order_type_filter_meta_key', $metaKey, $options['order_type']);

                    $query->join['woopurchased_meta_order_type'.$num] = '#__wc_orders_meta AS metatype'.$num.' 
                        ON metatype'.$num.'.post_id = post'.$num.'.ID 
                        AND metatype'.$num.'.meta_key = '.acym_escapeDB($metaKey).' 
                        AND metatype'.$num.'.meta_value != 0';
                }
            }
        }
    }

    public function onAcymProcessCondition_woopurchased(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_woopurchased($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_wooreminder(&$query, $options, $num)
    {
        $options['days'] = intval($options['days']);

        if ($this->isHposActive()) {
            $query->join['wooreminder_order'.$num] = '#__wc_orders AS order'.$num.' ON order'.$num.'.customer_id = user.cms_id';
            $query->where[] = 'order'.$num.'.type = "shop_order"';
            $query->where[] = 'user.cms_id != 0';

            $query->where[] = 'SUBSTRING(order'.$num.'.date_created_gmt, 1, 10) = '.acym_escapeDB(acym_date(time() - ($options['days'] * 86400), 'Y-m-d', false));
            $query->where[] = 'order'.$num.'.status = '.acym_escapeDB($options['status']);

            if (!empty($options['payment']) && $options['payment'] !== 'any') {
                $query->where[] = 'order'.$num.'.payment_method = '.acym_escapeDB($options['payment']);
            }
        } else {
            $query->join['wooreminder_post'.$num] = '#__posts AS post'.$num.' ON post'.$num.'.post_type = "shop_order"';
            $query->join['wooreminder_postmeta'.$num] = '#__postmeta AS postmeta'.$num.' ON postmeta'.$num.'.post_id = post'.$num.'.ID AND postmeta'.$num.'.meta_value = user.cms_id AND postmeta'.$num.'.meta_key = "_customer_user"';
            $query->where[] = 'user.cms_id != 0';
            $query->where[] = 'SUBSTRING(post'.$num.'.post_date, 1, 10) = '.acym_escapeDB(date('Y-m-d', time() - ($options['days'] * 86400)));
            $query->where[] = 'post'.$num.'.post_status = '.acym_escapeDB($options['status']);

            if (!empty($options['payment']) && $options['payment'] !== 'any') {
                $query->join['wooreminder_postmeta'.$num] = '#__postmeta AS postmeta'.$num.' ON postmeta'.$num.'.post_id = post'.$num.'.ID';
                $query->where[] = 'postmeta'.$num.'.meta_key = "_payment_method"';
                $query->where[] = 'postmeta'.$num.'.meta_value = '.acym_escapeDB($options['payment']);
            }
        }
    }

    public function onAcymProcessCondition_wooreminder(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_wooreminder($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_woosubscription(&$query, $options, $num)
    {
        $this->prepareOptions($options);

        if ($this->isHposActive()) {
            $query->join['woosubscription_order'.$num] = '#__wc_orders AS order'.$num.' ON order'.$num.'.customer_id = user.cms_id';
            $query->where[] = 'order'.$num.'.type = "shop_subscription"';
            $query->where[] = 'user.cms_id != 0';

            if (!empty($options['status'])) {
                $query->where[] = 'order'.$num.'.status IN ('.implode(',', $options['status']).')';
            }

            // Apply condition on product / category linked to the subscription
            if (!empty($options['product'])) {
                $query->join['woosubscription_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' ON order'.$num.'.id = woooi'.$num.'.order_id AND woooi'.$num.'.order_item_type = "line_item"';
                $query->join['woosubscription_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' 
                    ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id 
                    AND woooim'.$num.'.meta_key IN ("_product_id", "_variation_id") 
                    AND woooim'.$num.'.meta_value IN ('.implode(',', $options['product']).')';
            } elseif (!empty($options['category']) && $options['category'] != 'any') {
                $query->join['woosubscription_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' ON order'.$num.'.id = woooi'.$num.'.order_id AND woooi'.$num.'.order_item_type = "line_item"';
                $query->join['woosubscription_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' 
                    ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id 
                    AND woooim'.$num.'.meta_key = "_product_id"';
                $query->join['woosubscription_cat_map'.$num] = '#__term_relationships AS termrel'.$num.' ON termrel'.$num.'.object_id = woooim'.$num.'.meta_value';
                $query->join['woosubscription_cat'.$num] = '#__term_taxonomy AS termtax'.$num.' 
                    ON termtax'.$num.'.term_taxonomy_id = termrel'.$num.'.term_taxonomy_id 
                    AND termtax'.$num.'.term_id IN ('.implode(',', $options['category']).')';
            }

            if (!empty($options['renewal_type']) && in_array($options['renewal_type'], ['automatic', 'manual'])) {
                $query->join['woosubscription_meta_renewal_type'.$num] = '#__wc_orders_meta AS wcs_renewal_type'.$num.' 
                    ON wcs_renewal_type'.$num.'.order_id = order'.$num.'.id 
                    AND wcs_renewal_type'.$num.'.meta_key = "_requires_manual_renewal"
                    AND wcs_renewal_type'.$num.'.meta_value = "'.($options['renewal_type'] === 'manual' ? 'true' : 'false').'"';
            }

            // Prepare date fields values
            $dateOptions = ['datemin', 'datemax', 'nextdatemin', 'nextdatemax', 'enddatemin', 'enddatemax'];
            foreach ($dateOptions as $oneDateOption) {
                if (empty($options[$oneDateOption])) continue;

                $options[$oneDateOption] = acym_replaceDate($options[$oneDateOption]);
                if (!is_numeric($options[$oneDateOption])) $options[$oneDateOption] = strtotime($options[$oneDateOption]);
                if (!empty($options[$oneDateOption])) {
                    $options[$oneDateOption] = acym_date($options[$oneDateOption], 'Y-m-d H:i:s', false);
                }
            }

            // Apply date conditions
            $dateOptions = [
                'date' => '_schedule_start',
                'nextdate' => '_schedule_next_payment',
                'enddate' => '_schedule_end',
            ];
            foreach ($dateOptions as $oneDateType => $metaKey) {
                if (!empty($options[$oneDateType.'min']) || !empty($options[$oneDateType.'max'])) {
                    $query->join['woosubscription_meta'.$oneDateType.$num] = '#__wc_orders_meta AS wcs_'.$oneDateType.$num.' 
					ON wcs_'.$oneDateType.$num.'.order_id = order'.$num.'.id 
					AND wcs_'.$oneDateType.$num.'.meta_value != 0 
					AND wcs_'.$oneDateType.$num.'.meta_key = '.acym_escapeDB($metaKey);

                    if (!empty($options[$oneDateType.'min'])) {
                        $query->join['woosubscription_meta'.$oneDateType.$num] .= ' AND wcs_'.$oneDateType.$num.'.meta_value > '.acym_escapeDB($options[$oneDateType.'min']);
                    }

                    if (!empty($options[$oneDateType.'max'])) {
                        $query->join['woosubscription_meta'.$oneDateType.$num] .= ' AND wcs_'.$oneDateType.$num.'.meta_value < '.acym_escapeDB($options[$oneDateType.'max']);
                    }
                }
            }
        } else {
            // Retrieve the Acy users with subscriptions
            $conditions = [];
            $conditions[] = 'post'.$num.'.post_type = "shop_subscription"';

            if (!empty($options['status'])) {
                $conditions[] = 'post'.$num.'.post_status IN ('.implode(',', $options['status']).')';
            }

            $query->join['woosubscription_post'.$num] = '#__posts AS post'.$num.' ON '.implode(' AND ', $conditions);

            $query->join['woosubscription_user'.$num] = '#__postmeta AS wcsuser'.$num.' 
                ON wcsuser'.$num.'.post_id = post'.$num.'.ID 
                AND wcsuser'.$num.'.meta_value = user.cms_id 
                AND wcsuser'.$num.'.meta_value != 0 
                AND wcsuser'.$num.'.meta_key = "_customer_user"';

            // Apply condition on product / category linked to the subscription
            if (!empty($options['product'])) {
                $query->join['woosubscription_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' 
                    ON post'.$num.'.ID = woooi'.$num.'.order_id 
                    AND woooi'.$num.'.order_item_type = "line_item"';
                $query->join['woosubscription_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' 
                    ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id 
                    AND woooim'.$num.'.meta_key IN ("_product_id", "_variation_id") 
                    AND woooim'.$num.'.meta_value IN ('.implode(',', $options['product']).')';
            } elseif (!empty($options['category']) && $options['category'] != 'any') {
                $query->join['woosubscription_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' 
                    ON post'.$num.'.ID = woooi'.$num.'.order_id 
                    AND woooi'.$num.'.order_item_type = "line_item"';
                $query->join['woosubscription_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' 
                    ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id 
                    AND woooim'.$num.'.meta_key = "_product_id"';
                $query->join['woosubscription_cat_map'.$num] = '#__term_relationships AS termrel'.$num.' ON termrel'.$num.'.object_id = woooim'.$num.'.meta_value';
                $query->join['woosubscription_cat'.$num] = '#__term_taxonomy AS termtax'.$num.' 
                    ON termtax'.$num.'.term_taxonomy_id = termrel'.$num.'.term_taxonomy_id 
                    AND termtax'.$num.'.term_id IN ('.implode(',', $options['category']).')';
            }

            if (!empty($options['renewal_type']) && in_array($options['renewal_type'], ['automatic', 'manual'])) {
                $query->join['woosubscription_meta_renewal_type'.$num] = '#__postmeta AS wcs_renewal_type'.$num.' 
				ON wcs_renewal_type'.$num.'.post_id = post'.$num.'.ID 
				AND wcs_renewal_type'.$num.'.meta_key = "_requires_manual_renewal"
				AND wcs_renewal_type'.$num.'.meta_value = "'.($options['renewal_type'] === 'manual' ? 'true' : 'false').'"';
            }

            // Prepare date fields values
            $dateOptions = ['datemin', 'datemax', 'nextdatemin', 'nextdatemax', 'enddatemin', 'enddatemax'];
            foreach ($dateOptions as $oneDateOption) {
                if (empty($options[$oneDateOption])) continue;

                $options[$oneDateOption] = acym_replaceDate($options[$oneDateOption]);
                if (!is_numeric($options[$oneDateOption])) $options[$oneDateOption] = strtotime($options[$oneDateOption]);
                if (!empty($options[$oneDateOption])) {
                    $options[$oneDateOption] = acym_date($options[$oneDateOption], 'Y-m-d H:i:s', false);
                }
            }

            // Apply date conditions
            $dateOptions = [
                'date' => '_schedule_start',
                'nextdate' => '_schedule_next_payment',
                'enddate' => '_schedule_end',
            ];
            foreach ($dateOptions as $oneDateType => $metaKey) {
                if (!empty($options[$oneDateType.'min']) || !empty($options[$oneDateType.'max'])) {
                    $query->join['woosubscription_meta'.$oneDateType.$num] = '#__postmeta AS wcs_'.$oneDateType.$num.' 
					ON wcs_'.$oneDateType.$num.'.post_id = post'.$num.'.ID 
					AND wcs_'.$oneDateType.$num.'.meta_value != 0 
					AND wcs_'.$oneDateType.$num.'.meta_key = '.acym_escapeDB($metaKey);

                    if (!empty($options[$oneDateType.'min'])) {
                        $query->join['woosubscription_meta'.$oneDateType.$num] .= ' AND wcs_'.$oneDateType.$num.'.meta_value > '.acym_escapeDB($options[$oneDateType.'min']);
                    }

                    if (!empty($options[$oneDateType.'max'])) {
                        $query->join['woosubscription_meta'.$oneDateType.$num] .= ' AND wcs_'.$oneDateType.$num.'.meta_value < '.acym_escapeDB($options[$oneDateType.'max']);
                    }
                }
            }
        }
    }

    public function onAcymProcessCondition_woosubscription(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_woosubscription($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        $this->handleReminderSummary($automationCondition);
        $this->handlePurchaseSummary($automationCondition);
        $this->handleSubscriptionSummary($automationCondition);
    }

    private function handleReminderSummary(&$automationCondition)
    {
        if (empty($automationCondition['wooreminder'])) {
            return;
        }

        $paymentMethods = ['any' => acym_translation('ACYM_ANY_PAYMENT_METHOD')];
        if (function_exists('WC')) {
            $payments = WC()->payment_gateways()->payment_gateways;
            foreach ($payments as $oneMethod) {
                $paymentMethods[$oneMethod->id] = $oneMethod->title;
            }
        }

        $orderStatus = $this->getOrderStatuses();

        $automationCondition = acym_translationSprintf(
            'ACYM_CONDITION_ECOMMERCE_REMINDER',
            $paymentMethods[$automationCondition['wooreminder']['payment']],
            intval($automationCondition['wooreminder']['days']),
            $orderStatus[$automationCondition['wooreminder']['status']]
        );
    }

    private function handlePurchaseSummary(&$automationCondition)
    {
        if (empty($automationCondition['woopurchased'])) {
            return;
        }

        if (empty($automationCondition['woopurchased']['product'])) {
            $products = acym_translation('ACYM_AT_LEAST_ONE_PRODUCT');
        } else {
            if (!is_array($automationCondition['woopurchased']['product'])) {
                $automationCondition['woopurchased']['product'] = [$automationCondition['woopurchased']['product']];
            }
            $products = [];
            foreach ($automationCondition['woopurchased']['product'] as $productId) {
                $product = get_post($productId);
                $products[] = empty($product->post_title) ? acym_translation('ACYM_UNKNOWN') : $product->post_title;
            }
            $products = implode(', ', $products);
        }

        if (empty($automationCondition['woopurchased']['category']) || $automationCondition['woopurchased']['category'] === 'any') {
            $categories = acym_translation('ACYM_ANY_CATEGORY');
        } else {
            $cats = $this->getWooCategories();
            if (!is_array($automationCondition['woopurchased']['category'])) {
                $automationCondition['woopurchased']['category'] = [$automationCondition['woopurchased']['category']];
            }
            $categories = [];
            foreach ($automationCondition['woopurchased']['category'] as $categoryId) {
                $categories[] = empty($cats[$categoryId]->name) ? acym_translation('ACYM_UNKNOWN') : $cats[$categoryId]->name;
            }
            $categories = implode(', ', $categories);
        }

        $finalText = acym_translationSprintf('ACYM_CONDITION_PURCHASED', $products, $categories);

        $dates = [];
        if (!empty($automationCondition['woopurchased']['datemin'])) {
            $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['woopurchased']['datemin'], true);
        }

        if (!empty($automationCondition['woopurchased']['datemax'])) {
            $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['woopurchased']['datemax'], true);
        }

        if (!empty($dates)) {
            $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
        }

        if ($this->wcsInstalled && !empty($automationCondition['woopurchased']['order_type']) && !empty($this->orderTypes[$automationCondition['woopurchased']['order_type']])) {
            $finalText .= '<br/>'.acym_translation('ACYM_ORDER_TYPE').': '.$this->orderTypes[$automationCondition['woopurchased']['order_type']];
        }

        $automationCondition = $finalText;
    }

    private function handleSubscriptionSummary(&$automationCondition)
    {
        if (empty($automationCondition['woosubscription'])) {
            return;
        }

        if (empty($automationCondition['woosubscription']['product'])) {
            $products = acym_translation('ACYM_AT_LEAST_ONE_PRODUCT');
        } else {
            if (!is_array($automationCondition['woosubscription']['product'])) {
                $automationCondition['woosubscription']['product'] = [$automationCondition['woosubscription']['product']];
            }
            $products = [];
            foreach ($automationCondition['woosubscription']['product'] as $productId) {
                $product = get_post($productId);
                $products[] = empty($product->post_title) ? acym_translation('ACYM_UNKNOWN') : $product->post_title;
            }
            $products = implode(', ', $products);
        }

        if (empty($automationCondition['woosubscription']['category']) || $automationCondition['woosubscription']['category'] === 'any') {
            $categories = acym_translation('ACYM_ANY_CATEGORY');
        } else {
            $cats = $this->getWooCategories();
            if (!is_array($automationCondition['woosubscription']['category'])) {
                $automationCondition['woosubscription']['category'] = [$automationCondition['woosubscription']['category']];
            }
            $categories = [];
            foreach ($automationCondition['woosubscription']['category'] as $categoryId) {
                $categories[] = empty($cats[$categoryId]->name) ? acym_translation('ACYM_UNKNOWN') : $cats[$categoryId]->name;
            }
            $categories = implode(', ', $categories);
        }

        $finalText = acym_translationSprintf('ACYM_HAS_SUBSCRIPTION_SUMMARY', $products, $categories);

        if (!empty($automationCondition['woosubscription']['status']) && $automationCondition['woosubscription']['status'] !== 'any') {
            $allStatuses = wcs_get_subscription_statuses();
            if (!is_array($automationCondition['woosubscription']['status'])) {
                $automationCondition['woosubscription']['status'] = [$automationCondition['woosubscription']['status']];
            }
            $statuses = [];
            foreach ($automationCondition['woosubscription']['status'] as $statusId) {
                $statuses[] = $allStatuses[$statusId] ?? acym_translation('ACYM_UNKNOWN');
            }
            $finalText .= '<br/>'.acym_translation('ACYM_SUBSCRIPTION_STATUS').' : '.implode(', ', $statuses);
        }

        $dateOptions = [
            'date' => acym_translation('ACYM_START_DATE'),
            'nextdate' => __('Next Payment', 'woocommerce-subscriptions'),
            'enddate' => acym_translation('ACYM_END_DATE'),
        ];
        foreach ($dateOptions as $oneDateOption => $dateLabel) {
            $dates = [];
            if (!empty($automationCondition['woosubscription'][$oneDateOption.'min'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['woosubscription'][$oneDateOption.'min'], true);
            }

            if (!empty($automationCondition['woosubscription'][$oneDateOption.'max'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['woosubscription'][$oneDateOption.'max'], true);
            }

            if (!empty($dates)) {
                $finalText .= '<br/>'.$dateLabel.' : '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }
        }

        $automationCondition = $finalText;
    }

    private function getOrderStatuses(bool $withDefaultStatus = false): array
    {
        if (!function_exists('wc_get_order_statuses')) {
            return [];
        }

        $orderStatuses = [];
        if ($withDefaultStatus) {
            $orderStatuses[0] = acym_translation('ACYM_ANY_STATUS');
        }

        // Get all order statuses from WooCommerce (natives and from other plugins)
        $allWooCommerceOrderStatuses = wc_get_order_statuses();

        return array_merge($orderStatuses, $allWooCommerceOrderStatuses);
    }

    private function prepareOptions(array &$options)
    {
        // The product option was previously a select, and is now a multiselect
        if (!empty($options['product'])) {
            if (!is_array($options['product'])) {
                $options['product'] = [$options['product']];
            }

            acym_arrayToInteger($options['product']);
        }

        // The category option was previously a select, and is now a multiselect
        if (!empty($options['category'])) {
            if (!is_array($options['category'])) {
                $options['category'] = $options['category'] === 'any' ? [] : [$options['category']];
            }

            acym_arrayToInteger($options['category']);
        }

        // The status option was previously a select, and is now a multiselect
        if (!empty($options['status'])) {
            if (!is_array($options['status'])) {
                $options['status'] = $options['status'] === 'any' ? [] : [$options['status']];
            }

            $options['status'] = array_map('acym_escapeDB', $options['status']);
        }
    }
}
