<?php

use AcyMailing\Types\OperatorinType;
use AcyMailing\Types\OperatorType;

trait VirtuemartAutomationConditions
{
    public function onAcymDeclareConditions(&$conditions)
    {
        acym_loadLanguageFile('com_virtuemart_orders', JPATH_SITE.'/components/com_virtuemart');
        acym_loadLanguageFile('com_virtuemart_shoppers', JPATH_SITE.'/components/com_virtuemart');

        // Shopper groups
        $groups = acym_loadObjectList(
            'SELECT `virtuemart_shoppergroup_id` AS `value`, `shopper_group_name` AS `text` 
            FROM `#__virtuemart_shoppergroups` 
            ORDER BY `ordering` ASC, `shopper_group_name` ASC'
        );

        if (!empty($groups)) {
            foreach ($groups as $i => $oneGroup) {
                $groups[$i]->text = acym_translation($oneGroup->text);
            }

            $operatorIn = new OperatorinType();

            $conditions['user']['vmgroups'] = new stdClass();
            $conditions['user']['vmgroups']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'VirtueMart', acym_translation('ACYM_GROUP'));
            $conditions['user']['vmgroups']->option = '<div class="intext_select_automation cell">';
            $conditions['user']['vmgroups']->option .= $operatorIn->display('acym_condition[conditions][__numor__][__numand__][vmgroups][type]');
            $conditions['user']['vmgroups']->option .= '</div>';
            $conditions['user']['vmgroups']->option .= '<div class="intext_select_automation cell">';
            $conditions['user']['vmgroups']->option .= acym_select(
                $groups,
                'acym_condition[conditions][__numor__][__numand__][vmgroups][group]',
                null,
                ['class' => 'acym__select']
            );
            $conditions['user']['vmgroups']->option .= '</div>';
        }

