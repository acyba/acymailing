<?php

class plgAcymWoocommerce extends acymPlugin
{
    public function __construct()
    {
        parent::__construct();
        $this->cms = 'WordPress';
        $this->installed = acym_isExtensionActive('woocommerce/woocommerce.php');
        $this->rootCategoryId = 0;

        $this->pluginDescription->name = 'WooCommerce';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';

        if ($this->installed) {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'price' => ['ACYM_PRICE', true],
                'shortdesc' => ['ACYM_SHORT_DESCRIPTION', true],
                'desc' => ['ACYM_DESCRIPTION', false],
                'cats' => ['ACYM_CATEGORIES', false],
                'attribs' => ['ACYM_DETAILS', false],
            ];

            $this->initElementOptionsCustomView();
            $this->initReplaceOptionsCustomView();

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
            ];
        } else {
            $this->settings = [
                'not_installed' => '1',
            ];
        }
    }

    public function getStandardStructure(&$customView)
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = '{price} <br> {picthtml}';
        $format->afterArticle = '';
        $format->imagePath = '';
        $format->description = '{shortdesc}';
        $format->link = '{link}';
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initReplaceOptionsCustomView()
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'picthtml' => ['ACYM_IMAGE'],
        ];
    }

    public function initElementOptionsCustomView()
    {
        $query = 'SELECT product.*
                    FROM #__posts AS product
                    WHERE product.post_type = "product" 
                        AND product.post_status = "publish"';
        $element = acym_loadObject($query);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function getPossibleIntegrations()
    {
        return $this->pluginDescription;
    }

    public function insertionOptions($defaultValues = null)
    {
        $this->defaultValues = $defaultValues;

        $this->categories = acym_loadObjectList(
            "SELECT cat.term_taxonomy_id AS id, cat.parent AS parent_id, catdetails.name AS title 
            FROM `#__term_taxonomy` AS cat 
            JOIN `#__terms` AS catdetails ON cat.term_id = catdetails.term_id
            WHERE cat.taxonomy = 'product_cat'"
        );

        $this->tagvalues = acym_loadObjectList(
            'SELECT term.term_id, term.`name`
			FROM #__terms AS term
			JOIN #__term_taxonomy AS tax ON term.term_id = tax.term_id
			WHERE tax.taxonomy = "product_tag"
			ORDER BY term.`name`'
        );

        $wooCategories = [];
        foreach ($this->categories as $oneCat) {
            $wooCategories[$oneCat->id] = $oneCat->title;
        }

        $tabHelper = acym_get('helper.tab');
        $identifier = $this->name;
        $tabHelper->startTab(acym_translation('ACYM_ONE_BY_ONE'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $displayOptions = [
            [
                'title' => 'ACYM_DISPLAY',
                'type' => 'checkbox',
                'name' => 'display',
                'options' => $this->displayOptions,
            ],
            [
                'title' => 'ACYM_CLICKABLE_TITLE',
                'type' => 'boolean',
                'name' => 'clickable',
                'default' => true,
            ],
            [
                'title' => 'ACYM_TRUNCATE',
                'type' => 'intextfield',
                'isNumber' => 1,
                'name' => 'wrap',
                'text' => 'ACYM_TRUNCATE_AFTER',
                'default' => 0,
            ],
            [
                'title' => 'ACYM_DISPLAY_PICTURES',
                'type' => 'pictures',
                'name' => 'pictures',
            ],
        ];

        $zoneContent = $this->getFilteringZone().$this->prepareListing();
        echo $this->displaySelectionZone($zoneContent);
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'individual', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = 'auto'.$this->name;
        $tabHelper->startTab(acym_translation('ACYM_BY_CATEGORY'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $catOptions = [
            [
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => [
                    'ID' => 'ACYM_ID',
                    'post_date' => 'ACYM_PUBLISHING_DATE',
                    'post_modified' => 'ACYM_MODIFICATION_DATE',
                    'post_title' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
            ],
            [
                'title' => 'ACYM_COLUMNS',
                'type' => 'number',
                'name' => 'cols',
                'default' => 1,
            ],
            [
                'title' => 'ACYM_MAX_NB_ELEMENTS',
                'type' => 'number',
                'name' => 'max',
                'default' => 20,
            ],
        ];

        $this->autoCampaignOptions($catOptions);

        $displayOptions = array_merge($displayOptions, $catOptions);

        echo $this->displaySelectionZone($this->getCategoryListing());
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = $this->name.'_tags';
        $tabHelper->startTab(acym_translation('ACYM_BY_TAG'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        echo $this->displaySelectionZone($this->getTagListing());
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = $this->name.'_coupon';
        $tabHelper->startTab(acym_translation('ACYM_COUPON'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $displayOptions = [
            [
                'title' => 'ACYM_DISCOUNT_CODE',
                'type' => 'text',
                'name' => 'code',
                'default' => '[name][key][value]',
                'class' => 'acym_plugin__larger_text_field',
            ],
            [
                'title' => __('Coupon expiry date', 'woocommerce'),
                'type' => 'date',
                'name' => 'end',
                'default' => '',
            ],
            [
                'title' => __('Discount type', 'woocommerce'),
                'type' => 'select',
                'name' => 'type',
                'options' => [
                    'fixed_cart' => __('Fixed cart discount', 'woocommerce'),
                    'fixed_product' => __('Fixed product discount', 'woocommerce'),
                    'percent' => __('Percentage discount', 'woocommerce'),
                ],
            ],
            [
                'title' => __('Coupon amount', 'woocommerce'),
                'type' => 'number',
                'name' => 'amount',
                'default' => '0',
            ],
            [
                'title' => __('Allow free shipping', 'woocommerce'),
                'type' => 'boolean',
                'name' => 'free',
                'default' => false,
            ],
            [
                'title' => __('Exclude sale items', 'woocommerce'),
                'type' => 'boolean',
                'name' => 'exclsale',
                'default' => false,
            ],
            [
                'title' => __('Minimum spend', 'woocommerce'),
                'type' => 'number',
                'name' => 'min',
                'default' => '',
            ],
            [
                'title' => __('Maximum spend', 'woocommerce'),
                'type' => 'number',
                'name' => 'max',
                'default' => '',
            ],
            [
                'title' => __('Usage limit per coupon', 'woocommerce'),
                'type' => 'number',
                'name' => 'use',
                'default' => '1',
            ],
            [
                'title' => __('Limit usage to X items', 'woocommerce'),
                'type' => 'number',
                'name' => 'items',
                'default' => '',
            ],
            [
                'title' => __('Products', 'woocommerce'),
                'type' => 'text',
                'name' => 'prod',
                'class' => 'acym_plugin__larger_text_field',
                'default' => '',
            ],
            [
                'title' => __('Exclude products', 'woocommerce'),
                'type' => 'text',
                'name' => 'exclprod',
                'class' => 'acym_plugin__larger_text_field',
                'default' => '',
            ],
            [
                'title' => __('Product categories', 'woocommerce'),
                'type' => 'multiselect',
                'name' => 'cat',
                'options' => $wooCategories,
            ],
            [
                'title' => __('Exclude categories', 'woocommerce'),
                'type' => 'multiselect',
                'name' => 'exclcat',
                'options' => $wooCategories,
            ],
        ];

        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'simple', $this->defaultValues);

        $tabHelper->endTab();

        $tabHelper->display('plugin');
    }

    public function prepareListing()
    {
        $this->querySelect = 'SELECT product.ID, product.post_title, product.post_date ';
        $this->query = 'FROM #__posts AS product ';
        $this->filters = [];
        $this->filters[] = 'product.post_type = "product"';
        $this->filters[] = 'product.post_status = "publish"';
        $this->searchFields = ['product.ID', 'product.post_title'];
        $this->pageInfo->order = 'product.ID';
        $this->elementIdTable = 'product';
        $this->elementIdColumn = 'ID';

        parent::prepareListing();

        if (!empty($this->pageInfo->filter_cat)) {
            $this->query .= 'JOIN #__term_relationships AS cat ON product.ID = cat.object_id';
            $this->filters[] = 'cat.term_taxonomy_id = '.intval($this->pageInfo->filter_cat);
        }

        $listingOptions = [
            'header' => [
                'post_title' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '8',
                ],
                'post_date' => [
                    'label' => 'ACYM_DATE_CREATED',
                    'size' => '3',
                    'type' => 'date',
                ],
                'ID' => [
                    'label' => 'ACYM_ID',
                    'size' => '1',
                    'class' => 'text-center',
                ],
            ],
            'id' => 'ID',
            'rows' => $this->getElements(),
        ];

        return $this->getElementsListing($listingOptions);
    }

    public function replaceContent(&$email)
    {
        $this->replaceMultiple($email);
        $this->replaceOne($email);
    }

    public function generateByCategory(&$email)
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $tags = array_merge($tags, $this->pluginHelper->extractTags($email, $this->name.'_tags'));

        $this->tags = [];

        if (empty($tags)) return $this->generateCampaignResult;

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $query = 'SELECT DISTINCT product.`ID` 
                    FROM #__posts AS product 
                    LEFT JOIN #__term_relationships AS cat ON product.ID = cat.object_id';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'cat.term_taxonomy_id IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'product.post_type = "product"';
            $where[] = 'product.post_status = "publish"';

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = 'product.post_date_gmt > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                }
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter, 'product');
        }

        return $this->generateCampaignResult;
    }

    public function replaceIndividualContent($tag)
    {
        $query = 'SELECT product.*
                    FROM #__posts AS product
                    WHERE product.post_type = "product" 
                        AND product.post_status = "publish"
                        AND product.ID = '.intval($tag->id);

        $element = $this->initIndividualContent($tag, $query);
        $product = wc_get_product($tag->id);

        if (empty($element) || empty($product)) return '';

        $varFields = $this->getCustomLayoutVars($element);

        $link = get_permalink($element->ID);
        $varFields['{link}'] = $link;

        $title = '';
        $varFields['{title}'] = $element->post_title;
        if (in_array('title', $tag->display)) $title = $varFields['{title}'];

        $afterTitle = '';
        $varFields['{price}'] = $product->get_price_html();
        if (in_array('price', $tag->display)) $afterTitle .= $varFields['{price}'];

        $imagePath = '';
        $imageHTML = $product->get_image('full');
        if (!empty($imageHTML)) {
            $posURL = strpos($imageHTML, ' src="') + 6;
            $imagePath = substr($imageHTML, $posURL, strpos($imageHTML, '"', $posURL) - $posURL);
        }
        $varFields['{picthtml}'] = '<img alt="" src="'.$imagePath.'">';
        if (empty($tag->pict)) $imagePath = '';

        $contentText = '';
        $varFields['{shortdesc}'] = $element->post_excerpt;
        $varFields['{desc}'] = $element->post_content;
        if (in_array('shortdesc', $tag->display)) $contentText .= $varFields['{shortdesc}'];
        if (in_array('desc', $tag->display)) $contentText .= $varFields['{desc}'];

        $customFields = [];
        $varFields['{cats}'] = get_the_term_list($tag->id, 'product_cat', '', ', ');
        if (in_array('cats', $tag->display)) {
            $customFields[] = [
                $varFields['{cats}'],
                acym_translation('ACYM_CATEGORIES'),
            ];
        }

        $tmpCustomField = [];
        $varFields['{attribs}'] = '';
        $attributes = acym_loadResult('SELECT meta_value FROM #__postmeta WHERE meta_key = "_product_attributes" AND post_id = '.intval($tag->id));
        if (is_string($attributes)) {
            $attributes = unserialize($attributes);
            if (!empty($attributes)) {
                $varFields['{attribs}'] = [];
                foreach ($attributes as $oneAttribute) {
                    if ($oneAttribute['is_visible'] != 1) continue;

                    $varFields['{attribs}'][] = $oneAttribute['name'].': '.str_replace('|', ', ', $oneAttribute['value']);
                    $tmpCustomField[] = [
                        str_replace('|', ', ', $oneAttribute['value']),
                        $oneAttribute['name'],
                    ];
                }
                $varFields['{attribs}'] = implode('<br/>', $varFields['{attribs}']);
            }
        }

        if (in_array('attribs', $tag->display)) $customFields = array_merge($customFields, $tmpCustomField);

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = $title;
        $format->afterTitle = $afterTitle;
        $format->afterArticle = '';
        $format->imagePath = $imagePath;
        $format->description = $contentText;
        $format->link = empty($tag->clickable) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }

    public function replaceUserInformation(&$email, &$user, $send = true)
    {
        $this->_replaceCoupons($email, $user, $send);
    }

    private function _replaceCoupons(&$email, &$user, $send = true)
    {
        $tags = $this->pluginHelper->extractTags($email, 'woocommerce_coupon');
        if (empty($tags)) {
            return;
        }

        $tagsReplaced = [];
        foreach ($tags as $i => $oneTag) {
            if (isset($tagsReplaced[$i])) {
                continue;
            }
            if (!$send || empty($user->id)) {
                $tagsReplaced[$i] = '<i>'.acym_translation('ACYM_CHECK_EMAIL_COUPON').'</i>';
            } else {
                $tagsReplaced[$i] = $this->generateCoupon($oneTag, $user);
            }
        }

        $this->pluginHelper->replaceTags($email, $tagsReplaced, true);
    }

    private function generateCoupon($tag, $user)
    {
        if (empty($tag->code) || empty($tag->amount) || empty($tag->type) || !in_array($tag->type, ['fixed_cart', 'fixed_product', 'percent'])) return '';

        $intAttributes = ['amount', 'free', 'min', 'max', 'exclsale', 'use', 'items'];
        foreach ($intAttributes as $oneAttribute) {
            if (empty($tag->$oneAttribute)) $tag->$oneAttribute = 0;
            $tag->$oneAttribute = intval($tag->$oneAttribute);
        }

        if (empty($tag->amount)) return '';


        $clean_name = strtoupper($user->name);
        $space = strpos($clean_name, ' ');
        if (!empty($space)) $clean_name = substr($clean_name, 0, $space);

        $couponCode = str_replace(
            [
                '[name]',
                '[userid]',
                '[email]',
                '[key]',
                '[value]',
            ],
            [
                $clean_name,
                $user->id,
                $user->email,
                acym_generateKey(5),
                $tag->amount,
            ],
            $tag->code
        );


        $coupon = [
            'post_title' => $couponCode,
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
            'post_type' => 'shop_coupon',
        ];

        $couponId = wp_insert_post($coupon);

        // Add Details
        update_post_meta($couponId, 'discount_type', $tag->type);
        update_post_meta($couponId, 'coupon_amount', $tag->amount);
        update_post_meta($couponId, 'expiry_date', empty($tag->end) ? '' : acym_date(acym_replaceDate($tag->end), 'Y-m-d'));
        update_post_meta($couponId, 'date_expires', empty($tag->end) ? null : acym_replaceDate($tag->end));

        update_post_meta($couponId, 'usage_limit', $tag->use);
        update_post_meta($couponId, 'usage_limit_per_user', 0);
        update_post_meta($couponId, 'limit_usage_to_x_items', $tag->items);
        update_post_meta($couponId, 'usage_count', 0);

        update_post_meta($couponId, 'minimum_amount', empty($tag->min) ? '' : $tag->min);
        update_post_meta($couponId, 'maximum_amount', empty($tag->max) ? '' : $tag->max);

        update_post_meta($couponId, 'free_shipping', empty($tag->free) ? 'no' : 'yes');
        update_post_meta($couponId, 'exclude_sale_items', empty($tag->exclsale) ? 'no' : 'yes');


        update_post_meta($couponId, 'product_ids', implode(',', $this->cleanElements($tag->prod)));
        update_post_meta($couponId, 'exclude_product_ids', implode(',', $this->cleanElements($tag->exclprod)));

        update_post_meta($couponId, 'product_categories', $this->cleanElements($tag->cat));
        update_post_meta($couponId, 'exclude_product_categories', $this->cleanElements($tag->exclcat));


        // Apply the coupon only to the current user
        update_post_meta($couponId, 'individual_use', 'yes');
        update_post_meta($couponId, 'customer_email', [$user->email]);


        return $couponCode;
    }

    private function cleanElements($elements)
    {
        $elements = empty($elements) ? [] : explode(',', $elements);
        acym_arrayToInteger($elements);
        foreach ($elements as $i => $oneElement) {
            if (empty($oneElement)) unset($elements[$i]);
        }

        return $elements;
    }

    public function searchProduct()
    {
        $id = acym_getVar('int', 'id');
        if (!empty($id)) {
            $args = [
                'p' => $id,
                'post_type' => 'product',
            ];
            $posts = new WP_Query($args);

            $value = '';
            if ($posts->have_posts()) {
                $posts->the_post();
                $value = $posts->post->post_title;
            }
            echo json_encode(['value' => $value]);
            exit;
        }

        $return = [];
        $search = acym_getVar('string', 'search', '');

        $search_results = new WP_Query(
            [
                's' => $search,
                'post_status' => 'publish',
                'ignore_sticky_posts' => 1,
                'post_type' => 'product',
                'posts_per_page' => 20,
            ]
        );

        if ($search_results->have_posts()) {
            while ($search_results->have_posts()) {
                $search_results->the_post();
                $return[] = [$search_results->post->ID, $search_results->post->post_title];
            }
        }

        echo json_encode($return);
        exit;
    }

    public function onAcymDeclareConditions(&$conditions)
    {
        $categories = [
            'any' => acym_translation('ACYM_ANY_CATEGORY'),
        ];
        $cats = acym_loadObjectList('SELECT term.term_id, term.`name` FROM #__terms AS term JOIN #__term_taxonomy AS tax ON term.term_id = tax.term_id WHERE tax.taxonomy = "product_cat" ORDER BY term.`name`');
        foreach ($cats as $oneCat) {
            $categories[$oneCat->term_id] = $oneCat->name;
        }

        $conditions['user']['woopurchased'] = new stdClass();
        $conditions['user']['woopurchased']->name = acym_translation_sprintf('ACYM_COMBINED_TRANSLATIONS', 'WooCommerce', acym_translation('ACYM_PURCHASED'));
        $conditions['user']['woopurchased']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['woopurchased']->option .= '<div class="cell acym_vcenter shrink">'.acym_translation('ACYM_BOUGHT').'</div>';

        $conditions['user']['woopurchased']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode(
            [
                'plugin' => 'plgAcymWoocommerce',
                'trigger' => 'searchProduct',
            ]
        );
        $conditions['user']['woopurchased']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][woopurchased][product]',
            null,
            'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_AT_LEAST_ONE_PRODUCT', true).'" data-params="'.acym_escape($ajaxParams).'"'
        );
        $conditions['user']['woopurchased']->option .= '</div>';

        $conditions['user']['woopurchased']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['woopurchased']->option .= acym_select($categories, 'acym_condition[conditions][__numor__][__numand__][woopurchased][category]', 'any', 'class="acym__select"');
        $conditions['user']['woopurchased']->option .= '</div>';

        $conditions['user']['woopurchased']->option .= '</div>';

        $conditions['user']['woopurchased']->option .= '<div class="cell grid-x grid-margin-x">';
        $conditions['user']['woopurchased']->option .= acym_dateField('acym_condition[conditions][__numor__][__numand__][woopurchased][datemin]', '', 'cell shrink');
        $conditions['user']['woopurchased']->option .= '<span class="acym__content__title__light-blue acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $conditions['user']['woopurchased']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_DATE_CREATED').'</span>';
        $conditions['user']['woopurchased']->option .= '<span class="acym__content__title__light-blue acym_vcenter margin-bottom-0 cell shrink"><</span>';
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
        $conditions['user']['wooreminder']->name = acym_translation_sprintf('ACYM_COMBINED_TRANSLATIONS', 'WooCommerce', acym_translation('ACYM_REMINDER'));
        $conditions['user']['wooreminder']->option = '<div class="cell">';
        $conditions['user']['wooreminder']->option .= acym_translation_sprintf(
            'ACYM_ORDER_WITH_STATUS',
            '<input type="number" name="acym_condition[conditions][__numor__][__numand__][wooreminder][days]" value="1" min="1" class="intext_input"/>',
            '<div class="intext_select_automation cell margin-right-1">'.acym_select(
                [
                    'wc-pending' => _x('Pending payment', 'Order status', 'woocommerce'),
                    'wc-processing' => _x('Processing', 'Order status', 'woocommerce'),
                    'wc-on-hold' => _x('On hold', 'Order status', 'woocommerce'),
                    'wc-completed' => _x('Completed', 'Order status', 'woocommerce'),
                    'wc-cancelled' => _x('Cancelled', 'Order status', 'woocommerce'),
                    'wc-refunded' => _x('Refunded', 'Order status', 'woocommerce'),
                    'wc-failed' => _x('Failed', 'Order status', 'woocommerce'),
                ],
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
    }

    public function onAcymDeclareFilters(&$filters)
    {
        $categories = [
            'any' => acym_translation('ACYM_ANY_CATEGORY'),
        ];
        $cats = acym_loadObjectList('SELECT term.term_id, term.`name` FROM #__terms AS term JOIN #__term_taxonomy AS tax ON term.term_id = tax.term_id WHERE tax.taxonomy = "product_cat" ORDER BY term.`name`');
        foreach ($cats as $oneCat) {
            $categories[$oneCat->term_id] = $oneCat->name;
        }

        $filters['woopurchased'] = new stdClass();
        $filters['woopurchased']->name = acym_translation_sprintf('ACYM_COMBINED_TRANSLATIONS', 'WooCommerce', acym_translation('ACYM_PURCHASED'));
        $filters['woopurchased']->option = '<div class="cell grid-x grid-margin-x">';

        $filters['woopurchased']->option .= '<div class="cell acym_vcenter shrink">'.acym_translation('ACYM_BOUGHT').'</div>';

        $filters['woopurchased']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode(
            [
                'plugin' => 'plgAcymWoocommerce',
                'trigger' => 'searchProduct',
            ]
        );
        $filters['woopurchased']->option .= acym_select(
            [],
            'acym_action[filters][__numor__][__numand__][woopurchased][product]',
            null,
            'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_AT_LEAST_ONE_PRODUCT', true).'" data-params="'.acym_escape($ajaxParams).'"'
        );
        $filters['woopurchased']->option .= '</div>';

        $filters['woopurchased']->option .= '<div class="intext_select_automation cell">';
        $filters['woopurchased']->option .= acym_select($categories, 'acym_action[filters][__numor__][__numand__][woopurchased][category]', 'any', 'class="acym__select"');
        $filters['woopurchased']->option .= '</div>';

        $filters['woopurchased']->option .= '</div>';

        $filters['woopurchased']->option .= '<div class="cell grid-x grid-margin-x">';
        $filters['woopurchased']->option .= acym_dateField('acym_action[filters][__numor__][__numand__][woopurchased][datemin]', '', 'cell shrink');
        $filters['woopurchased']->option .= '<span class="acym__content__title__light-blue acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $filters['woopurchased']->option .= '<span class="acym_vcenter">'.acym_translation('ACYM_DATE_CREATED').'</span>';
        $filters['woopurchased']->option .= '<span class="acym__content__title__light-blue acym_vcenter margin-bottom-0 cell shrink"><</span>';
        $filters['woopurchased']->option .= acym_dateField('acym_action[filters][__numor__][__numand__][woopurchased][datemax]', '', 'cell shrink');
        $filters['woopurchased']->option .= '</div>';


        $paymentMethods = ['any' => acym_translation('ACYM_ANY_PAYMENT_METHOD')];
        if (function_exists('WC')) {
            $payments = WC()->payment_gateways()->payment_gateways;
            foreach ($payments as $oneMethod) {
                $paymentMethods[$oneMethod->id] = $oneMethod->title;
            }
        }

        $filters['wooreminder'] = new stdClass();
        $filters['wooreminder']->name = acym_translation_sprintf('ACYM_COMBINED_TRANSLATIONS', 'WooCommerce', acym_translation('ACYM_REMINDER'));
        $filters['wooreminder']->option = '<div class="cell">';
        $filters['wooreminder']->option .= acym_translation_sprintf(
            'ACYM_ORDER_WITH_STATUS',
            '<input type="number" name="acym_action[filters][__numor__][__numand__][wooreminder][days]" value="1" min="1" class="intext_input"/>',
            '<div class="intext_select_automation cell margin-right-1">'.acym_select(
                [
                    'wc-pending' => _x('Pending payment', 'Order status', 'woocommerce'),
                    'wc-processing' => _x('Processing', 'Order status', 'woocommerce'),
                    'wc-on-hold' => _x('On hold', 'Order status', 'woocommerce'),
                    'wc-completed' => _x('Completed', 'Order status', 'woocommerce'),
                    'wc-cancelled' => _x('Cancelled', 'Order status', 'woocommerce'),
                    'wc-refunded' => _x('Refunded', 'Order status', 'woocommerce'),
                    'wc-failed' => _x('Failed', 'Order status', 'woocommerce'),
                ],
                'acym_action[filters][__numor__][__numand__][wooreminder][status]',
                'wc-pending',
                'class="acym__select"'
            ).'</div>'
        );
        $filters['wooreminder']->option .= '<div class="intext_select_automation cell">';
        $filters['wooreminder']->option .= acym_select(
            $paymentMethods,
            'acym_action[filters][__numor__][__numand__][wooreminder][payment]',
            'any',
            'class="acym__select"'
        );
        $filters['wooreminder']->option .= '</div>';
        $filters['wooreminder']->option .= '</div>';
    }

    public function onAcymProcessFilterCount_woopurchased(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_woopurchased($query, $options, $num);

        return acym_translation_sprintf('ACYM_SELECTED_USERS', $query->count());
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
                $conditions[] = 'post'.$num.'.post_date > '.acym_escapeDB(acym_date($options['datemin'], "Y-m-d H:i:s"));
            }
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (!is_numeric($options['datemax'])) $options['datemax'] = strtotime($options['datemax']);
            if (!empty($options['datemax'])) {
                $conditions[] = 'post'.$num.'.post_date < '.acym_escapeDB(acym_date($options['datemax'], "Y-m-d H:i:s"));
            }
        }

        $query->join['woopurchased_post'.$num] = '#__posts AS post'.$num.' ON '.implode(' AND ', $conditions);

        $query->join['woopurchased_postmeta'.$num] = '#__postmeta AS postmeta'.$num.' ON postmeta'.$num.'.post_id = post'.$num.'.ID AND postmeta'.$num.'.meta_value = user.cms_id AND postmeta'.$num.'.meta_value != 0 AND postmeta'.$num.'.meta_key = "_customer_user"';

        if (!empty($options['product'])) {
            $query->join['woopurchased_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' ON post'.$num.'.ID = woooi'.$num.'.order_id AND woooi'.$num.'.order_item_type = "line_item"';
            $query->join['woopurchased_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id AND woooim'.$num.'.meta_key = "_product_id" AND woooim'.$num.'.meta_value = '.intval($options['product']);
        } elseif (!empty($options['category']) && $options['category'] != 'any') {
            $query->join['woopurchased_order_items'.$num] = '#__woocommerce_order_items AS woooi'.$num.' ON post'.$num.'.ID = woooi'.$num.'.order_id AND woooi'.$num.'.order_item_type = "line_item"';
            $query->join['woopurchased_order_itemmeta'.$num] = '#__woocommerce_order_itemmeta AS woooim'.$num.' ON woooi'.$num.'.order_item_id = woooim'.$num.'.order_item_id AND woooim'.$num.'.meta_key = "_product_id"';
            $query->join['woopurchased_cat_map'.$num] = '#__term_relationships AS termrel'.$num.' ON termrel'.$num.'.object_id = woooim'.$num.'.meta_value';
            $query->join['woopurchased_cat'.$num] = '#__term_taxonomy AS termtax'.$num.' ON termtax'.$num.'.term_taxonomy_id = termrel'.$num.'.term_taxonomy_id AND termtax'.$num.'.term_id = '.intval($options['category']);
        }
    }

    public function onAcymProcessCondition_woopurchased(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_woopurchased($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function onAcymProcessFilter_woopurchased(&$query, $options, $num)
    {
        $this->processConditionFilter_woopurchased($query, $options, $num);
    }

    public function onAcymProcessFilterCount_wooreminder(&$query, $options, $num)
    {
        $this->onAcymProcessFilter_wooreminder($query, $options, $num);

        return acym_translation_sprintf('ACYM_SELECTED_USERS', $query->count());
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

    public function onAcymProcessFilter_wooreminder(&$query, $options, $num)
    {
        $this->processConditionFilter_wooreminder($query, $options, $num);
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

            $orderStatus = [
                'wc-pending' => _x('Pending payment', 'Order status', 'woocommerce'),
                'wc-processing' => _x('Processing', 'Order status', 'woocommerce'),
                'wc-on-hold' => _x('On hold', 'Order status', 'woocommerce'),
                'wc-completed' => _x('Completed', 'Order status', 'woocommerce'),
                'wc-cancelled' => _x('Cancelled', 'Order status', 'woocommerce'),
                'wc-refunded' => _x('Refunded', 'Order status', 'woocommerce'),
                'wc-failed' => _x('Failed', 'Order status', 'woocommerce'),
            ];

            $automationCondition = acym_translation_sprintf(
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

            $cats = acym_loadObjectList('SELECT term.`term_id`, term.`name` FROM #__terms AS term JOIN #__term_taxonomy AS tax ON term.term_id = tax.term_id WHERE tax.taxonomy = "product_cat"', 'term_id');
            $category = empty($cats[$automationCondition['woopurchased']['category']]) ? acym_translation('ACYM_ANY_CATEGORY') : $cats[$automationCondition['woopurchased']['category']]->name;

            $finalText = acym_translation_sprintf('ACYM_CONDITION_PURCHASED', $product, $category);

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
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }

    public function onRegacyOptionsDisplay($lists)
    {
        if (!is_plugin_active('woocommerce/woocommerce.php')) return;

        ?>
		<div class="acym__configuration__subscription acym__content acym_area padding-vertical-1 padding-horizontal-2">
			<div class="acym_area_title"><?php echo acym_escape(acym_translation_sprintf('ACYM_XX_INTEGRATION', 'WooCommerce')); ?></div>

			<div class="grid-x">
				<div class="cell grid-x grid-margin-x">
                    <?php
                    $subOptionTxt = acym_translation_sprintf('ACYM_SUBSCRIBE_OPTION_ON_XX_CHECKOUT', 'WooCommerce').acym_info(acym_translation('ACYM_SUBSCRIBE_OPTION_ON_XX_CHECKOUT_DESC'));
                    echo acym_switch(
                        'config[woocommerce_sub]',
                        $this->config->get('woocommerce_sub'),
                        $subOptionTxt,
                        [],
                        'xlarge-3 medium-5 small-9',
                        'auto',
                        'tiny',
                        'acym__config__woocommerce_sub'
                    );
                    ?>
				</div>
				<div class="cell grid-x" id="acym__config__woocommerce_sub">
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__woocommerce-text">
                            <?php echo acym_translation('ACYM_SUBSCRIBE_CAPTION').acym_info(acym_translation('ACYM_SUBSCRIBE_CAPTION_OPT_DESC')); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
						<input type="text" name="config[woocommerce_text]" id="acym__config__woocommerce-text" value="<?php echo acym_escape($this->config->get('woocommerce_text')); ?>" />
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__woocommerce-autolists">
                            <?php echo acym_translation('ACYM_AUTO_SUBSCRIBE_TO').acym_info(acym_translation('ACYM_SUBSCRIBE_OPTION_AUTO_SUBSCRIBE_TO_DESC')); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo acym_selectMultiple(
                            $lists,
                            'config[woocommerce_autolists]',
                            explode(',', $this->config->get('woocommerce_autolists')),
                            ['class' => 'acym__select', 'id' => 'acym__config__woocommerce-autolists'],
                            'id',
                            'name'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
				</div>
			</div>
		</div>
        <?php
    }

    public function onBeforeSaveConfigFields(&$formData)
    {
        $formData['woocommerce_autolists'] = !empty($formData['woocommerce_autolists']) ? $formData['woocommerce_autolists'] : [];
    }

    public function onAcymIsTrackingWoocommerce(&$trackingWoocommerce)
    {
        $trackingWoocommerce = $this->getParam('track', 0) == 1;
    }

    public function formatCookie(&$cookie, &$formattedCookie)
    {
        $cookie = explode('_', $cookie);

        foreach ($cookie as $value) {
            $value = explode('-', $value);
            $formattedCookie[$value[0]] = $value[1];
        }
    }

    public function getCurrency(&$currency)
    {
        if (empty($currency)) $currency = get_woocommerce_currency();
        $woocommerceCurrencies = get_woocommerce_currency_symbols();
        $currency = $woocommerceCurrencies[$currency];
    }

    public function onAcymInitWordpressAddons()
    {
        add_filter('woocommerce_checkout_fields', [$this, 'addSubsciptionFieldWC']);
        add_action('woocommerce_checkout_order_processed', [$this, 'subscribeUserOnCheckoutWC'], 15, 3);
        if (acym_isTrackingSalesActive()) {
            add_action('woocommerce_payment_successful_result', [$this, 'trackingWoocommerce'], 10, 2);
            add_action('init', [$this, 'trackingWoocommerceAddCookie']);
        }
    }

    public function acym_displayTrackingMessage(&$message)
    {

        $remindme = json_decode($this->config->get('remindme', '[]'), true);

        if ($this->getParam('track', 0) != 1 && acym_isExtensionActive('woocommerce/woocommerce.php') && acym_isAdmin() && ACYM_CMS == 'wordpress' && !in_array('woocommerce_tracking', $remindme)) {
            $message = acym_translation('ACYM_WOOCOMMERCE_TRACKING_INFO');
            $message .= ' <a target="_blank" href="https://docs.acymailing.com/addons/wordpress-add-ons/woocommerce#tracking">'.acym_translation('ACYM_READ_MORE').'</a>';
            $message .= ' <a href="#" class="acym__do__not__remindme acym__do__not__remindme__info" title="woocommerce_tracking">'.acym_translation('ACYM_DO_NOT_REMIND_ME').'</a>';
            acym_display($message, 'info', false);
        } elseif (!in_array('woocommerce_tracking', $remindme)) {
            $remindme[] = 'woocommerce_tracking';
            $this->config->save(['remindme' => json_encode($remindme)]);
        }
    }

    public function trackingWoocommerceAddCookie()
    {
        $trackingWoo = acym_getVar('string', 'linkReferal', '');
        if (empty($trackingWoo)) return;

        $trackingWoo = explode('-', $trackingWoo);

        $hours = $this->getParam('cookie_expire', 1);

        $time = time() + (3600 * $hours);

        setcookie('acym_track_woocommerce', 'mailid-'.$trackingWoo[0].'_userid-'.$trackingWoo[1], $time, COOKIEPATH, COOKIE_DOMAIN);
    }

    public function trackingWoocommerce($result, $order_id)
    {
        if (empty($_COOKIE['acym_track_woocommerce'])) return $result;
        $cookie = $_COOKIE['acym_track_woocommerce'];

        $formattedCookie = [];

        acym_trigger('formatCookie', [&$cookie, &$formattedCookie], 'plgAcymWoocommerce');

        if (empty($formattedCookie['userid']) || empty($formattedCookie['mailid'])) return $result;
        $userStatClass = acym_get('class.userstat');
        $userStat = $userStatClass->getOneByMailAndUserId($formattedCookie['mailid'], $formattedCookie['userid']);
        if (empty($userStat)) return $result;
        unset($userStat->statusSending);
        unset($userStat->open);
        unset($userStat->open_date);

        $order = wc_get_order($order_id);
        $currency = $order->get_currency();
        if (empty($currency)) return $result;

        $total = (float)$order->get_total() - $order->get_total_tax() - $order->get_total_shipping() - $order->get_shipping_tax();

        $userStat->tracking_sale = empty($userStat->tracking_sale) ? $total : $userStat->tracking_sale + $total;
        $userStat->currency = $currency;

        $userStatClass->save($userStat);

        return $result;
    }


    /**
     * Subscribe user when the WooCommerce checkout is processed
     *
     * @param $order_id    : WooCommerce order ID
     * @param $posted_data : All data WooCommerce will get from form on checkout process
     * @param $order       : WooCommerce order
     */
    public function subscribeUserOnCheckoutWC($order_id, $posted_data, $order)
    {
        $config = acym_config();
        if (!$config->get('woocommerce_sub', 0)) return;

        if (empty($posted_data['billing_email']) || empty($posted_data['acym_regacy_sub'])) return;


        // Get existing AcyMailing user or create one
        $userClass = acym_get('class.user');

        $user = $userClass->getOneByEmail($posted_data['billing_email']);
        if (empty($user)) {
            $user = new stdClass();
            $user->email = $posted_data['billing_email'];
            $userName = [];
            if (!empty($posted_data['billing_first_name'])) $userName[] = $posted_data['billing_first_name'];
            if (!empty($posted_data['billing_last_name'])) $userName[] = $posted_data['billing_last_name'];
            if (!empty($userName)) $user->name = implode(' ', $userName);
            $user->source = 'woocommerce';
            $user->id = $userClass->save($user);
        }

        if (empty($user->id)) return;

        // Subscribe the user
        $listsToSubscribe = $config->get('woocommerce_autolists', '');
        if (empty($listsToSubscribe)) return;
        $hiddenLists = explode(',', $listsToSubscribe);
        $userClass->subscribe($user->id, $hiddenLists);
    }

    /**
     * Declare the field for WooCommerce to display it in the checkout and get it on checkout validation.
     *
     * @param $fields (available WooCommerce fields)
     *
     * @return mixed
     */
    public function addSubsciptionFieldWC($fields)
    {
        $config = acym_config();
        if (!$config->get('woocommerce_sub', 0)) return $fields;

        // Add our field at the end of the billing fields (where the email is mandatory)
        $displayTxt = !empty($config->get('woocommerce_text')) ? $config->get('woocommerce_text') : acym_translation('ACYM_SUBSCRIBE_NEWSLETTER');
        $acyfield = [
            'type' => 'checkbox',
            'label' => $displayTxt,
            'required' => false,
            'class' => ['form-row-wide'],
        ];
        $fields['billing']['acym_regacy_sub'] = $acyfield;

        return $fields;
    }
}
