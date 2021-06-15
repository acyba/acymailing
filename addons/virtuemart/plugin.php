<?php

use AcyMailing\Classes\UserClass;
use AcyMailing\Classes\ListClass;
use AcyMailing\Libraries\acymPlugin;
use AcyMailing\Helpers\TabHelper;
use AcyMailing\Types\OperatorinType;
use AcyMailing\Types\OperatorType;

class plgAcymVirtuemart extends acymPlugin
{
    var $lang = null;

    public function __construct()
    {
        parent::__construct();
        $this->cms = 'Joomla';
        $this->installed = acym_isExtensionActive('com_virtuemart');
        if ($this->installed) {
            $params = JComponentHelper::getParams('com_languages');
            $this->lang = strtolower(str_replace('-', '_', $params->get('site', 'en-GB')));
        }

        $this->pluginDescription->name = 'VirtueMart';
        $this->pluginDescription->icon = ACYM_DYNAMICS_URL.basename(__DIR__).'/icon.png';
        $this->rootCategoryId = 0;

        if ($this->installed && acym_getVar('string', 'option', '') === 'com_acym') {
            $this->displayOptions = [
                'title' => ['ACYM_TITLE', true],
                'price' => ['ACYM_PRICE', true],
                'image' => ['ACYM_IMAGE', true],
                'shortdesc' => ['ACYM_SHORT_DESCRIPTION', true],
                'desc' => ['ACYM_DESCRIPTION', false],
                'cats' => ['ACYM_CATEGORIES', false],
                'readmore' => ['ACYM_READ_MORE', false],
            ];

            $this->initElementOptionsCustomView();
            $this->initReplaceOptionsCustomView();
            $this->initCustomOptionsCustomView();

            $this->settings = [
                'custom_view' => [
                    'type' => 'custom_view',
                    'tags' => array_merge($this->displayOptions, $this->replaceOptions, $this->elementOptions, $this->customOptions),
                ],
                'front' => [
                    'type' => 'select',
                    'label' => 'ACYM_FRONT_ACCESS',
                    'value' => 'all',
                    'data' => [
                        'all' => 'ACYM_ALL_ELEMENTS',
                        'author' => 'ACYM_ONLY_AUTHORS_ELEMENTS',
                        'hide' => 'ACYM_DONT_SHOW',
                    ],
                ],
                'vat' => [
                    'type' => 'switch',
                    'label' => 'ACYM_PRICE_WITH_TAX',
                    'value' => 1,
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

    public function initReplaceOptionsCustomView()
    {
        $this->replaceOptions = [
            'link' => ['ACYM_LINK'],
            'picthtml' => ['ACYM_IMAGE'],
            'readmore' => ['ACYM_READ_MORE'],
        ];
    }

    public function initElementOptionsCustomView()
    {
        $product = acym_loadResult('SELECT virtuemart_product_id FROM #__virtuemart_products ORDER BY virtuemart_product_id desc');
        if (false === $this->loadLibraries('') || empty($product)) return;
        $vmProductModel = VmModel::getModel('product');
        // VirtueMart generates a PHP notice even when the correct data is passed
        $element = @$vmProductModel->getProduct($product, true, true, true, 1);
        if (empty($element)) return;
        foreach ($element as $key => $value) {
            $this->elementOptions[$key] = [$key];
        }
    }

    public function getPossibleIntegrations()
    {
        if (!acym_isAdmin() && $this->getParam('front', 'all') === 'hide') return null;

        return $this->pluginDescription;
    }

    public function insertionOptions($defaultValues = null)
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
            JOIN `#__virtuemart_categories_'.$this->lang.'` AS cattrans ON cat.category_child_id = cattrans.virtuemart_category_id'
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
            ]
        );

        $zoneContent = $this->getFilteringZone().$this->prepareListing();
        echo $this->displaySelectionZone($zoneContent);
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'individual', $this->defaultValues);

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

        echo $this->displaySelectionZone($this->getCategoryListing());
        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'grouped', $this->defaultValues);

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

        echo $this->pluginHelper->displayOptions($displayOptions, $identifier, 'simple', $this->defaultValues);