        // VirtueMart fields
        $fields = acym_getColumns('virtuemart_userinfos', false);
        if (!empty($fields)) {
            $fields = array_combine($fields, $fields);
            ksort($fields);
            $operator = new OperatorType();

            $conditions['user']['vmfield'] = new stdClass();
            $conditions['user']['vmfield']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'VirtueMart', acym_translation('ACYM_FIELDS'));
            $conditions['user']['vmfield']->option = '<div class="intext_select_automation cell">';
            $conditions['user']['vmfield']->option .= acym_select(
                $fields,
                'acym_condition[conditions][__numor__][__numand__][vmfield][field]',
                null,
                ['class' => 'acym__select acym__automation__conditions__fields__dropdown']
            );
            $conditions['user']['vmfield']->option .= '</div>';
            $conditions['user']['vmfield']->option .= '<div class="intext_select_automation cell">';
            $conditions['user']['vmfield']->option .= $operator->display(
                'acym_condition[conditions][__numor__][__numand__][vmfield][operator]',
                '',
                'acym__automation__conditions__operator__dropdown'
            );
            $conditions['user']['vmfield']->option .= '</div>';
            $conditions['user']['vmfield']->option .= '<input 
                                                            class="acym__automation__one-field intext_input_automation cell acym__automation__condition__regular-field" 
                                                            type="text" 
                                                            name="acym_condition[conditions][__numor__][__numand__][vmfield][value]">';
        }

        // Reminder
        $orderStatuses = ['' => acym_translation('ACYM_ANY_STATUS')];
        $statuses = acym_loadObjectList('SELECT `order_status_code` AS `code`, `order_status_name` AS `name` FROM `#__virtuemart_orderstates` ORDER BY `ordering` ASC');
        foreach ($statuses as $status) {
            $orderStatuses[$status->code] = acym_translation($status->name);
        }

        $paymentMethods = ['' => acym_translation('ACYM_ANY_PAYMENT_METHOD')];
        $payments = acym_loadObjectList(
            'SELECT `method`.`virtuemart_paymentmethod_id` AS `id`, `translation`.`payment_name` AS `name` 
            FROM `#__virtuemart_paymentmethods` AS `method` 
            LEFT JOIN `#__virtuemart_paymentmethods_'.$this->lang.'` AS `translation` ON `method`.`virtuemart_paymentmethod_id` = `translation`.`virtuemart_paymentmethod_id` 
            WHERE `published` = 1 
            ORDER BY `ordering` ASC'
        );
        foreach ($payments as $oneMethod) {
            $paymentMethods[$oneMethod->id] = $oneMethod->name;
        }

        $conditions['user']['vmreminder'] = new stdClass();
        $conditions['user']['vmreminder']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'VirtueMart', acym_translation('ACYM_REMINDER'));
        $conditions['user']['vmreminder']->option = '<div class="cell">';
        $conditions['user']['vmreminder']->option .= acym_translationSprintf(
            'ACYM_ORDER_WITH_STATUS',
            '<input type="number" name="acym_condition[conditions][__numor__][__numand__][vmreminder][days]" value="1" min="1" class="intext_input"/>',
            '<div class="intext_select_automation cell margin-right-1">'.acym_select(
                $orderStatuses,
                'acym_condition[conditions][__numor__][__numand__][vmreminder][status]',
                '',
                ['class' => 'acym__select']
            ).'</div>'
        );
        $conditions['user']['vmreminder']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['vmreminder']->option .= acym_select(
            $paymentMethods,
            'acym_condition[conditions][__numor__][__numand__][vmreminder][payment]',
            '',
            ['class' => 'acym__select']
        );
        $conditions['user']['vmreminder']->option .= '</div>';
        $conditions['user']['vmreminder']->option .= '</div>';


        // Placed orders
        $conditions['user']['vmpurchased'] = new stdClass();
        $conditions['user']['vmpurchased']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'VirtueMart', acym_translation('ACYM_PURCHASED'));
        $conditions['user']['vmpurchased']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['vmpurchased']->option .= '<div class="cell acym_vcenter shrink">'.acym_translation('ACYM_BOUGHT').'</div>';

        $conditions['user']['vmpurchased']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode(['plugin' => __CLASS__, 'trigger' => 'searchProduct']);
        $conditions['user']['vmpurchased']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][vmpurchased][product]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_AT_LEAST_ONE_PRODUCT'),
                'data-params' => $ajaxParams,
            ]
        );
        $conditions['user']['vmpurchased']->option .= '</div>';

        $conditions['user']['vmpurchased']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode(['plugin' => __CLASS__, 'trigger' => 'searchCategory',]);
        $conditions['user']['vmpurchased']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][vmpurchased][category]',
            null,
            [
                'class' => 'acym__select acym_select2_ajax',
                'data-placeholder' => acym_translation('ACYM_ANY_CATEGORY'),
                'data-params' => $ajaxParams,
            ]
        );
        $conditions['user']['vmpurchased']->option .= '</div>';

        $conditions['user']['vmpurchased']->option .= '<div class="cell grid-x grid-margin-x margin-top-1 margin-left-0">';
        $conditions['user']['vmpurchased']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][vmpurchased][datemin]', '', 'cell shrink');
        $conditions['user']['vmpurchased']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['vmpurchased']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_DATE_CREATED').'</span>';
        $conditions['user']['vmpurchased']->option .= '<span class="acym__title acym__title__secondary acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['vmpurchased']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][vmpurchased][datemax]', '', 'cell shrink');
        $conditions['user']['vmpurchased']->option .= '</div>';

        $conditions['user']['vmpurchased']->option .= '</div>';
    }

    /**
     * Function called with ajax to search in products
     */
    public function searchProduct()
    {
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            $value = '';
            $element = acym_loadResult('SELECT `product_name` FROM #__virtuemart_products_'.$this->lang.' WHERE `virtuemart_product_id` = '.intval($id));
            if (!empty($element)) $value = $element;
            echo json_encode(
                [
                    [
                        'value' => $id,
                        'text' => $value,
                    ],
                ]
            );
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');

        $elements = acym_loadObjectList(
            'SELECT `virtuemart_product_id`, `product_name` 
            FROM #__virtuemart_products_'.$this->lang.' 
            WHERE `product_name` LIKE '.acym_escapeDB('%'.$search.'%').' 
            ORDER BY `product_name` ASC'
        );
        foreach ($elements as $oneElement) {
            $return[] = [$oneElement->virtuemart_product_id, $oneElement->product_name];
        }

        echo json_encode($return);
        exit;
    }

    /**
     * Function called with ajax to search in product categories
     */
    public function searchCategory()
    {
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            $element = acym_loadObject(
                'SELECT `virtuemart_category_id` AS id, `category_name` AS name FROM #__virtuemart_categories_'.$this->lang.' WHERE `virtuemart_category_id` = '.intval($id)
            );
            if (empty($element)) {
                echo json_encode([]);
            } else {
                echo json_encode(['value' => $element->id, 'text' => $element->name]);
            }
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');
        $categories = acym_loadObjectList(
            'SELECT `virtuemart_category_id`, `category_name` 
            FROM `#__virtuemart_categories_'.$this->lang.'` 
            WHERE `category_name` LIKE '.acym_escapeDB('%'.$search.'%').' 
            ORDER BY `category_name` ASC'
        );

        foreach ($categories as $oneCategory) {
            $return[] = [$oneCategory->virtuemart_category_id, $oneCategory->category_name];
        }

        echo json_encode($return);
        exit;
    }

    public function onAcymProcessCondition_vmgroups(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_vmgroups($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_vmgroups(&$query, $options, $num)
    {
        $defaultGroups = acym_loadResultArray('SELECT `virtuemart_shoppergroup_id` FROM `#__virtuemart_shoppergroups` WHERE `default` > 0');
        if (empty($defaultGroups)) $defaultGroups = [0];

        $join = '#__virtuemart_vmuser_shoppergroups AS vmgroup_'.$num.' ON user.cms_id = vmgroup_'.$num.'.virtuemart_user_id AND user.cms_id > 0';
        $where = 'vmgroup_'.$num.'.virtuemart_shoppergroup_id = '.intval($options['group']);

        if (empty($options['type']) || $options['type'] == 'in') {
            // VirtueMart doesn't add an entry in the vmuser_shoppergroups table for default groups...
            $query->where['vmgroups_'.$num] = $where;
            if (in_array($options['group'], $defaultGroups)) {
                $query->leftjoin['vmgroups_'.$num] = $join;
                $query->where['vmgroups_'.$num] .= ' OR vmgroup_'.$num.'.virtuemart_shoppergroup_id IS NULL';
            } else {
                $query->join['vmgroups_'.$num] = $join;
            }
        } else {
            if (in_array($options['group'], $defaultGroups)) {
                $query->leftjoin['vmgroups_'.$num] = $join;
                $query->leftjoin['vmgroups_'.$num.'_2'] = str_replace('vmgroup_'.$num, 'vmgroup_'.$num.'_2', $join.' AND '.$where);
                $query->where[] = 'vmgroup_'.$num.'.virtuemart_user_id IS NOT NULL AND vmgroup_'.$num.'_2.virtuemart_user_id IS NULL';
            } else {
                $query->leftjoin['vmgroups_'.$num] = $join.' AND '.$where;
                $query->where[] = 'vmgroup_'.$num.'.virtuemart_user_id IS NULL';
            }
        }
    }

    public function onAcymProcessCondition_vmfield(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_vmfield($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_vmfield(&$query, $options, $num)
    {
        $query->join['vmfield_user'] = '#__virtuemart_userinfos AS vmfield_user ON user.cms_id = vmfield_user.virtuemart_user_id AND user.cms_id > 0';
        $query->where[] = $query->convertQuery('vmfield_user', $options['field'], $options['operator'], $options['value']);
    }

    public function onAcymProcessCondition_vmreminder(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_vmreminder($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_vmreminder(&$query, $options, $num)
    {
        $options['days'] = intval($options['days']);

        $query->join['vmreminder_user_'.$num] = '`#__virtuemart_order_userinfos` AS vmuserinfos_'.$num.' ON vmuserinfos_'.$num.'.`email` = `user`.`email`';
        $query->join['vmreminder_orders_'.$num] = '`#__virtuemart_orders` AS vmorders'.$num.' ON vmorders'.$num.'.`virtuemart_order_id` = vmuserinfos_'.$num.'.`virtuemart_order_id`';

        if (!empty($options['status'])) $query->where[] = 'vmorders'.$num.'.`order_status` = '.acym_escapeDB($options['status']);
        if (!empty($options['payment'])) $query->where[] = 'vmorders'.$num.'.`virtuemart_paymentmethod_id` = '.intval($options['payment']);

        $query->where[] = 'SUBSTR(vmorders'.$num.'.`created_on`, 1, 10) = '.acym_escapeDB(acym_date(time() - ($options['days'] * 86400), 'Y-m-d', false));
    }

    public function onAcymProcessCondition_vmpurchased(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_vmpurchased($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    private function processConditionFilter_vmpurchased(&$query, $options, $num)
    {
        $orderUser = '`vmorderuserinfos_'.$num.'`';
        $order = '`vmorder_'.$num.'`';
        $orderItem = '`vmorderitem_'.$num.'`';

        $query->join[$orderUser] = '`#__virtuemart_order_userinfos` AS '.$orderUser.' ON '.$orderUser.'.`email` = `user`.`email` OR ('.$orderUser.'.`virtuemart_user_id` = `user`.`cms_id` AND `user`.`cms_id` > 0)';
        $query->join[$order] = '`#__virtuemart_orders` AS '.$order.' ON '.$order.'.`virtuemart_order_id` = '.$orderUser.'.`virtuemart_order_id`';
        $query->where[] = $order.'.`order_status` IN ("C", "F", "U")';

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (is_numeric($options['datemin'])) $options['datemin'] = acym_date($options['datemin'], 'Y-m-d H:i:s', false);
            $query->where[] = $order.'.created_on > '.acym_escapeDB($options['datemin']);
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (is_numeric($options['datemax'])) $options['datemax'] = acym_date($options['datemax'], 'Y-m-d H:i:s', false);
            $query->where[] = $order.'.created_on < '.acym_escapeDB($options['datemax']);
        }

        $join = '`#__virtuemart_order_items` AS '.$orderItem.' ON '.$orderItem.'.`virtuemart_order_id` = '.$orderUser.'.`virtuemart_order_id` ';
        if (!empty($options['product'])) {
            $query->join['vmpurchased_item_'.$num] = $join;
            $query->where[] = $orderItem.'.`virtuemart_product_id` = '.intval($options['product']);
        } elseif (!empty($options['category'])) {
            $query->join['vmpurchased_item_'.$num] = $join;
            $query->join['vmpurchased_products_'.$num] = '`#__virtuemart_products` AS vp'.$num.' ON '.$orderItem.'.virtuemart_product_id = vp'.$num.'.virtuemart_product_id';
            $query->join['vmpurchased_order_cat'.$num] = '`#__virtuemart_product_categories` AS vpc'.$num.' 
                                                                ON vp'.$num.'.virtuemart_product_id = vpc'.$num.'.virtuemart_product_id 
                                                                OR vp'.$num.'.product_parent_id = vpc'.$num.'.virtuemart_product_id';
            $query->where[] = 'vpc'.$num.'.virtuemart_category_id = '.intval($options['category']);
        }
    }

    public function onAcymDeclareSummary_conditions(&$automationCondition)
    {
        $this->summaryConditionFilters($automationCondition);
    }

    private function summaryConditionFilters(&$automationCondition)
    {
        if (!empty($automationCondition['vmgroups'])) {
            acym_loadLanguageFile('com_virtuemart_shoppers', JPATH_SITE.'/components/com_virtuemart');

            $groupName = acym_loadResult(
                'SELECT `shopper_group_name` FROM `#__virtuemart_shoppergroups` WHERE `virtuemart_shoppergroup_id` = '.intval($automationCondition['vmgroups']['group'])
            );
            $automationCondition = acym_translationSprintf(
                'ACYM_FILTER_ACY_GROUP_SUMMARY',
                acym_translation($automationCondition['vmgroups']['type'] == 'in' ? 'ACYM_IN' : 'ACYM_NOT_IN'),
                acym_translation($groupName)
            );
        }

        if (!empty($automationCondition['vmfield'])) {
            $automationCondition = acym_translationSprintf(
                'ACYM_CONDITION_X_FIELD_SUMMARY',
                $this->pluginDescription->name,
                $automationCondition['vmfield']['field'],
                $automationCondition['vmfield']['operator'],
                $automationCondition['vmfield']['value']
            );
        }

        if (!empty($automationCondition['vmreminder'])) {
            acym_loadLanguageFile('com_virtuemart_orders', JPATH_SITE.'/components/com_virtuemart');

            $status = acym_loadResult(
                'SELECT `order_status_name` FROM `#__virtuemart_orderstates` WHERE `order_status_code` = '.acym_escapeDB($automationCondition['vmreminder']['status'])
            );
            if (empty($status)) $status = 'ACYM_ANY_STATUS';

            $payment = acym_loadResult(
                'SELECT `payment_name` FROM `#__virtuemart_paymentmethods_'.$this->lang.'` WHERE `virtuemart_paymentmethod_id` = '.intval(
                    $automationCondition['vmreminder']['payment']
                )
            );
            if (empty($payment)) $payment = 'ACYM_ANY_PAYMENT_METHOD';

            $automationCondition = acym_translationSprintf(
                'ACYM_CONDITION_ECOMMERCE_REMINDER',
                acym_translation($payment),
                intval($automationCondition['vmreminder']['days']),
                acym_translation($status)
            );
        }

        if (!empty($automationCondition['vmpurchased'])) {
            if (!empty($automationCondition['vmpurchased']['product'])) {
                $product = acym_loadResult(
                    'SELECT `product_name` FROM #__virtuemart_products_'.$this->lang.' WHERE `virtuemart_product_id` = '.intval($automationCondition['vmpurchased']['product'])
                );
            }
            if (empty($product)) $product = acym_translation('ACYM_AT_LEAST_ONE_PRODUCT');

            if (!empty($automationCondition['vmpurchased']['category'])) {
                $category = acym_loadResult(
                    'SELECT `category_name` FROM #__virtuemart_categories_'.$this->lang.' WHERE `virtuemart_category_id` = '.intval($automationCondition['vmpurchased']['category'])
                );
            }
            if (empty($category)) $category = acym_translation('ACYM_ANY_CATEGORY');

            $finalText = acym_translationSprintf('ACYM_CONDITION_PURCHASED', $product, $category);

            $dates = [];
            if (!empty($automationCondition['vmpurchased']['datemin'])) {
                $dates[] = acym_translation('ACYM_AFTER').' '.acym_replaceDate($automationCondition['vmpurchased']['datemin'], true);
            }

            if (!empty($automationCondition['vmpurchased']['datemax'])) {
                $dates[] = acym_translation('ACYM_BEFORE').' '.acym_replaceDate($automationCondition['vmpurchased']['datemax'], true);
            }

            if (!empty($dates)) {
                $finalText .= ' '.implode(' '.acym_translation('ACYM_AND').' ', $dates);
            }

            $automationCondition = $finalText;
        }
    }
}
