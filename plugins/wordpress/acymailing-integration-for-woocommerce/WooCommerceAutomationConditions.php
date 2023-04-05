<?php

trait WooCommerceAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        $categories = [
            'any' => acym_translation('ACYM_ANY_CATEGORY'),
        ];
        $cats = $this->getWooCategories();
        foreach ($cats as $oneCat) {
            $categories[$oneCat->term_id] = $oneCat->name;
        }

        $conditions['user']['woopurchased'] = new stdClass();
        $conditions['user']['woopurchased']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'WooCommerce', acym_translation('ACYM_PURCHASED'));
        $conditions['user']['woopurchased']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['woopurchased']->option .= '<div class="cell acym_vcenter shrink">'.acym_translation('ACYM_BOUGHT').'</div>';

        $conditions['user']['woopurchased']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode([
            'plugin' => 'plgAcymWoocommerce',
            'trigger' => 'searchProduct',
        ]);
        $conditions['user']['woopurchased']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][woopurchased][product]',
            null,
            'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_AT_LEAST_ONE_PRODUCT', true).'" data-params="'.acym_escape($ajaxParams).'"'
        );
        $conditions['user']['woopurchased']->option .= '</div>';

        $conditions['user']['woopurchased']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['woopurchased']->option .= acym_select(
            $categories,
            'acym_condition[conditions][__numor__][__numand__][woopurchased][category]',
            'any',
            'class="acym__select"'
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
                'class="acym__select"'
            ).'</div>'
        );
        $conditions['user']['wooreminder']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['wooreminder']->option .= acym_select(
            $paymentMethods,
            'acym_condition[conditions][__numor__][__numand__][wooreminder][payment]',
            'any',
            'class="acym__select"'
        );
        $conditions['user']['wooreminder']->option .= '</div>';
        $conditions['user']['wooreminder']->option .= '</div>';

        // WooCommerce Subscriptions filter
        if (acym_isExtensionActive('woocommerce-subscriptions/woocommerce-subscriptions.php')) {
            $conditions['user']['woosubscription'] = new stdClass();
            $conditions['user']['woosubscription']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'WooCommerce', __('Subscription', 'woocommerce-subscriptions'));
            $conditions['user']['woosubscription']->option = '<div class="cell grid-x grid-margin-x">';

            $conditions['user']['woosubscription']->option .= '<div class="cell shrink acym_vcenter">';
            $conditions['user']['woosubscription']->option .= acym_translation('ACYM_HAS_SUBSCRIPTION');
            $conditions['user']['woosubscription']->option .= '</div>';

            $conditions['user']['woosubscription']->option .= '<div class="intext_select_automation cell">';
            $ajaxParams = json_encode([
                'plugin' => 'plgAcymWoocommerce',
                'trigger' => 'searchProduct',
                'variations' => true,
            ]);
            $conditions['user']['woosubscription']->option .= acym_select(
                [],
                'acym_condition[conditions][__numor__][__numand__][woosubscription][product]',
                null,
                'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_ANY_PRODUCT', true).'" data-params="'.acym_escape($ajaxParams).'"'
            );
            $conditions['user']['woosubscription']->option .= '</div>';

            $conditions['user']['woosubscription']->option .= '<div class="intext_select_automation cell">';
            $conditions['user']['woosubscription']->option .= acym_select(
                $categories,
                'acym_condition[conditions][__numor__][__numand__][woosubscription][category]',
                'any',
                'class="acym__select"'
            );
            $conditions['user']['woosubscription']->option .= '</div>';

            $subscriptionStatuses = [
                'any' => acym_translation('ACYM_SUBSCRIPTION_STATUS'),
            ];
            $statuses = wcs_get_subscription_statuses();
            foreach ($statuses as $status => $statusName) {
                $subscriptionStatuses[$status] = $statusName;
            }
            $conditions['user']['woosubscription']->option .= '<div class="intext_select_automation cell">';
            $conditions['user']['woosubscription']->option .= acym_select(
                $subscriptionStatuses,
                'acym_condition[conditions][__numor__][__numand__][woosubscription][status]',
                'any',
                'class="acym__select"'
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
                'class="acym__select"'
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
                'post_type' => 'product',
            ];
            $posts = new WP_Query($args);

            $value = [];
            if ($posts->have_posts()) {
                foreach ($posts->get_posts() as $post) {
                    $value[] = ['text' => $post->post_title, 'value' => $post->ID];
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
                $return[] = [$search_results->post->ID, $search_results->post->post_title];
            }
        }

        echo json_encode($return);
        exit;
    }

    private function processConditionFilter_woopurchased(&$query, $options, $num)
    {
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

        $query->join['woopurchased_postmeta'.$num] = '#__postmeta AS postmeta'.$num.' ON postmeta'.$num.'.post_id = post'.$num.'.ID AND postmeta'.$num.'.meta_value = user.cms_id AND postmeta'.$num.'.meta_value != 0 AND postmeta'.$num.'.meta_key = "_customer_user"';

        if (!empty($options['product'])) {
            $query->join['woopurchased_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' ON post'.$num.'.ID = woooi'.$num.'.order_id AND woooi'.$num.'.order_item_type = "line_item"';
            $query->join['woopurchased_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id AND woooim'.$num.'.meta_key = "_product_id" AND woooim'.$num.'.meta_value = '.intval(
                    $options['product']
                );
        } elseif (!empty($options['category']) && $options['category'] != 'any') {
            $query->join['woopurchased_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' ON post'.$num.'.ID = woooi'.$num.'.order_id AND woooi'.$num.'.order_item_type = "line_item"';
            $query->join['woopurchased_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id AND woooim'.$num.'.meta_key = "_product_id"';
            $query->join['woopurchased_cat_map'.$num] = '#__term_relationships AS termrel'.$num.' ON termrel'.$num.'.object_id = woooim'.$num.'.meta_value';
            $query->join['woopurchased_cat'.$num] = '#__term_taxonomy AS termtax'.$num.' ON termtax'.$num.'.term_taxonomy_id = termrel'.$num.'.term_taxonomy_id AND termtax'.$num.'.term_id = '.intval(
                    $options['category']
                );
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

        $query->join['wooreminder_post'.$num] = '#__posts AS post'.$num.' ON post'.$num.'.post_type = "shop_order"';
        $query->join['wooreminder_postmeta'.$num] = '#__postmeta AS postmeta'.$num.' ON postmeta'.$num.'.post_id = post'.$num.'.ID AND postmeta'.$num.'.meta_value = user.cms_id AND postmeta'.$num.'.meta_key = "_customer_user"';
        $query->where[] = 'user.cms_id != 0';
        $query->where[] = 'SUBSTRING(post'.$num.'.post_date, 1, 10) = '.acym_escapeDB(date('Y-m-d', time() - ($options['days'] * 86400)));
        $query->where[] = 'post'.$num.'.post_status = '.acym_escapeDB($options['status']);

        if (!empty($options['payment']) && $options['payment'] != 'any') {
            $query->join['wooreminder_postmeta'.$num] = '#__postmeta AS postmeta'.$num.' ON postmeta'.$num.'.post_id = post'.$num.'.ID';
            $query->where[] = 'postmeta'.$num.'.meta_key = "_payment_method"';
            $query->where[] = 'postmeta'.$num.'.meta_value = '.acym_escapeDB($options['payment']);
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
        // Retrieve the Acy users with subscriptions
        $conditions = [];
        $conditions[] = 'post'.$num.'.post_type = "shop_subscription"';

        $statuses = wcs_get_subscription_statuses();
        if (!empty($options['status']) && in_array($options['status'], array_keys($statuses))) {
            $conditions[] = 'post'.$num.'.post_status = '.acym_escapeDB($options['status']);
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
            	AND woooim'.$num.'.meta_value = '.intval($options['product']);
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
            	AND termtax'.$num.'.term_id = '.intval($options['category']);
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
        if (!empty($automationCondition['wooreminder'])) {

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

        if (!empty($automationCondition['woopurchased'])) {

            if (empty($automationCondition['woopurchased']['product'])) {
                $product = acym_translation('ACYM_AT_LEAST_ONE_PRODUCT');
            } else {
                $product = get_post($automationCondition['woopurchased']['product']);
                $product = $product->post_title;
            }

            $cats = $this->getWooCategories();
            if (empty($cats[$automationCondition['woopurchased']['category']])) {
                $category = acym_translation('ACYM_ANY_CATEGORY');
            } else {
                $category = $cats[$automationCondition['woopurchased']['category']]->name;
            }

            $finalText = acym_translationSprintf('ACYM_CONDITION_PURCHASED', $product, $category);

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

            $automationCondition = $finalText;
        }

        if (!empty($automationCondition['woosubscription'])) {

            if (empty($automationCondition['woosubscription']['product'])) {
                $product = acym_translation('ACYM_AT_LEAST_ONE_PRODUCT');
            } else {
                $product = get_post($automationCondition['woosubscription']['product']);
                $product = $product->post_title;
            }

            $cats = $this->getWooCategories();
            if (empty($cats[$automationCondition['woosubscription']['category']])) {
                $category = acym_translation('ACYM_ANY_CATEGORY');
            } else {
                $category = $cats[$automationCondition['woosubscription']['category']]->name;
            }

            $finalText = acym_translationSprintf('ACYM_HAS_SUBSCRIPTION_SUMMARY', $product, $category);
            if (!empty($automationCondition['woosubscription']['status']) && $automationCondition['woosubscription']['status'] !== 'any') {
                $statuses = wcs_get_subscription_statuses();
                if (in_array($automationCondition['woosubscription']['status'], array_keys($statuses))) {
                    $finalText .= '<br/>'.acym_translation('ACYM_SUBSCRIPTION_STATUS').' : '.$statuses[$automationCondition['woosubscription']['status']];
                }
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
    }

    private function getOrderStatuses($withDefaultStatus = false)
    {
        if (!function_exists('wc_get_order_statuses')) return [];

        $orderStatuses = [];
        if ($withDefaultStatus) $orderStatuses[0] = acym_translation('ACYM_ANY_STATUS');

        // Get all order statuses from WooCommerce (natives and from other plugins)
        $allWooCoommerceOrderStatuses = wc_get_order_statuses();
        $orderStatuses = array_merge($orderStatuses, $allWooCoommerceOrderStatuses);

        return $orderStatuses;
    }
}