        $tabHelper->endTab();

        $tabHelper->display('plugin');
    }

    public function prepareListing()
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

    public function replaceContent(&$email)
    {
        $this->replaceMultiple($email);
        $this->replaceOne($email);
    }

    public function generateByCategory(&$email)
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

    protected function loadLibraries($email)
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
            if (!empty($element->prices['salesPrice']) && number_format($price1, 2, ',', ' ') != number_format(
                    $element->prices['salesPrice'],
                    2,
                    ',',
                    ' '
                )) {
                $price2 = $element->prices['salesPrice'];
            }
        } else {
            if (!empty($element->prices['basePrice'])) $price1 = $element->prices['basePrice'];
            if (!empty($element->prices['discountedPriceWithoutTax']) && number_format($price1, 2, ',', ' ') != number_format(
                    $element->prices['discountedPriceWithoutTax'],
                    2,
                    ',',
                    ' '
                )) {
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

    public function replaceIndividualContent($tag)
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
        $link = $this->finalizeLink($link);
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
                $catUrl = $this->finalizeLink('index.php?option=com_virtuemart&view=category&virtuemart_category_id='.$oneCategory->virtuemart_category_id);
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
        if (empty($tag->code) || empty($tag->amount) || empty($tag->vendor) || empty($tag->type) || !in_array($tag->type, ['total', 'percent']) || empty($tag->ctype) || !in_array(
                $tag->ctype,
                ['permanent', 'gift']
            )) {
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
            $conditions['user']['vmgroups']->option .= acym_select($groups, 'acym_condition[conditions][__numor__][__numand__][vmgroups][group]', null, 'class="acym__select"');
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
                'class="acym__select acym__automation__conditions__fields__dropdown"'
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
                'class="acym__select"'
            ).'</div>'
        );
        $conditions['user']['vmreminder']->option .= '<div class="intext_select_automation cell">';
        $conditions['user']['vmreminder']->option .= acym_select(
            $paymentMethods,
            'acym_condition[conditions][__numor__][__numand__][vmreminder][payment]',
            '',
            'class="acym__select"'
        );
        $conditions['user']['vmreminder']->option .= '</div>';
        $conditions['user']['vmreminder']->option .= '</div>';


        // Placed orders
        $conditions['user']['vmpurchased'] = new stdClass();
        $conditions['user']['vmpurchased']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'VirtueMart', acym_translation('ACYM_PURCHASED'));
        $conditions['user']['vmpurchased']->option = '<div class="cell grid-x grid-margin-x">';

        $conditions['user']['vmpurchased']->option .= '<div class="cell acym_vcenter shrink">'.acym_translation('ACYM_BOUGHT').'</div>';

        $conditions['user']['vmpurchased']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode(['plugin' => __CLASS__, 'trigger' => 'searchProduct',]);
        $conditions['user']['vmpurchased']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][vmpurchased][product]',
            null,
            'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_AT_LEAST_ONE_PRODUCT', true).'" data-params="'.acym_escape($ajaxParams).'"'
        );
        $conditions['user']['vmpurchased']->option .= '</div>';

        $conditions['user']['vmpurchased']->option .= '<div class="intext_select_automation cell">';
        $ajaxParams = json_encode(['plugin' => __CLASS__, 'trigger' => 'searchCategory',]);
        $conditions['user']['vmpurchased']->option .= acym_select(
            [],
            'acym_condition[conditions][__numor__][__numand__][vmpurchased][category]',
            null,
            'class="acym__select acym_select2_ajax" data-placeholder="'.acym_translation('ACYM_ANY_CATEGORY', true).'" data-params="'.acym_escape($ajaxParams).'"'
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

    public function onAcymProcessCondition_vmgroups(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_vmgroups($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function onAcymProcessFilter_vmgroups(&$query, $options, $num)
    {
        $this->processConditionFilter_vmgroups($query, $options, $num);
    }

    private function processConditionFilter_vmgroups(&$query, $options, $num)
    {
        $defaultGroups = acym_loadResultArray('SELECT `virtuemart_shoppergroup_id` FROM `#__virtuemart_shoppergroups` WHERE `default` > 0');
        if (empty($defaultGroups)) $defaultGroups = [0];

        $join = '#__virtuemart_vmuser_shoppergroups AS vmgroup_'.$num.' ON user.cms_id = vmgroup_'.$num.'.virtuemart_user_id';
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

    public function onAcymProcessFilter_vmfield(&$query, $options, $num)
    {
        $this->processConditionFilter_vmfield($query, $options, $num);
    }

    private function processConditionFilter_vmfield(&$query, $options, $num)
    {
        $query->join['vmfield_user'] = '#__virtuemart_userinfos AS vmfield_user ON user.cms_id = vmfield_user.virtuemart_user_id';
        $query->where[] = $query->convertQuery('vmfield_user', $options['field'], $options['operator'], $options['value']);
    }

    public function onAcymProcessCondition_vmreminder(&$query, $options, $num, &$conditionNotValid)
    {
        $this->processConditionFilter_vmreminder($query, $options, $num);
        $affectedRows = $query->count();
        if (empty($affectedRows)) $conditionNotValid++;
    }

    public function onAcymProcessFilter_vmreminder(&$query, $options, $num)
    {
        $this->processConditionFilter_vmreminder($query, $options, $num);
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

    public function onAcymProcessFilter_vmpurchased(&$query, $options, $num)
    {
        $this->processConditionFilter_vmpurchased($query, $options, $num);
    }

    private function processConditionFilter_vmpurchased(&$query, $options, $num)
    {
        $query->join['vmpurchased_user_'.$num] = '`#__virtuemart_order_userinfos` AS `vmorderuserinfos_'.$num.'` ON `vmorderuserinfos_'.$num.'`.`email` = `user`.`email`';
        $query->join['vmpurchased_order_'.$num] = '`#__virtuemart_orders` AS `vmorder_'.$num.'` ON `vmorder_'.$num.'`.`virtuemart_order_id` = `vmorderuserinfos_'.$num.'`.`virtuemart_order_id`';
        $query->where[] = '`vmorder_'.$num.'`.`order_status` IN ("C", "F", "U")';

        if (!empty($options['datemin'])) {
            $options['datemin'] = acym_replaceDate($options['datemin']);
            if (is_numeric($options['datemin'])) $options['datemin'] = acym_date($options['datemin'], 'Y-m-d H:i:s', false);
            $query->where[] = '`vmorder_'.$num.'`.created_on > '.acym_escapeDB($options['datemin']);
        }

        if (!empty($options['datemax'])) {
            $options['datemax'] = acym_replaceDate($options['datemax']);
            if (is_numeric($options['datemax'])) $options['datemax'] = acym_date($options['datemax'], 'Y-m-d H:i:s', false);
            $query->where[] = '`vmorder_'.$num.'`.created_on < '.acym_escapeDB($options['datemax']);
        }

        $join = '`#__virtuemart_order_items` AS `vmorderitem_'.$num.'` ON `vmorderitem_'.$num.'`.`virtuemart_order_id` = `vmorderuserinfos_'.$num.'`.`virtuemart_order_id` ';
        if (!empty($options['product'])) {
            $query->join['vmpurchased_item_'.$num] = $join;
            $query->where[] = '`vmorderitem_'.$num.'`.`virtuemart_product_id` = '.intval($options['product']);
        } elseif (!empty($options['category'])) {
            $query->join['vmpurchased_item_'.$num] = $join;
            $query->join['vmpurchased_products_'.$num] = '`#__virtuemart_products` AS vp'.$num.' ON vmorderitem_'.$num.'.virtuemart_product_id = vp'.$num.'.virtuemart_product_id';
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

    public function onAcymDeclareFilters(&$filters)
    {
        $this->filtersFromConditions($filters);
    }

    public function onAcymProcessFilterCount_vmgroups(&$query, $options, $num)
    {
        $this->processConditionFilter_vmgroups($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilterCount_vmfield(&$query, $options, $num)
    {
        $this->processConditionFilter_vmfield($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilterCount_vmreminder(&$query, $options, $num)
    {
        $this->processConditionFilter_vmreminder($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymProcessFilterCount_vmpurchased(&$query, $options, $num)
    {
        $this->processConditionFilter_vmpurchased($query, $options, $num);

        return acym_translationSprintf('ACYM_SELECTED_USERS', $query->count());
    }

    public function onAcymDeclareSummary_filters(&$automationFilter)
    {
        $this->summaryConditionFilters($automationFilter);
    }

    public function onAcymDeclareTriggers(&$triggers)
    {
        $triggers['user']['vmorder'] = new stdClass();
        $triggers['user']['vmorder']->name = acym_translationSprintf('ACYM_COMBINED_TRANSLATIONS', 'VirtueMart', acym_translation('ACYM_WHEN_ORDER'));
        $triggers['user']['vmorder']->option = '<input type="hidden" name="[triggers][user][vmorder][]" value="">';
    }

    public function onAcymExecuteTrigger(&$step, &$execute, &$data)
    {
        if (empty($data['userId'])) return;

        $triggers = $step->triggers;

        if (!empty($triggers['vmorder'])) {
            $execute = true;
        }
    }

    public function onAcymDeclareSummary_triggers(&$automation)
    {
        if (!empty($automation->triggers['vmorder'])) $automation->triggers['vmorder'] = acym_translation('ACYM_WHEN_ORDER');
    }

    public function onRegacyOptionsDisplay($lists)
    {
        if (!$this->installed) return;

        ?>
		<div class="acym__configuration__subscription acym__content acym_area padding-vertical-1 padding-horizontal-2">
			<div class="acym__title acym__title__secondary"><?php echo acym_escape(acym_translationSprintf('ACYM_XX_INTEGRATION', $this->pluginDescription->name)); ?></div>

			<div class="grid-x">
				<div class="cell grid-x grid-margin-x">
                    <?php
                    $subOptionTxt = acym_translationSprintf('ACYM_SUBSCRIBE_OPTION_ON_XX_CHECKOUT', $this->pluginDescription->name).acym_info(
                            'ACYM_SUBSCRIBE_OPTION_ON_XX_CHECKOUT_DESC'
                        );
                    echo acym_switch(
                        'config[virtuemart_sub]',
                        $this->config->get('virtuemart_sub'),
                        $subOptionTxt,
                        [],
                        'xlarge-3 medium-5 small-9',
                        'auto',
                        '',
                        'acym__config__virtuemart_sub'
                    );
                    ?>
				</div>
				<div class="cell grid-x margin-y" id="acym__config__virtuemart_sub">
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__virtuemart-text">
                            <?php echo acym_translation('ACYM_SUBSCRIBE_CAPTION').acym_info('ACYM_SUBSCRIBE_CAPTION_OPT_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
						<input type="text"
							   name="config[virtuemart_text]"
							   id="acym__config__virtuemart-text"
							   value="<?php echo acym_escape($this->config->get('virtuemart_text')); ?>" />
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__virtuemart-lists">
                            <?php echo acym_translation('ACYM_DISPLAYED_LISTS').acym_info('ACYM_DISPLAYED_LISTS_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo acym_selectMultiple(
                            $lists,
                            'config[virtuemart_lists]',
                            explode(',', $this->config->get('virtuemart_lists', '')),
                            ['class' => 'acym__select', 'id' => 'acym__config__virtuemart-lists'],
                            'id',
                            'name'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>

					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__virtuemart-checkedlists">
                            <?php echo acym_translation('ACYM_LISTS_CHECKED_DEFAULT').acym_info('ACYM_LISTS_CHECKED_DEFAULT_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo acym_selectMultiple(
                            $lists,
                            'config[virtuemart_checkedlists]',
                            explode(',', $this->config->get('virtuemart_checkedlists', '')),
                            ['class' => 'acym__select', 'id' => 'acym__config__virtuemart-checkedlists'],
                            'id',
                            'name'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__virtuemart-autolists">
                            <?php echo acym_translation('ACYM_AUTO_SUBSCRIBE_TO').acym_info('ACYM_SUBSCRIBE_OPTION_AUTO_SUBSCRIBE_TO_DESC'); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo acym_selectMultiple(
                            $lists,
                            'config[virtuemart_autolists]',
                            explode(',', $this->config->get('virtuemart_autolists', '')),
                            ['class' => 'acym__select', 'id' => 'acym__config__virtuemart-autolists'],
                            'id',
                            'name'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell xlarge-3 medium-5">
						<label for="acym__config__virtuemart-regacy-listsposition">
                            <?php echo acym_escape(acym_translation('ACYM_LISTS_POSITION')); ?>
						</label>
					</div>
					<div class="cell xlarge-4 medium-7">
                        <?php
                        echo acym_select(
                            acym_getOptionRegacyPosition(),
                            'config[virtuemart_regacy_listsposition]',
                            $this->config->get('virtuemart_regacy_listsposition', 'password'),
                            'class="acym__select" data-toggle-select="'.acym_escape('{"custom":"#acym__config__virtuemart__regacy__custom-list-position"}').'"',
                            'value',
                            'text',
                            'acym__config__virtuemart-regacy-listsposition'
                        );
                        ?>
					</div>
					<div class="cell xlarge-5 hide-for-medium-only hide-for-small-only"></div>
					<div class="cell grid-x" id="acym__config__virtuemart__regacy__custom-list-position">
						<div class="cell xlarge-3 medium-5"></div>
						<div class="cell xlarge-4 medium-7">
							<input type="text"
								   name="config[virtuemart_regacy_listspositioncustom]"
								   value="<?php echo acym_escape($this->config->get('virtuemart_regacy_listspositioncustom')); ?>" />
						</div>
					</div>
				</div>
			</div>
		</div>
        <?php
    }

    public function onBeforeSaveConfigFields(&$formData)
    {
        if (empty($formData['virtuemart_lists'])) $formData['virtuemart_lists'] = [];
        if (empty($formData['virtuemart_checkedlists'])) $formData['virtuemart_checkedlists'] = [];
        if (empty($formData['virtuemart_autolists'])) $formData['virtuemart_autolists'] = [];
    }

    public function onRegacyAddComponent(&$components)
    {
        $config = acym_config();
        if (!$config->get('virtuemart_sub', 0) || acym_isAdmin()) return;

        $components['com_virtuemart'] = [
            'view' => ['user', 'cart', 'shop.registration', 'account.billing', 'checkout.index', 'editaddresscart', 'editaddresscheckout', 'askquestion'],
            'lengthafter' => 500,
            'valueClass' => 'controls',
            'baseOption' => 'virtuemart',
        ];
    }

    public function onRegacyAfterRoute()
    {
        //We are updating the user information from VM...
        $option = acym_getVar('string', 'option', '');
        $acySource = acym_getVar('string', 'acy_source', '');
        if ($option == 'com_virtuemart' && $acySource == 'virtuemart registration form') {
            $this->updateVM();
        }
    }

    private function updateVM()
    {
        $config = acym_config();
        if (!$config->get('virtuemart_sub', 0) || acym_isAdmin()) return;

        $email = acym_getVar('string', 'email', '');
        $selectedLists = acym_getVar('array', 'virtuemart_visible_lists_checked', []);
        $autoLists = explode(',', $config->get('virtuemart_autolists', ''));
        $listsToSubscribe = array_merge($selectedLists, $autoLists);

        if (empty($email) || empty($listsToSubscribe)) return;

        // Get existing AcyMailing user or create one
        $userClass = new UserClass();
        $user = $userClass->getOneByEmail($email);
        if (empty($user)) {
            $user = new stdClass();
            $user->email = $email;

            $userName = acym_getVar('string', 'name', '');
            if (empty($userName)) {
                $userNameArray = [];
                $userNameArray[] = acym_getVar('string', 'first_name', '');
                $userNameArray[] = acym_getVar('string', 'middle_name', '');
                $userNameArray[] = acym_getVar('string', 'last_name', '');
                $userName = trim(implode(' ', $userNameArray));
            }
            if (!empty($userName)) $user->name = $userName;

            $user->source = 'virtuemart';
            $user->id = $userClass->save($user);
        }

        if (empty($user->id)) return;

        // Subscribe the user
        $userClass->subscribe($user->id, $listsToSubscribe);
    }
}
