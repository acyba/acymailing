<?php

use AcyMailing\Helpers\TabHelper;

trait VirtuemartInsertion
{
    public function getStandardStructure(string &$customView): void
    {
        $tag = new stdClass();
        $tag->id = 0;

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = '{title}';
        $format->afterTitle = acym_translation('ACYM_PRICE').': {price}';
        $format->afterArticle = '';
        $format->imagePath = '{image}';
        $format->description = '{shortdesc}';
        $format->link = '{link}';
        $format->customFields = [];
        $customView = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';
    }

    public function initCustomOptionsCustomView()
    {
        $customFieldsObjects = acym_loadObjectList(
            'SELECT cf.virtuemart_custom_id, cf.custom_title, cf.show_title, cf.field_type, cfvalues.customfield_value 
                FROM #__virtuemart_customs AS cf 
                JOIN #__virtuemart_product_customfields AS cfvalues 
                    ON cf.virtuemart_custom_id = cfvalues.virtuemart_custom_id 
                    AND cf.field_type IN ("B", "I", "S", "D", "M", "X", "Y")'
        );

        foreach ($customFieldsObjects as $oneCustom) {
            $this->customOptions['{'.$oneCustom->custom_title.'}'] = [$oneCustom->custom_title];
        }
    }

    public function initReplaceOptionsCustomView(): void
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'picthtml' => ['ACYM_IMAGE'],
            'readmore' => ['ACYM_READ_MORE'],
        ];
    }

    public function initElementOptionsCustomView(): void
    {
        $product = acym_loadResult('SELECT virtuemart_product_id FROM #__virtuemart_products ORDER BY virtuemart_product_id desc');
        if (false === $this->loadLibraries(null) || empty($product)) return;
        $vmProductModel = VmModel::getModel('product');
        // VirtueMart generates a PHP notice even when the correct data is passed
        $element = @$vmProductModel->getProduct($product, true, true, true, 1);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function insertionOptions(?object $defaultValues = null): void
    {
        if (!in_array(acym_getPrefix().'virtuemart_products_'.$this->lang, acym_getTableList())) {
            acym_display('The language '.$this->lang.' isn\'t in the VirtueMart\'s Language settings, make sure it is the case first.', 'error', false);
            exit;
        }

        // For all insertion options
        acym_loadLanguageFile('com_virtuemart', JPATH_ADMINISTRATOR.'/components/com_virtuemart');
        // For the shopper groups translation
        acym_loadLanguageFile('com_virtuemart_shoppers', JPATH_SITE.'/components/com_virtuemart');

        $this->defaultValues = $defaultValues;
        $this->categories = acym_loadObjectList(
            'SELECT cat.category_child_id AS id, cat.category_parent_id AS parent_id, cattrans.category_name AS title 
            FROM `#__virtuemart_category_categories` AS cat 
            JOIN `#__virtuemart_categories` AS category ON category.virtuemart_category_id = cat.id 
            JOIN `#__virtuemart_categories_'.$this->lang.'` AS cattrans ON cat.category_child_id = cattrans.virtuemart_category_id
            WHERE category.published = 1'
        );

        $tabHelper = new TabHelper();
        $identifier = $this->name;
        $tabHelper->startTab(acym_translation('ACYM_ONE_BY_ONE'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $displayOptions = [
            [
                'title' => 'ACYM_DISPLAY',
                'type' => 'checkbox',
                'name' => 'display',
                'options' => $this->displayOptions,
            ],
        ];

        $customFields = acym_loadObjectList(
            'SELECT virtuemart_custom_id, custom_title 
            FROM #__virtuemart_customs 
            WHERE published = 1 
                AND field_type IN ("B","I","S","D","M","X","Y")'
        );

        if (!empty($customFields)) {
            $formattedCustomFields = [];
            foreach ($customFields as $oneCF) {
                $formattedCustomFields[$oneCF->virtuemart_custom_id] = [$oneCF->custom_title, false];
            }

            $displayOptions[] = [
                'title' => 'ACYM_CUSTOM_FIELDS',
                'type' => 'checkbox',
                'name' => 'custom',
                'options' => $formattedCustomFields,
            ];
        }

        // Init shopper group price selection
        $shoppergroups = acym_loadObjectList(
            'SELECT virtuemart_shoppergroup_id AS `value`, shopper_group_name AS `text` 
            FROM #__virtuemart_shoppergroups 
            WHERE published = 1'
        );
        if (empty($shoppergroups)) $shoppergroups = [];
        foreach ($shoppergroups as $i => $oneShoppergroup) {
            $shoppergroups[$i]->text = acym_translation($oneShoppergroup->text);
        }
        $firstVal = (object)['value' => 0, 'text' => ' - - - '];
        array_unshift($shoppergroups, $firstVal);

        $displayOptions = array_merge(
            $displayOptions,
            [
                [
                    'title' => 'COM_VIRTUEMART_SHOPPERGROUP_LIST_DISCOUNT',
                    'type' => 'select',
                    'name' => 'shoppergroup',
                    'options' => $shoppergroups,
                ],
                [
                    'title' => 'ACYM_CLICKABLE_TITLE',
                    'type' => 'boolean',
                    'name' => 'clickable',
                    'default' => true,
                ],
                [
                    'title' => 'ACYM_CLICKABLE_IMAGE',
                    'type' => 'boolean',
                    'name' => 'clickableimg',
                    'default' => false,
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
                [
                    'title' => 'ACYM_AUTO_LOGIN',
                    'tooltip' => 'ACYM_AUTO_LOGIN_DESCRIPTION_WARNING',
                    'type' => 'boolean',
                    'name' => 'autologin',
                    'default' => false,
                ],
            ]
        );

        $zoneContent = $this->getFilteringZone().$this->prepareListing();
        $this->displaySelectionZone($zoneContent);
        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'individual', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = 'auto'.$this->name;
        $tabHelper->startTab(acym_translation('ACYM_BY_CATEGORY'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        // Init manufacturers filter
        $manufacturers = acym_loadObjectList(
            'SELECT virtuemart_manufacturer_id AS `value`, mf_name AS `text` 
            FROM #__virtuemart_manufacturers_'.$this->lang.' AS manufacturer 
            ORDER BY mf_name ASC'
        );

        if (empty($manufacturers)) $manufacturers = [];
        $firstVal = (object)['value' => 0, 'text' => ' - - - '];
        array_unshift($manufacturers, $firstVal);

        $catOptions = [
            [
                'title' => 'ACYM_ORDER_BY',
                'type' => 'select',
                'name' => 'order',
                'options' => [
                    'virtuemart_product_id' => 'ACYM_ID',
                    'created_on' => 'ACYM_PUBLISHING_DATE',
                    'modified_on' => 'ACYM_MODIFICATION_DATE',
                    'product_name' => 'ACYM_TITLE',
                    'rand' => 'ACYM_RANDOM',
                ],
            ],
            [
                'title' => 'ACYM_ONLY_FEATURED',
                'type' => 'boolean',
                'name' => 'featured',
                'default' => false,
            ],
            [
                'title' => 'COM_VIRTUEMART_PRODUCT_IN_STOCK',
                'type' => 'boolean',
                'name' => 'instock',
                'default' => false,
            ],
            [
                'title' => 'COM_VIRTUEMART_MANUFACTURER',
                'type' => 'select',
                'name' => 'manufacturer',
                'options' => $manufacturers,
            ],
        ];
        $this->autoContentOptions($catOptions);

        $this->autoCampaignOptions($catOptions);

        $displayOptions = array_merge($displayOptions, $catOptions);

        $this->displaySelectionZone($this->getCategoryListing());
        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

        $tabHelper->endTab();
        $identifier = $this->name.'_coupon';
        $tabHelper->startTab(acym_translation('ACYM_COUPON'), !empty($this->defaultValues->defaultPluginTab) && $identifier === $this->defaultValues->defaultPluginTab);

        $vendors = acym_loadObjectList(
            'SELECT virtuemart_vendor_id AS `value`, vendor_name AS `text` 
            FROM #__virtuemart_vendors 
            ORDER BY `vendor_name` ASC 
            LIMIT 500'
        );

        $displayOptions = [
            [
                'title' => 'ACYM_DISCOUNT_CODE',
                'type' => 'text',
                'name' => 'code',
                'default' => '[name][key][value]',
                'class' => 'acym_plugin__larger_text_field',
            ],
            [
                'title' => 'COM_VIRTUEMART_COUPON_VALUE_VALID_AT',
                'type' => 'number',
                'name' => 'min',
                'default' => '',
            ],
            [
                'title' => 'COM_VIRTUEMART_COUPON_PERCENT_TOTAL',
                'type' => 'select',
                'name' => 'type',
                'options' => [
                    'total' => 'COM_VIRTUEMART_COUPON_TOTAL',
                    'percent' => 'COM_VIRTUEMART_COUPON_PERCENT',
                ],
            ],
            [
                'title' => 'ACYM_VALUE',
                'type' => 'number',
                'name' => 'amount',
                'default' => '0',
            ],
            [
                'title' => 'ACYM_MAX_ATTEMPT',
                'type' => 'number',
                'name' => 'attempt',
                'default' => '0',
            ],
            [
                'title' => 'COM_VIRTUEMART_COUPON_START',
                'type' => 'date',
                'name' => 'start',
                'default' => '',
                'relativeDate' => '+',
            ],
            [
                'title' => 'COM_VIRTUEMART_COUPON_EXPIRY',
                'type' => 'date',
                'name' => 'end',
                'default' => '',
                'relativeDate' => '+',
            ],
            [
                'title' => 'COM_VIRTUEMART_COUPON_TYPE',
                'type' => 'select',
                'name' => 'ctype',
                'options' => [
                    'permanent' => 'COM_VIRTUEMART_COUPON_TYPE_PERMANENT',
                    'gift' => 'COM_VIRTUEMART_COUPON_TYPE_GIFT',
                ],
            ],
            [
                'title' => 'COM_VIRTUEMART_VENDOR',
                'type' => 'select',
                'name' => 'vendor',
                'options' => $vendors,
            ],

        ];

        $this->pluginHelper->displayOptions($displayOptions, $identifier, 'simple', $this->defaultValues);

        $tabHelper->endTab();

        $tabHelper->display('plugin');
    }

    public function prepareListing(): string
    {
        acym_loadLanguageFile('com_virtuemart', JPATH_ADMINISTRATOR.'/components/com_virtuemart');

        $this->querySelect = 'SELECT product.virtuemart_product_id, product.product_sku, producttrans.product_name ';
        $this->query = 'FROM #__virtuemart_products AS product ';
        $this->query .= 'JOIN #__virtuemart_products_'.$this->lang.' AS producttrans ON product.virtuemart_product_id = producttrans.virtuemart_product_id ';
        $this->filters = [];
        $this->filters[] = 'product.published = 1';
        $this->searchFields = ['product.virtuemart_product_id', 'product.product_sku', 'producttrans.product_name'];
        $this->pageInfo->order = 'product.virtuemart_product_id';
        $this->elementIdTable = 'product';
        $this->elementIdColumn = 'virtuemart_product_id';

        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'author') {
            $this->filters[] = 'product.created_by = '.intval(acym_currentUserId());
        }

        parent::prepareListing();

        if (!empty($this->pageInfo->filter_cat)) {
            $this->query .= 'JOIN #__virtuemart_product_categories AS cat ON product.virtuemart_product_id = cat.virtuemart_product_id ';
            $this->filters[] = 'cat.virtuemart_category_id = '.intval($this->pageInfo->filter_cat);
        }

        $listingOptions = [
            'header' => [
                'product_name' => [
                    'label' => 'ACYM_TITLE',
                    'size' => '8',
                ],
                'product_sku' => [
                    'label' => 'COM_VIRTUEMART_PRODUCT_SKU',
                    'size' => '3',
                ],
                'virtuemart_product_id' => [
                    'label' => 'ACYM_ID',
                    'size' => '1',
                    'class' => 'text-center',
                ],
            ],
            'id' => 'virtuemart_product_id',
            'rows' => $this->getElements(),
        ];

        return $this->getElementsListing($listingOptions);
    }

    public function replaceContent(object &$email): void
    {
        $this->replaceMultiple($email);
        $this->replaceOne($email);
    }

    public function generateByCategory(object &$email): object
    {
        $tags = $this->pluginHelper->extractTags($email, 'auto'.$this->name);
        $this->tags = [];

        if (empty($tags)) return $this->generateCampaignResult;

        foreach ($tags as $oneTag => $parameter) {
            if (isset($this->tags[$oneTag])) continue;

            $query = 'SELECT DISTINCT product.`virtuemart_product_id` 
                    FROM #__virtuemart_products AS product 
                    JOIN #__virtuemart_products_'.$this->lang.' AS producttrans ON product.virtuemart_product_id = producttrans.virtuemart_product_id 
                    LEFT JOIN #__virtuemart_product_categories AS cat ON product.virtuemart_product_id = cat.virtuemart_product_id ';

            $where = [];

            $selectedArea = $this->getSelectedArea($parameter);
            if (!empty($selectedArea)) {
                $where[] = 'cat.virtuemart_category_id IN ('.implode(',', $selectedArea).')';
            }

            $where[] = 'product.published = 1';
            if (!empty($parameter->featured)) $where[] = 'product.product_special = 1';
            if (!empty($parameter->instock)) $where[] = 'product.product_in_stock > 0';

            if (!empty($parameter->manufacturer)) {
                $query .= 'JOIN #__virtuemart_product_manufacturers AS manufacturer ON manufacturer.virtuemart_product_id = product.virtuemart_product_id ';
                $where[] = 'manufacturer.virtuemart_manufacturer_id = '.intval($parameter->manufacturer);
            }
            if (!empty($parameter->min_publish)) {
                $parameter->min_publish = acym_date(acym_replaceDate($parameter->min_publish), 'Y-m-d H:i:s', false);
                $where[] = 'product.created_on >= '.acym_escapeDB($parameter->min_publish);
            }

            if (!empty($parameter->onlynew)) {
                $lastGenerated = $this->getLastGenerated($email->id);
                if (!empty($lastGenerated)) {
                    $where[] = 'product.created_on > '.acym_escapeDB(acym_date($lastGenerated, 'Y-m-d H:i:s', false));
                }
            }

            $query .= ' WHERE ('.implode(') AND (', $where).')';

            $this->tags[$oneTag] = $this->finalizeCategoryFormat($query, $parameter);
        }

        return $this->generateCampaignResult;
    }

    protected function loadLibraries(?object $email): bool
    {
        acym_loadLanguageFile('com_virtuemart', JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart');

        $vmPath = ACYM_ROOT.'administrator'.DS.'components'.DS.'com_virtuemart'.DS;
        if (file_exists($vmPath.'helpers'.DS.'config.php')) include_once $vmPath.'helpers'.DS.'config.php';
        if (file_exists($vmPath.'helpers'.DS.'vobject.php')) include_once $vmPath.'helpers'.DS.'vobject.php';
        if (file_exists($vmPath.'helpers'.DS.'vmmodel.php')) include_once $vmPath.'helpers'.DS.'vmmodel.php';
        if (file_exists($vmPath.'helpers'.DS.'calculationh.php')) include_once $vmPath.'helpers'.DS.'calculationh.php';
        if (file_exists($vmPath.'models'.DS.'product.php')) include_once $vmPath.'models'.DS.'product.php';
        vmConfig::loadConfig();
        if (method_exists('vmConfig', 'setdbLanguageTag')) vmConfig::setdbLanguageTag();

        if (class_exists('calculationHelper')) include_once __DIR__.DS.'fixedcalc.php';

        return true;
    }

    public function replaceIndividualContent(object $tag): string
    {
        $tag->shoppergroup = empty($tag->shoppergroup) ? 0 : intval($tag->shoppergroup);

        $vmProductModel = VmModel::getModel('product');
        $element = $vmProductModel->getProduct($tag->id, true, true, true, 1, [$tag->shoppergroup]);
        $vmProductModel->addImages($element);

        $tag->display = empty($tag->display) ? [] : explode(',', $tag->display);
        $tag->custom = empty($tag->custom) ? [] : explode(',', $tag->custom);

        if (empty($element)) return '';

        $varFields = [];
        foreach ($element as $fieldName => $oneField) {
            if (!is_array($oneField) && !is_object($oneField)) $varFields['{'.$fieldName.'}'] = $oneField;
        }

        $link = 'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$element->virtuemart_product_id.'&virtuemart_category_id='.$element->virtuemart_category_id;
        $link = $this->finalizeLink($link, $tag);
        $varFields['{link}'] = $link;

        $title = '';
        $afterTitle = '';
        $imagePath = '';
        $contentText = '';
        $customFields = [];
        $afterArticle = '';

        $varFields['{title}'] = $element->product_name;
        if (in_array('title', $tag->display)) $title = $varFields['{title}'];

        $this->preparePrices($varFields, $element, [$tag->shoppergroup]);

        $varFields['{price}'] = $varFields['{finalPrice}'];
        if (in_array('price', $tag->display)) {
            $afterTitle .= $varFields['{finalPrice}'];
        }

        if (!empty($element->images)) {
            $mainPict = reset($element->images);
            $pictvar = 'file_url';
            if (empty($mainPict->file_url) || !file_exists(JPATH_SITE.DS.$mainPict->file_url)) $pictvar = 'file_url_thumb';
            if (!empty($mainPict->$pictvar) && file_exists(JPATH_SITE.DS.$mainPict->$pictvar)) $imagePath = $mainPict->$pictvar;
        }
        if (!in_array('image', $tag->display)) $imagePath = '';
        $varFields['{image}'] = $imagePath;
        $varFields['{picthtml}'] = '<img alt="" src="'.$imagePath.'">';

        $varFields['{shortdesc}'] = '<p>'.$element->product_s_desc.'</p>';
        if (in_array('shortdesc', $tag->display)) $contentText .= $varFields['{shortdesc}'];
        $varFields['{desc}'] = '<p>'.$element->product_desc.'</p>';
        if (in_array('desc', $tag->display)) $contentText .= $varFields['{desc}'];

        $categories = acym_loadObjectList(
            'SELECT cattrans.virtuemart_category_id, cattrans.category_name 
                FROM #__virtuemart_categories_'.$this->lang.' AS cattrans 
                JOIN #__virtuemart_product_categories AS map ON cattrans.virtuemart_category_id = map.virtuemart_category_id 
                JOIN #__virtuemart_categories AS cat ON cat.virtuemart_category_id = cattrans.virtuemart_category_id 
                WHERE cat.published = 1 
                    AND map.virtuemart_product_id = '.intval($tag->id)
        );
        $cats = [];
        if (!empty($categories)) {
            foreach ($categories as $oneCategory) {
                $catUrl = $this->finalizeLink('index.php?option=com_virtuemart&view=category&virtuemart_category_id='.$oneCategory->virtuemart_category_id, $tag);
                $cats[] = '<a href="'.$catUrl.'" target="_blank">'.$oneCategory->category_name.'</a>';
            }
        }
        $varFields['{cats}'] = empty($cats) ? '' : implode(', ', $cats);
        if (in_array('cats', $tag->display)) {
            $customFields[] = [
                $varFields['{cats}'],
                acym_translation('ACYM_CATEGORIES'),
            ];
        }

        acym_arrayToInteger($tag->custom);
        $customFieldsObjects = acym_loadObjectList(
            'SELECT cf.virtuemart_custom_id, cf.custom_title, cf.show_title, cf.field_type, cfvalues.customfield_value 
                FROM #__virtuemart_customs AS cf 
                JOIN #__virtuemart_product_customfields AS cfvalues 
                    ON cf.virtuemart_custom_id = cfvalues.virtuemart_custom_id 
                    AND cf.field_type IN ("B", "I", "S", "D", "M", "X", "Y") 
                    AND cfvalues.virtuemart_product_id = '.intval($tag->id)
        );

        // Group cf values per cf
        $fieldsValueList = [];
        foreach ($customFieldsObjects as $oneCustom) {
            $fieldsValueList[$oneCustom->virtuemart_custom_id][] = $oneCustom;
        }

        $customFieldsObjects = $fieldsValueList;
        foreach ($customFieldsObjects as $values) {
            $oneCustom = $values[0];
            if (count($values) > 1) {
                $val = $values[0]->customfield_value;
                foreach ($values as $i => $oneValue) {
                    $val = empty($i) ? $oneValue->customfield_value : $val.', '.$oneValue->customfield_value;
                }
                $oneCustom->customfield_value = $val;
            }

            if ($oneCustom->field_type == 'B') {
                $value = acym_translation($oneCustom->customfield_value == 1 ? 'ACYM_YES' : 'ACYM_NO');
            } elseif ($oneCustom->field_type == 'D') {
                $value = acym_date($oneCustom->customfield_value, acym_translation('ACYM_DATE_FORMAT_LC1'));
            } elseif ($oneCustom->field_type == 'M') {
                $currentImagePath = acym_loadResult(
                    'SELECT file_url 
                        FROM #__virtuemart_medias 
                        WHERE virtuemart_media_id = '.intval($oneCustom->customfield_value)
                );

                $value = empty($currentImagePath) ? '' : '<img src="'.$currentImagePath.'" alt=""/>';
            } else {
                $value = $oneCustom->customfield_value;
            }
            $varFields['{'.$oneCustom->custom_title.'}'] = $value;
        }
        if (!empty($tag->custom)) {
            foreach ($customFieldsObjects as $fieldId => $values) {
                if (!in_array($fieldId, $tag->custom)) continue;
                $oneCustom = $values[0];
                $customFields[] = [
                    $varFields['{'.$oneCustom->custom_title.'}'],
                    $oneCustom->show_title == 1 ? acym_translation($oneCustom->custom_title) : '',
                ];
            }
        }

        $varFields['{readmore}'] = '<a class="acymailing_readmore_link" style="text-decoration:none;" target="_blank" href="'.$link.'"><span class="acymailing_readmore">'.acym_escape(
                acym_translation('ACYM_READ_MORE')
            ).'</span></a>';
        if (in_array('readmore', $tag->display)) $afterArticle .= $varFields['{readmore}'];

        $format = new stdClass();
        $format->tag = $tag;
        $format->title = $title;
        $format->afterTitle = $afterTitle;
        $format->afterArticle = $afterArticle;
        $format->imagePath = $imagePath;
        $format->description = $contentText;
        $format->link = empty($tag->clickable) && empty($tag->clickableimg) ? '' : $link;
        $format->customFields = $customFields;
        $result = '<div class="acymailing_content">'.$this->pluginHelper->getStandardDisplay($format).'</div>';

        return $this->finalizeElementFormat($result, $tag, $varFields);
    }

    private function preparePrices(&$varFields, $element, $shoppergroups)
    {
        if (class_exists('fixedCalculationHelper')) {
            $calculator = new fixedCalculationHelper(1, 0, 0);
            $calculator->setCorrectShopperGroupIds($shoppergroups);
            $calculator->setVendorId($calculator->productVendorId);

            $element->prices = $calculator->getProductPrices($element, true, 1);
        }

        if (!empty($element->prices)) {
            foreach ($element->prices as $key => $val) {
                $element->$key = $val;
            }
        }

        $price1 = $element->product_price;
        if (!empty($element->product_override_price) && $element->override && $element->product_override_price != $element->product_price) {
            $price2 = $element->product_override_price;
        }

        // Add the tax:
        if ($this->getParam('vat', '1') === '1') {
            if (!empty($element->prices['basePriceWithTax'])) $price1 = $element->prices['basePriceWithTax'];
            if (
                !empty($element->prices['salesPrice'])
                && number_format($price1, 2, ',', ' ') != number_format($element->prices['salesPrice'], 2, ',', ' ')
            ) {
                $price2 = $element->prices['salesPrice'];
            }
        } else {
            if (!empty($element->prices['basePrice'])) $price1 = $element->prices['basePrice'];
            if (
                !empty($element->prices['discountedPriceWithoutTax'])
                && number_format($price1, 2, ',', ' ') != number_format($element->prices['discountedPriceWithoutTax'], 2, ',', ' ')
            ) {
                $price2 = $element->prices['discountedPriceWithoutTax'];
            }
        }

        $currencyHelper = CurrencyDisplay::getInstance($element->product_currency);
        $price = $currencyHelper->priceDisplay($price1, $element->product_currency);
        if (!empty($price2)) $price2 = $currencyHelper->priceDisplay($price2, $element->product_currency);

        $varFields['{finalPrice}'] = empty($price2) ? $price : '<span style="text-decoration: line-through;">'.$price.'</span> '.$price2;
        $varFields['{price}'] = $price;
        $varFields['{price2}'] = empty($price2) ? 0 : $price2;
    }

    public function replaceUserInformation(object &$email, ?object &$user, bool $send = true): void
    {
        $this->replaceCoupons($email, $user, $send);
    }

    private function replaceCoupons(&$email, &$user, $send = true)
    {
        $tags = $this->pluginHelper->extractTags($email, $this->name.'_coupon');
        if (empty($tags)) return;

        $tagsReplaced = [];
        foreach ($tags as $i => $oneTag) {
            if (isset($tagsReplaced[$i])) continue;

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
        if (
            empty($tag->code)
            || empty($tag->amount)
            || empty($tag->vendor)
            || empty($tag->type)
            || !in_array($tag->type, ['total', 'percent'])
            || empty($tag->ctype)
            || !in_array($tag->ctype, ['permanent', 'gift'])
        ) {
            return '';
        }

        $intAttributes = ['amount', 'min', 'vendor'];
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
            'coupon_code' => $couponCode,
            'percent_or_total' => $tag->type,
            'coupon_type' => $tag->ctype,
            'coupon_value' => $tag->amount,
            'virtuemart_vendor_id' => $tag->vendor,
            'virtuemart_coupon_max_attempt_per_user' => empty($tag->attempt) ? 0 : $tag->attempt,
        ];

        if (!empty($tag->min)) $coupon['coupon_value_valid'] = $tag->min;
        if (!empty($tag->start)) $coupon['coupon_start_date'] = acym_date(acym_replaceDate($tag->start), 'Y-m-d H:i:s');
        if (!empty($tag->end)) $coupon['coupon_expiry_date'] = acym_date(acym_replaceDate($tag->end), 'Y-m-d H:i:s');

        foreach ($coupon as $column => $oneValue) {
            $coupon[$column] = acym_escapeDB($oneValue);
        }

        acym_query('INSERT INTO #__virtuemart_coupons ('.implode(',', array_keys($coupon)).') VALUES ('.implode(',', $coupon).')');

        return $couponCode;
    }
}
